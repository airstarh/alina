<?php
/** @var $data html */

use alina\mvc\view\html;
use alina\utils\Sys;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    require_once ALINA_WEB_PATH . '/sources/searchengiines/000.php';
    ?>
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="/manifest.json"/>
    <link rel="icon" href="/favicon.svg">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $data->pageTitle() ?>"/>
    <title><?= $data->pageTitle() ?></title>
    <?php if ($data->tagRelAlternateUrl()) { ?>
        <link rel="alternate" href="<?= $data->tagRelAlternateUrl() ?>"/>
    <?php } ?>
    <meta property="og:description" content="<?= $data->pageDescription() ?>"/>
    <?= $data->js() ?>
    <?= $data->css() ?>
</head>
<body id="alina-real-body" style="background-color: #343a40; color: #ffffff">
<div id="alina-body-wrapper" class="bg-dark text-white">
    <?= (new html())->piece('/_system/html/menu.php') ?>
    <div class="container p-0 alina-content">
        <?= $data->messages(); ?>
        <?= $data->content(); ?>
    </div> <!-- /container -->
    <?= (new html())->piece('/_system/html/_commonFooter.php') ?>
</div>
</body>
</html>
