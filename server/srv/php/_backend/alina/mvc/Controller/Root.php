<?php

namespace alina\mvc\Controller;

use alina\Message;
use alina\mvc\View\html;
use alina\Utils\Request;

class Root
{
    public function actionIndex()
    {
        require_once(ALINA_WEB_PATH . '/apps/vue/index.html');
    }

    public function actionFrontend()
    {
        require_once(ALINA_WEB_PATH . '/apps/vue/index.html');
    }

    public function actionIndex2()
    {
        $vd = (object)[
            '/main/CheckAutoload'                                => 'CLEAN Check Custom Zero Class ',
            '/main/CheckAutoload/qq/aa?Par1=ASD&Par2=привет мир' => 'Check Custom Zero Class ',
            '/AdminTests/Redirect1'                              => 'Redirect',
            '/AdminTests/somedata'                               => 'Some Data',
            '/AdminTests/ConversionToObject'                     => 'Conversion to Object',
            '/AdminTests/BaseAlinaModel'                         => 'action BaseAlinaModel',
            '/AdminTests/ReversibleEncryption'                   => 'Test Reversible Encryption',
            '/AdminTests/Mailer'                                 => 'Test Mail Send',
            '/FileUpload/Common'                                 => 'File Upload',
            '/main/index'                                        => 'ZERO',
            '/AdminTests/TestMessages'                           => 'Messages',
            '/Auth/Login'                                        => 'Auth Login',
            '/Auth/Profile'                                      => 'Auth User',
            '/Auth/ChangePassword'                               => 'Auth actionChangePassword',
            '/Auth/Register?lala=lala'                           => 'Auth Register',
            '/Auth/logout?lala=lala'                             => 'Auth Log Out',
            '/Auth/ResetPasswordRequest?lala=lala'               => 'Auth ResetPasswordRequest',
            '/Auth/ResetPasswordWithCode?lala=lala'              => 'Auth ResetPasswordWithCode',
            '/root/index?lalala=333'                             => 'Root with GET',
            '/egCookie/Test001'                                  => 'COOKIE',
            '/FormPatternsInvestigation/index/'                  => 'Form Patterns Investigation',
            '/AdminDbManager/EditRow/user/1'                     => 'Edit a DB line',
            '/alinaRestAccept/index?cmd=Model&m=user&mId=1'      => 'Rest call',
            '/NotExistingPage'                                   => 'Test 404',
            '/tools/SerializedDataEditor'                        => 'Serialized Data Editor',
            '/CtrlDataTransformations/json'                      => 'JSON search-replace',
            '/AdminDbManager/DbTablesColumnsInfo'                => 'MySQL Manager',
            '/SendRestApiQueries/BaseCurlCalls'                  => 'HTTP calls',
            '/AdminTests/Errors'                                 => 'Tst Errors',
            '/AdminTests/Serialization'                          => 'Tst Serialization',
            '/AdminTests/JsonEncode'                             => 'Tst Json Encode',
        ];
        echo (new html)->page($vd);
    }

    public function actionIndex3()
    {
        $vd = \alina\Utils\FS::dirToClassActionIndex(ALINA_PATH_TO_FRAMEWORK . '/mvc/Controller');
        echo (new html)->page($vd);
    }

    public function action404()
    {
        AlinaResponseSuccess(0);
        http_response_code(404);
        echo (new html)->page();
        exit;
    }

    public function actionException($vd = NULL)
    {
        AlinaResponseSuccess(0);
        http_response_code(500);
        echo (new html)->page($vd, html::$htmLayoutErrorCatcher);
        exit;
    }

    public function actionAccessDenied($code = 403)
    {
        AlinaResponseSuccess(0);
        http_response_code($code);
        echo (new html)->page(NULL, html::$htmLayoutErrorCatcher);
        exit;
    }
}
