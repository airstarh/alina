<?php
namespace alina;

class app
{
    #region Officials
    public $name    = 'Alina';
    public $version = 2;
    public $license = 'Free For All';
    #endregion Officials

    #region Initiation
    protected function __construct($config = [])
    {
        $this->init();
        $this->autoload($config);
        $this->setConfig($config);
        set_exception_handler([\alina\core\catchErrorsExceptions::obj(), 'exception']);
        set_error_handler([\alina\core\catchErrorsExceptions::obj(), 'error']);
    }

    public function init()
    {
        // Fasade functions
        require_once PATH_TO_ALINA_BACKEND_DIR . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . '_independent' . DIRECTORY_SEPARATOR . '_autoloadFunctions.php';
        require_once PATH_TO_ALINA_BACKEND_DIR . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . '_dependent' . DIRECTORY_SEPARATOR . '_autoloadFunctions.php';
    }

    public function autoload($config)
    {
        spl_autoload_extensions(".php");
        spl_autoload_register();
        // Fix of PHP bug. Please, see: https://bugs.php.net/bug.php?id=52339
        //spl_autoload_register(function(){});
        spl_autoload_register(function ($class) use ($config) {
            $extension = '.php';

            // For Alina
            $className = ltrim($class, '\\');
            $className = ltrim($className, 'alina');
            $className = ltrim($className, '\\');
            $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
            $classFile = $className . $extension;
            $classPath = PATH_TO_ALINA_BACKEND_DIR . DIRECTORY_SEPARATOR . $classFile;
            if (file_exists($classPath)) {
                require_once $classPath;
            }

            // For Application
            if (!isset($config['appNamespace']) || empty($config['appNamespace'])) {
                return NULL;
            }
            $className = ltrim($class, '\\');
            $className = ltrim($className, $config['appNamespace']);
            $className = ltrim($className, '\\');
            $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
            $classFile = $className . $extension;
            $classPath = PATH_TO_APP_DIR . DIRECTORY_SEPARATOR . $classFile;
            if (file_exists($classPath)) {
                require_once $classPath;
            }
        });
    }

    public $config        = [];
    public $configDefault = [];

    protected function setConfig($config = [])
    {
        $defaultConfigPath   = normalPath(__DIR__ . '/configs/default.php');
        $defaultConfig       = require($defaultConfigPath);
        $this->configDefault = $defaultConfig;
        $this->config        = arrayMergeRecursive($this->configDefault, $config);
        static::$instance    = $this;

        return $this;
    }
    #endregion Initiation

    #region Instantiation
    static public $instance = NULL;

    /**
     * @return \alina\app
     */
    static public function get()
    {
        if (!isset(static::$instance) || !is_a(static::$instance, '\alina\app')) {
            throw new \Exception("App is not set");
        }

        return static::$instance;
    }

    /**
     * @return \alina\app
     */
    static public function set($config)
    {
        if (isset(static::$instance) && is_a('\alina\app', static::$instance)) {
            throw new \Exception("App is set already.");
        }
        $_this = new static($config);

        return $_this;
    }
    #endregion Instantiation

    #region Config manipulations
    static public function getConfig($path)
    {
        $_this = static::get();
        $cfg   = $_this->config;

        return getArrayValue($path, $cfg);
    }

    static public function getConfigDefault($path)
    {
        $_this = static::get();
        $cfg   = $_this->configDefault;

        return getArrayValue($path, $cfg);
    }

    #endregion Config manipulations

    #region Routes
    /** @var \alina\core\router */
    public $router;

    public function defineRoute()
    {
        $this->router              = \alina\core\router::obj();
        $this->router->vocAliasUrl = static::getConfig(['vocAliasUrl']);
        $this->router->processUrl();

        /*
         * This will redirect user to Page's Alias
         */
        if (static::getConfig(['forceSysPathToAlias'])) {
            if ($this->router->pathAlias == $this->router->pathSys) {
                $this->router->forcedAlias = routeAccordance($this->router->pathSys, $this->router->vocAliasUrl, FALSE);
                if ($this->router->forcedAlias != $this->router->pathSys) {
                    redirect($this->router->forcedAlias);
                }
            }
        }

        return $this;
    }
    #endregion Routes

    #region MVC
    public $controller;
    public $action;
    public $actionParams = [];
    const ACTION_PREFIX  = 'action';
    const DEFAULT_ACTION = 'index';

    public function mvcControllerAction($controller, $action, $params = [])
    {
        return returnClassMethod($controller, $action, $params);
    }

    public function fullActionName($name)
    {
        return static::ACTION_PREFIX . ucfirst($name);
    }

    public function mvcGo($controller = NULL, $action = NULL, $params = NULL)
    {
        $this->controller   = (isset($controller)) ? $controller : $this->router->controller;
        $this->action       = (isset($action)) ? $action : $this->router->action;
        $this->actionParams = (isset($params)) ? $params : $this->router->pathParameter;

        if (empty($this->controller) && empty($this->action)) {
            return $this->mvcDefaultPage();
        }
        if (empty($this->controller)) {
            return $this->mvcPageNotFound();
        }
        if (empty($this->action)) {
            $this->action = static::DEFAULT_ACTION;
        }

        // Defined by route in user app.
        try {
            $namespace      = static::getConfig('appNamespace');
            $controllerPath = static::getConfig('mvc/structure/controller');
            $controller     = $this->controller;
            $controller     = fullClassName($namespace, $controllerPath, $controller);
            $action         = $this->fullActionName($this->action);
            $params         = $this->actionParams;

            return $this->mvcControllerAction($controller, $action, $params);
        }
        catch (\Exception $e) {
            // Defined by route in Alina
            try {
                $namespace      = static::getConfigDefault('appNamespace');
                $controllerPath = static::getConfigDefault('mvc/structure/controller');
                $controller     = $this->controller;
                $controller     = fullClassName($namespace, $controllerPath, $controller);
                $action         = $this->fullActionName($this->action);;
                $params = $this->actionParams;
                return $this->mvcControllerAction($controller, $action, $params);

            }
            catch (\Exception $e) {
                return $this->mvcPageNotFound();
            }
        }
    }

    public function mvcPageNotFound()
    {
        http_response_code(404);
        // 404 of user app
        try {
            $namespace      = static::getConfig('appNamespace');
            $controllerPath = static::getConfig('mvc/structure/controller');
            $controller     = static::getConfig('mvc/pageNotFoundController');
            $controller     = fullClassName($namespace, $controllerPath, $controller);
            $action         = $this->fullActionName(static::getConfig('mvc/pageNotFoundAction'));

            return $this->mvcControllerAction($controller, $action);
        }
        catch (\Exception $e) {
            // 404 of Alina
            try {
                $namespace      = static::getConfigDefault('appNamespace');
                $controllerPath = static::getConfigDefault('mvc/structure/controller');
                $controller     = static::getConfigDefault('mvc/pageNotFoundController');
                $controller     = fullClassName($namespace, $controllerPath, $controller);
                $action         = $this->fullActionName(static::getConfigDefault('mvc/pageNotFoundAction'));

                return $this->mvcControllerAction($controller, $action);
            }
            catch (\Exception $e) {
                throw new \Exception('Total Fail');
            }
        }

    }

    public function mvcDefaultPage()
    {
        // Default page of user app
        try {
            $namespace      = static::getConfig('appNamespace');
            $controllerPath = static::getConfig('mvc/structure/controller');
            $controller     = static::getConfig('mvc/defaultController');
            $controller     = fullClassName($namespace, $controllerPath, $controller);
            $action         = $this->fullActionName(static::getConfig('mvc/defaultAction'));

            return $this->mvcControllerAction($controller, $action);
        }
        catch (\Exception $e) {
            // Default page of Alina
            try {
                $namespace      = static::getConfigDefault(['appNamespace']);
                $controllerPath = static::getConfigDefault('mvc/structure/controller');
                $controller     = static::getConfigDefault('mvc/defaultController');
                $controller     = fullClassName($namespace, $controllerPath, $controller);
                $action         = $this->fullActionName(static::getConfigDefault('mvc/defaultAction'));

                return $this->mvcControllerAction($controller, $action);
            }
            catch (\Exception $e) {
                throw new \Exception('No index page');
            }
        }

    }
    #endregion MVC
}