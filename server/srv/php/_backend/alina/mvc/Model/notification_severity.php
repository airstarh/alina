<?php

namespace alina\mvc\Model;

use alina\Utils\Data;
use Illuminate\Database\Capsule\Manager as Dal;
use Illuminate\Database\Query\Builder as BuilderAlias;

class notification_severity extends _BaseAlinaModel
{
    public $table = 'notification_severity';

    public function fields()
    {
        return [
            'id'         => [],
            'human_name' => [],
            'class'      => [],
        ];
    }
    ##################################################
}
