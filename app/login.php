<?php

use app\language\LanguageManager;
use app\session\SessionManager;
use app\template\PageFooterBuilder;
use app\template\PageHeaderBuilder;
use app\template\PageSidebarBuilder;
use app\user\UserManager;

// Include the page top
require_once('top.php');

// Make sure the user isn't logged in
if(SessionManager::isLoggedIn()) {
    header('Location: index.php');
    die();
}

// Check whether the user is trying to login, if not show the login form instead
if(!isset($_POST['login_user']) || !isset($_POST['login_password'])) {
    // Get the default user
    $userValue = '';
    if(isset($_GET['user']))
        $userValue = trim($_GET['user']);

    // Determine whether to show a back button
    $showBackButton = true;
    if(isset($_GET['back']))
        $showBackButton = $_GET['back'] == 1;

    ?>
    <div data-role="page" id="page-login">
        <?php PageHeaderBuilder::create(__('account', 'login'))->setBackButton($showBackButton ? 'index.php' : null)->build(); ?>
        <div data-role="main" class="ui-content">
            <p><?= __('login', 'enterUsernamePasswordToLogin'); ?></p><br />
            <form method="POST" action="login.php?a=login">
                <input type="text" name="login_user" value="<?=$userValue; ?>" placeholder="<?= __('account', 'username'); ?>" />
                <input type="password" name="login_password" value="" placeholder="<?= __('account', 'password'); ?>" />
                <br />

                <fieldset data-role="controlgroup" data-type="vertical" class="ui-shadow ui-corner-all">
                    <input type="submit" value="<?= __('account', 'login'); ?>" class="ui-btn ui-icon-lock ui-btn-icon-right" />
                </fieldset>

                <fieldset data-role="controlgroup" data-type="vertical" class="ui-shadow ui-corner-all"">
                    <a href="register.php" class="ui-btn ui-shadow"><?= __('account', 'register'); ?></a>
                    <a href="#" class="ui-btn ui-shadow"><?= __('account', 'forgotPassword'); ?></a>
                </fieldset>
            </form>
        </div>
        <?php

        // Build the footer and sidebar
        PageFooterBuilder::create()->build();
        PageSidebarBuilder::create()->build();

        ?>
    </div>
    <?php

} else {
    // Get the username/email and password
    $loginUser = trim($_POST['login_user']);
    $loginPassword = $_POST['login_password'];

    // Validate the user credentials, and show an error message if the credentials are invalid
    if(($user = UserManager::validateLogin($loginUser, $loginPassword)) === null)
        showErrorPage(__('login', 'usernameOrPasswordIncorrect'));

    // Create a session for the user
    if(!SessionManager::createSession($user))
        showErrorPage();

    // Get and apply the user's language if set
    if(($userLang = LanguageManager::getUserLanguageTag($user)) !== null)
        LanguageManager::setLanguageTag($userLang, true, true, false);

    // Redirect to the front page
    header('Location: index.php');

    ?>
    <div data-role="page" id="page-main">
        <?php PageHeaderBuilder::create()->build(); ?>
        <div data-role="main" class="ui-content">
            <p>
                <?= __('general', 'welcome'); ?> <?=$user->getFullName(); ?>!<br />
                <br />
                <?= __('login', 'loginSuccess'); ?>
            </p>
            <br />

            <fieldset data-role="controlgroup" data-type="vertical">
                <a href="index.php" data-ajax="false"
                   class="ui-btn ui-icon-carat-r ui-btn-icon-left"><?= __('navigation', 'continue'); ?></a>
            </fieldset>
        </div>
        <?php

        // Build the footer and sidebar
        PageFooterBuilder::create()->build();
        PageSidebarBuilder::create()->build();
        ?>
    </div>
    <?php
}

// Include the page bottom
require_once('bottom.php');
