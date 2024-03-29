<?php

namespace alina\mvc\Model;

use alina\Message;
use alina\Utils\DateTime;

class pm_work_done extends _BaseAlinaModel
{
    use pm_trait;

    public $table        = 'pm_work_done';
    public $addAuditInfo = true;

    public function fields()
    {
        return [
            'id'            => [],
            'pm_work_id'    => [],
            'assignee_id'   => [
                'default' => CurrentUser::id(),
            ],
            'amount'        => [
                'required' => true,
            ],
            'price_final'   => [
                'type' => 'readonly',
            ], /*calculation*/
            'time_spent'    => [
                'type' => 'readonly',
            ], /*calculation*/
            'for_date'      => [
                'filters' => [
                    function ($v) {

                        if (empty($v)) return null;
                        if (!is_numeric($v)) {
                            if (is_string($v)) {
                                $v = DateTime::dateToUnixTime($v);
                            }
                        }

                        return $v;
                    },
                ],
            ],
            'flag_archived' => ['default' => 0,],
            'created_at'    => [],
            'created_by'    => [],
            'modified_at'   => [],
            'modified_by'   => [],
        ];
    }

    #####
    public function referencesTo()
    {
        return array_merge([],
            [
                ##### field ######
                'assignee_id' => [
                    'has'        => 'one',
                    'multiple'   => false,
                    ##############################
                    # for Apply dependencies
                    'apply'      => [
                        'childTable'     => 'user',
                        'childPk'        => 'id',
                        'childHumanName' => ['firstname', 'lastname', 'mail'],
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
                ##### field ######
                'pm_work_id'  => [
                    'disabled'   => true,
                    'has'        => 'one',
                    'multiple'   => false,
                    ##############################
                    # for Apply dependencies
                    //'apply'      => [
                    //    'childTable'     => 'pm_work',
                    //    'childPk'        => 'id',
                    //    'childHumanName' => ['name_human'],
                    //],
                    ##############################
                    # for Select With References
                    'joins'      => [
                        ['leftJoin', 'pm_work AS pm_work', 'pm_work.id', '=', "{$this->alias}.pm_work_id"],
                        ['leftJoin', 'pm_subtask AS pm_subtask', 'pm_subtask.id', '=', 'pm_work.pm_subtask_id'],
                        ['leftJoin', 'pm_task AS pm_task', 'pm_task.id', '=', 'pm_work.pm_task_id'],
                        ['leftJoin', 'pm_project AS pm_project', 'pm_project.id', '=', 'pm_work.pm_project_id'],
                        ['leftJoin', 'pm_department AS pm_department', 'pm_department.id', '=', 'pm_work.pm_department_id'],
                        ['leftJoin', 'pm_organization AS pm_organization', 'pm_organization.id', '=', 'pm_work.pm_organization_id'],
                    ],
                    'conditions' => [],
                    'addSelects' => [
                        [
                            'addSelect',
                            [
                                'pm_work.name_human AS pm_work.name_human',
                                'pm_subtask.name_human AS pm_subtask.name_human',
                                'pm_task.name_human AS pm_task.name_human',
                                'pm_project.name_human AS pm_project.name_human',
                                'pm_department.name_human AS pm_department.name_human',
                                'pm_organization.name_human AS pm_organization.name_human',
                            ],
                        ],
                    ],
                ],
                ##### field ######
            ],
            $this->created_by(),
            $this->modified_by()
        );
    }

    public function hookRightBeforeSave(&$dataArray)
    {
        if ($dataArray['flag_archived'] == 0) {

            $mWork = new pm_work();
            $mWork->getById($dataArray['pm_work_id']);
            $w_price_this_work = $mWork->attributes->price_this_work;

            $wd_price_final           = $this->calcPriceFinal($dataArray['amount'], $w_price_this_work);
            $dataArray['price_final'] = $wd_price_final;

            $mDepartment = new pm_department();
            $mDepartment->getById($mWork->attributes->pm_department_id);
            $d_price_min = $mDepartment->attributes->price_min;

            $mProject = new pm_project();
            $mProject->getById($mWork->attributes->pm_project_id);
            $p_price_multiplier = $mProject->attributes->price_multiplier;

            $wd_time_spent           = $this->calcTimeSpent($wd_price_final, $d_price_min, $p_price_multiplier);
            $dataArray['time_spent'] = $wd_time_spent;
        }

        return $this;
    }

    public function hookRightAfterSave($data)
    {
        if ($data->flag_archived == 1) {

            _baseAlinaEloquentTransaction::begin();

            $mWorkStory = new pm_work_story();
            $mWorkStory->doArchiveWorkDone($data->id);

            _baseAlinaEloquentTransaction::commit();

        }

        if ($data->flag_archived == 0) {
            (new pm_work_story())->delete([
                    ['pm_work_done_id', '=', $data->id],
                ]
            );
        }
    }

    public function calcPriceFinal($amount, $w_price_this_work)
    {
        return $amount * $w_price_this_work;
    }

    public function calcTimeSpent($wd_price_final, $d_price_min, $p_price_multiplier)
    {
        return $wd_price_final / $p_price_multiplier / $d_price_min;
    }

    public function doArchive($idWorkDone = null)
    {
        if (!empty($idWorkDone)) {
            $this->getById($idWorkDone);
        }
        else {
            $this->getById($this->id);
        }

        $item                = $this->attributes;
        $item->flag_archived = 1;
        $this->updateById($item);


        return $this;
    }

    public function doUnArchive($idWorkDone = null)
    {
        if (!empty($idWorkDone)) {
            $this->getById($idWorkDone);
        }
        else {
            $this->getById($this->id);
        }

        $item                = $this->attributes;
        $item->flag_archived = 0;
        $this->updateById($item);

        return $this;
    }

    #####
}
