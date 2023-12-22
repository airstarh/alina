<?php

namespace alina\mvc\Model;

class pm_task extends _BaseAlinaModel
{
    public $table        = 'pm_task';
    public $addAuditInfo = true;

    public function fields()
    {
        return [
            'id'            => [],
            'name_human'    => [],
            'pm_project_id' => [],
            'manager_id'    => [],
            'assignee_id'   => [],
            'created_at'    => [],
            'completed_at'  => [],
            'status'        => [],
        ];
    }

    #####
    public function referencesTo()
    {
        return [
            ##### field #####
            'manager_id'    => [
                'has'        => 'one',
                'multiple'   => false,
                ##############################
                # for Apply dependencies
                'apply'      => [
                    'childTable'     => 'user',
                    'childPk'        => 'id',
                    'childHumanName' => ['firstname', 'lastname', 'mail'],
                    'masterChildPk'  => 'manager_id',
                ],
                ##############################
                # for Select With References
                'joins'      => [
                    ['leftJoin', 'user AS manager', 'manager.id', '=', "{$this->alias}.manager_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'manager.firstname AS manager.firstname',
                            'manager.lastname AS manager.lastname',
                            'manager.mail AS manager.mail',
                            'manager.emblem AS manager.emblem',
                        ],
                    ],
                ],
            ],
            ##### field #####
            'assignee_id'   => [
                'has'        => 'one',
                'multiple'   => false,
                ##############################
                # for Apply dependencies
                'apply'      => [
                    'childTable'     => 'user',
                    'childPk'        => 'id',
                    'childHumanName' => ['firstname', 'lastname', 'mail'],
                    'masterChildPk'  => 'manager_id',
                ],
                ##############################
                # for Select With References
                'joins'      => [
                    ['leftJoin', 'user AS assignee', 'assignee.id', '=', "{$this->alias}.assignee_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'assignee.firstname AS assignee.firstname',
                            'assignee.lastname AS assignee.lastname',
                            'assignee.mail AS assignee.mail',
                            'assignee.emblem AS assignee.emblem',
                        ],
                    ],
                ],
            ],
            ##### field #####
            'pm_project_id' => [
                'has'        => 'one',
                'multiple'   => false,
                ##############################
                # for Apply dependencies
                'apply'      => [
                    'childTable'     => 'pm_project',
                    'childPk'        => 'id',
                    'childHumanName' => ['name_human'],
                    'masterChildPk'  => 'pm_project_id',
                ],
                ##############################
                # for Select With References
                'joins'      => [
                    ['leftJoin', 'pm_project AS pm_project', 'pm_project.id', '=', "{$this->alias}.pm_project_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'pm_project.name_human AS pm_project.name_human',
                            'pm_project.price_multiplier AS pm_project.price_multiplier',
                        ],
                    ],
                ],
            ],
            ##### field #####
        ];
    }
    #####
}
