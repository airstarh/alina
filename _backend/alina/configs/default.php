<?php
switch (ALINA_ENV) {
    case  'HOME':
    case  'DA':
    default:
        return [
            'appNamespace'        => 'alina',
            'title'               => 'Alina: another PHP framework. Powered by OrcTechService.',
            'fileUploadDir'       => "F:\\_Z_F_UPLOAD\\",
            'db'                  => [
                'driver'    => 'mysql',
                'host'      => 'localhost',
                'database'  => 'alina',
                'username'  => 'root',
                'password'  => '',
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix'    => '',
            ],
            'mvc'                 => [
                'defaultController'       => 'root',
                'defaultAction'           => 'Index',
                'pageNotFoundController'  => 'root',
                'pageNotFoundAction'      => '404',
                'pageExceptionController' => 'root',
                'pageExceptionAction'     => 'Exception',

                // Relative Class Namespace Path.
                'structure'               => [
                    'controller' => 'mvc\controller',
                    'model'      => 'mvc\model',
                    'view'       => 'mvc\view',
                    'template'   => 'mvc\template',
                ],
            ],

            // Routes, Aliases.
            'forceSysPathToAlias' => TRUE,
            'vocAliasUrl'         => [
                'действие/:p2/контроллер/:p1' => 'cont/act/:p1/:p2', // /действие/ВТОРОЙ_ПАРАМЕТР/контроллер/ПЕРВЫЙ_ПАРАМЕТР
            ],

            'debug' => [
                //'toPage' => TRUE,
                //'toDb'   => TRUE,
                'toFile' => TRUE,
            ],

            'html' => [

                'css' => [
                    // Jquery; Jquery UI
                    'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css',

                    //Bootstrap Framework.
                    // https://getbootstrap.com/docs/4.3/getting-started/introduction/
                    'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css',
                    //'/sources/node_modules/bootstrap/dist/css/bootstrap-theme.min.css',

                    // Alina
                    '/sources/css/alina.css',
                    //'/frontend/css/alina_form.css',
                ],

                'js'   => [
                    // Jquery; Jquery UI
                    // https://code.jquery.com/
                    // https://code.jquery.com/ui/
                    'https://code.jquery.com/jquery-3.4.1.js',
                    'https://code.jquery.com/ui/1.12.1/jquery-ui.js',
                    //'/sources/node_modules/popper.js/dist/popper.min.js',

                    //Bootstrap Framework.
                    // https://getbootstrap.com/docs/4.3/getting-started/introduction/
                    'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js',
                    'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js',

                    // Alina.
                    '/sources/js/001_alina_init.js',
                    //'/sources/js/002_alina_hash_catcher.js',
                ],
                'meta' => [],
            ],
        ];
        break;
}
