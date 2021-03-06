<?php
/** @var $data html */

use alina\mvc\view\html;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    require_once ALINA_PATH_TO_FRAMEWORK . '/mvc/template/_system/html/searchengiines/google.php';
    ?>
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="/manifest.json"/>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $data->pageTitle() ?></title>
    <?= $data->js() ?>
    <?= $data->css() ?>
</head>
<body id="alina-real-body" style="background-color: #343a40; color: #ffffff">
<div id="alina-body-wrapper" class="bg-dark text-white">
    <?= (new \alina\mvc\view\html())->piece('/_system/html/menu.php') ?>
    <div class="container-sm">

        <?= $data->messages(); ?>
        <?= $data->content(); ?>
    </div> <!-- /container -->
    <?php require_once(__DIR__ . '/_commonFooter.php') ?>
</div>
</body>
</html>
