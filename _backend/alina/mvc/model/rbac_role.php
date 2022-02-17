<?php

namespace alina\mvc\model;

class rbac_role extends _BaseAlinaModel
{
    public $table = 'rbac_role';

    public function fields()
    {
        return [
            'id'  => [],
            'name'  => [],
            'description'  => [],
            'active'  => [],
        ];
    }

    public function uniqueKeys()
    {
        return [
            ['name']
        ];
    }
}