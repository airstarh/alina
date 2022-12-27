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