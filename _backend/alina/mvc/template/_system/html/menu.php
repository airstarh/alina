<?php

use alina\mvc\model\CurrentUser;
use alina\utils\Html;

$cu = alina\mvc\model\CurrentUser::obj();
$ua = $cu->attributes();
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="/"><?= AlinaCfg('title'); ?></a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav w-100">
            <li class="nav-item dropdown ml-auto">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown"
                   aria-haspopup="true" aria-expanded="false">
                    <?= $cu->name() ?>
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                    <?php if (!$cu->isLoggedIn()): ?><a class="dropdown-item" href="<?= Html::aRef('/auth/register/') ?>">Register</a> <?php endif; ?>
                    <?php if (!$cu->isLoggedIn()): ?><a class="dropdown-item" href="<?= Html::aRef('/#/auth/login/') ?>">LogIn</a><?php endif; ?>
                    <?php if ($cu->isLoggedIn()): ?><a class="dropdown-item" href="<?= Html::aRef('/#/auth/profile') ?>">Profile</a><?php endif; ?>
                    <?php if ($cu->isLoggedIn()): ?><a class="dropdown-item" href="<?= Html::aRef('/#/auth/change_password') ?>">Change Password</a><?php endif; ?>
                    <?php if (!$cu->isLoggedIn()): ?><a class="dropdown-item" href="<?= Html::aRef('/#/auth/reset_password_request') ?>">Reset Password Request</a><?php endif; ?>
                    <div class="dropdown-divider"></div>
                    <?php if ($cu->isLoggedIn()): ?><a class="dropdown-item" href="<?= Html::aRef('/#/auth/logOut/') ?>">Exit</a><?php endif; ?>
                </div>
            </li>
        </ul>
    </div>
</nav>
