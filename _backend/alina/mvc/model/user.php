<?php
namespace alina\mvc\model;

class user extends alinaLaravelSimpleModel
{
    public $table='user';

    public function fields() {
        return [
            'id'=>[],
            'mail'=>[],
            'firstname'=>[],
            'lastname'=>[],
            'active'=>[],
            'verified'=>[],
            'created'=>[],
            'lastenter'=>[],
            'picture'=>[],
            'timezone'=>[],
            'password'=>[],
        ];
    }

    public function uniqueKeys() {
        return [
            ['mail']
        ];
    }
}