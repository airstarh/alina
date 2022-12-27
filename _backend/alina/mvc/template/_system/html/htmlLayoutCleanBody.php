<?php
/** @var $data html */

use alina\mvc\view\html;
use alina\utils\Sys;

?>
<!DOCTYPE html>
<html lang="en" style="background-color: #343a40; color: #fff;">
<head>
    <? require_once '_commonHead.php' ?>
</head>
<body id="alina-real-body" class="bg-dark text-white">
<body id="alina-real-body" class="bg-dark text-white">
<div class="alina-flex-vertical-container alina-vh-100">
    <div class="alina-flex-vertical-header">
        <?= (new html())->piece(html::$htmlMenu) ?>
    </div>
    <div class="alina-flex-vertical-content alina-flex-item-wide">
        &nbsp;
    </div>
    <div class="alina-flex-vertical-content alina-flex-item-shrink container">
        <?= $data->content(); ?>
        <?= $data->messages(); ?>
    </div>
    <div class="alina-flex-vertical-content alina-flex-item-wide">
        &nbsp;
    </div>
    <div class="alina-flex-vertical-footer">
        <?= (new html())->piece(html::$htmlFooter) ?>
    </div>
</div>
<? require_once '_commonFooter2.php' ?>
</body>
</body>
</html>