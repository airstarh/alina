<?php

namespace alina\mvc\controller;


class root
{
    public function actionIndex()
    {
        echo (new \alina\mvc\view\html)->page();
    }

    public function action404()
    {
        echo '<pre>';
        print_r('Alina core 404. Page not found.');
        echo '</pre>';
    }

    public function actionException()
    {
        if (isAjax()) {
            echo \alina\message::returnAllMessages();

            return TRUE;
        }

        echo (new \alina\mvc\view\html)->page();

        return TRUE;
    }
}