<?php

namespace alina\mvc\model;

use alina\cookie;
use alina\Message;
use alina\MessageAdmin;
use alina\session;
use alina\traits\Singleton;
use alina\utils\Data;
use alina\utils\Request;
use alina\utils\Sys;

class CurrentUser
{
    ##################################################
    #region SingleTon
    use Singleton;
    const KEY_USER_ID    = 'uid';
    const KEY_USER_TOKEN = 'token';
    public    $id    = NULL;
    protected $token = NULL;
    /**@var user */
    protected $USER = NULL;
    /**@var login */
    protected $LOGIN = NULL;
    protected $device_ip;
    protected $device_browser_enc;
    ##########
    protected $state_AUTHORIZATION_PASSED  = FALSE;
    protected $state_AUTHORIZATION_SUCCESS = FALSE;
    protected $state_USER_DEFINED          = FALSE;
    ##########
    public $msg = [];

    protected function __construct()
    {
        $this->reset();
        $this->authorize();
    }

    protected function reset()
    {
        $this->device_ip          = Request::obj()->IP;
        $this->device_browser_enc = Request::obj()->BROWSER_enc;
        $this->resetDiscoveredData();
        $this->resetStates();
    }

    public function resetDiscoveredData()
    {
        $this->msg   = [];
        $this->id    = NULL;
        $this->token = NULL;
        $this->USER  = new user();
        $this->LOGIN = new login();
    }

    public function resetStates()
    {
        $this->state_AUTHORIZATION_PASSED  = FALSE;
        $this->state_AUTHORIZATION_SUCCESS = FALSE;
        $this->state_USER_DEFINED          = FALSE;
    }
    #endregion SingleTon
    ##################################################
    #region LogIn

    protected function identify($conditions)
    {
        if ($this->authenticate()) {
            $this->msg[] = 'You are already Logged-in';

            return FALSE;
        }
        #####
        $this->reset();
        #####
        $this->getUSER($conditions);
        if (empty($this->USER->id)) {
            $this->msg[] = 'Incorrect credentials';

            return FALSE;
        }

        $data = $this->buildLoginData();
        $this->LOGIN->insert($data);
        $this->getLOGIN($data);

        return $this->rememberAuthInfo($this->USER->id, $this->LOGIN->attributes->token);
    }

    protected function authenticate()
    {
        $id    = $this->discoverId();
        $token = $this->discoverToken();
        #####
        $this->getLOGIN([
            'user_id' => $id,
            'token'   => $token,
        ]);
        if ($this->LOGIN->id) {
            $this->getUSER([
                "{$this->USER->alias}.{$this->USER->pkName}" => $id,
            ]);
        }

        #####
        return $this->checkConsistency();
    }

    protected function authorize()
    {
        #####
        if ($this->state_AUTHORIZATION_PASSED) {
            return $this->state_AUTHORIZATION_SUCCESS;
        }
        #####
        $isAuthenticated = $this->authenticate();
        if ($isAuthenticated) {
            $data     = $this->buildLoginData();
            $newToken = $data['token']; // ACCENT
            $this->LOGIN->updateById($data);
            $this->token = $newToken;
            //$this->getLOGIN($data);
            ##########
            $this->USER->updateById([
                'last_time'        => ALINA_TIME,
                'last_browser_enc' => $this->device_browser_enc,
                'last_ip'          => $this->device_ip,
            ]);
            ##########
            $this->state_AUTHORIZATION_SUCCESS = $this->rememberAuthInfo($this->id, $newToken);
        }
        #####
        $this->state_AUTHORIZATION_PASSED = TRUE;

        return $this->state_AUTHORIZATION_SUCCESS;
    }

    public function LogInByPass($mail, $password)
    {
        if (!Data::isValidMd5($password)) {
            $password = md5($password);
        }
        $conditions = [
            'mail'     => $mail,
            'password' => $password,
        ];
        if ($this->identify($conditions)) {
            return $this->authorize();
        }

        return FALSE;
    }
    #endregion LogIn
    ##################################################
    #region LogOut
    public function LogOut()
    {
        return $this->forgetAuthInfo();
    }
    #endregion LogOut
    ##################################################
    #region Register
    public function Register($vd)
    {
        $u = $this->USER;
        //ToDo: Add Browser
        //ToDo: Add other data
        $vd->ip               = Sys::getUserIp();
        $vd->date_int_created = ALINA_TIME;
        $u->insert($vd);
        if (isset($u->id)) {
            $ur = new _BaseAlinaModel(['table' => 'rbac_user_role']);
            $ur->insert([
                'user_id' => $u->id,
                //TODo: Hardcoded, 5-servants
                'role_id' => 5,
            ]);
            if (isset($ur->id)) {
                $this->msg[] = 'Registration has passed successfully!';
            }
        }

        return $this;
    }
    #endregion Register
    ##################################################
    #region States
    public function hasRole($role)
    {
        if ($this->isLoggedIn()) {
            return $this->USER->hasRole($role);
        }

        return FALSE;
    }

    public function hasPerm($perm)
    {
        if ($this->isLoggedIn()) {
            return $this->USER->hasPerm($perm);
        }

        return FALSE;
    }

    public function isLoggedIn()
    {
        $res = $this->authorize();

        return $res;
    }

    public function isAdmin()
    {
        if ($this->isLoggedIn()) {
            return $this->hasRole('ADMIN');
        }

        return FALSE;

    }
    #endregion States
    ##################################################
    #region Utils
    protected function discoverId()
    {
        $id = NULL;
        if (empty($id)) {
            $id = $this->USER->id;
        }
        if (empty($id)) {
            $id = session::get(static::KEY_USER_ID);
        }
        if (empty($id)) {
            $id = cookie::get(static::KEY_USER_ID);
        }
        if (empty($id)) {
            $id = Request::obj()->tryHeader(static::KEY_USER_ID);
        }
        $this->id = $id;

        return $id;
    }

    protected function discoverToken()
    {
        $token = NULL;
        if (empty($token)) {
            $token = $this->token;
        }
        if (empty($token)) {
            $token = session::get(static::KEY_USER_TOKEN);
        }
        if (empty($token)) {
            $token = cookie::get(static::KEY_USER_TOKEN);
        }
        if (empty($token)) {
            $token = Request::obj()->tryHeader(static::KEY_USER_TOKEN);
        }
        $this->token = $token;

        return $token;
    }

    protected function buildToken()
    {
        if (
            Request::obj()->AJAX
            &&
            !empty($this->LOGIN->attributes->token)
        ) {
            return $this->LOGIN->attributes->token;
        }

        if (!empty($this->LOGIN->attributes->token)) {
            if ($this->LOGIN->attributes->expires_at > ALINA_TIME) {
                $this->LOGIN->attributes->token;
            }
        }

        $u           = $this->USER;
        $ua          = $u->attributes;
        $tokenSource = [
            $ua->id,
            $ua->mail,
            $ua->password,
            ALINA_TIME,
        ];
        $token       = md5(implode('', $tokenSource));

        return $token;
    }

    protected function buildLoginData()
    {
        $newToken = $this->buildToken();
        $data     = [
            'user_id'     => $this->USER->id,
            'token'       => $newToken,
            'ip'          => $this->device_ip,
            'browser_enc' => $this->device_browser_enc,
            'expires_at'  => ALINA_AUTH_EXPIRES,
            'lastentered' => ALINA_TIME,
        ];

        return $data;
    }

    public function attributes()
    {
        unset($this->USER->attributes->password);

        return $this->USER->attributes;
    }

    protected function rememberAuthInfo($uid, $token)
    {
        if (!$this->checkConsistency()) {
            return FALSE;
        }
        #####
        cookie::set(static::KEY_USER_TOKEN, $token);
        cookie::set(static::KEY_USER_ID, $uid);
        #####
        session::set(static::KEY_USER_TOKEN, $token);
        session::set(static::KEY_USER_ID, $uid);
        #####
        header(implode(': ', [
            static::KEY_USER_TOKEN,
            $token,
        ]));
        header(implode(': ', [
            static::KEY_USER_ID,
            $uid,
        ]));

        return TRUE;
    }

    protected function forgetAuthInfo()
    {
        if ($this->isLoggedIn()) {
            #####
            $this->LOGIN->deleteById($this->LOGIN->id);
            #####
            cookie::delete(static::KEY_USER_TOKEN);
            cookie::delete(static::KEY_USER_ID);
            #####
            session::delete(static::KEY_USER_TOKEN);
            session::delete(static::KEY_USER_ID);
            #####
            header_remove(static::KEY_USER_ID);
            header_remove(static::KEY_USER_TOKEN);
            #####
            $this->reset();

            #####
            return TRUE;
        }

        return FALSE;
    }

    public function name()
    {
        $res = $this->USER->attributes->mail;
        if (empty($res)) {
            $res = 'Not Logged-in';
        }

        return $res;
    }

    public function ownsId($id)
    {
        return $this->isLoggedIn() && $this->id === $id;
    }

    protected function getUSER($conditions)
    {
        if ($this->state_USER_DEFINED) {
            return $this->USER;
        }
        $this->USER->getOneWithReferences($conditions);
        $this->id                 = $this->USER->id;
        $this->state_USER_DEFINED = TRUE;

        return $this->USER;
    }

    protected function getLOGIN($conditions)
    {
        $conditions = array_merge($conditions, [
            ['expires_at', '>', ALINA_TIME],
        ]);
        $this->LOGIN->getOne($conditions);
        $this->token = $this->LOGIN->attributes->token;

        return $this->LOGIN;
    }

    private function checkConsistency()
    {
        if (empty($this->USER->id)) {
            $this->msg[] = 'User undefined';

            return FALSE;
        }

        if (empty($this->LOGIN->id)) {
            $this->msg[] = 'Login undefined';

            return FALSE;
        }
        ##################################################
        if ($this->id !== $this->USER->id) {
            $this->msg[] = 'User mismatch';

            return FALSE;
        }
        if ($this->token !== $this->LOGIN->attributes->token) {
            $this->msg[] = 'Token mismatch';

            return FALSE;
        }
        ##################################################
        if ($this->USER->id !== $this->LOGIN->attributes->user_id) {
            $this->msg[] = 'User ID differs from Logged one';

            return FALSE;
        }
        ##################################################
        if ($this->device_ip !== $this->LOGIN->attributes->ip) {
            $this->msg[] = 'IP mismatch';

            return FALSE;
        }

        if ($this->device_browser_enc !== $this->LOGIN->attributes->browser_enc) {
            $this->msg[] = 'Browser mismatch';

            return FALSE;
        }
        ##################################################
        if (ALINA_TIME >= $this->LOGIN->attributes->expires_at) {
            $this->msg[] = 'Token expired';

            return FALSE;
        }

        if (ALINA_TIME <= $this->USER->attributes->banned_till) {
            $this->msg[] = 'User banned';

            return FALSE;
        }

        return TRUE;
    }

    public function messages()
    {
        foreach ($this->msg as $i => $v) {
            Message::set($v);
        }
    }
    #endregion Utils
    ##################################################
}
