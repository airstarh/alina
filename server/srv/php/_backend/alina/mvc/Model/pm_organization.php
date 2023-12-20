<?php

namespace alina\mvc\Model;

class pm_organization extends _BaseAlinaModel
{
    public $table = 'pm_organization';

    public function fields()
    {
        return [
            'id'         => [],
            'name_human' => [
                'default' => 'Организация',
            ],
            'manager_id' => [],
        ];
    }

    #####
    public function referencesTo()
    {
        return [
            ##### field #####
            'manager' => [
                'has'        => 'one',
                'joins'      => [
                    ['leftJoin', 'user AS manager', 'manager.id', '=', "{$this->alias}.manager_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'manager.firstname AS manager_firstname',
                            'manager.lastname AS manager_lastname',
                            'manager.emblem AS manager_emblem',
                        ],
                    ],
                ],
            ],
            ##### field #####
        ];
    }
    #####
}