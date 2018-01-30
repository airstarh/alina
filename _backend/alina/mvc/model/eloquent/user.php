<?php
namespace alina\mvc\model\eloquent;

use \alina\mvc\model\eloquent\_base AS BaseEloquentModel;

class user extends BaseEloquentModel
{
    protected $table      = 'user';
    protected $primaryKey = 'id';
    protected $guarded    = [];
    protected $dateFormat = 'U';
    public    $timestamps = FALSE;
    const CREATED_AT = 'creation_date';
    const UPDATED_AT = 'last_update';


    #region Dynamic Attributes EAV

    #endregion Dynamic Attributes EAV

    #region Custom
    public function fields()
    {
        return [
            'id'        => [],
            'mail'      => [],
            'firstname' => [],
            'lastname'  => [],
            'active'    => [],
            'verified'  => [],
            'created'   => [],
            'lastenter' => [],
            'picture'   => [],
            'timezone'  => [],
            'password'  => [],
        ];
    }

    public function uniqueKeys()
    {
        return [
            ['mail'],
        ];
    }
    #endregion Custom
}