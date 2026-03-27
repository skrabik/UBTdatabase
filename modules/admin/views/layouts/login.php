<?php
/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use app\widgets\Alert;
use yii\bootstrap5\Html;

AppAsset::register($this);
$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1']);
$this->registerJs(<<<JS
(function() {
    var storageKey = 'admin-theme';
    var theme = 'dark';
    try {
        var savedTheme = window.localStorage.getItem(storageKey);
        if (savedTheme === 'light' || savedTheme === 'dark') {
            theme = savedTheme;
        }
    } catch (e) {}
    document.documentElement.setAttribute('data-admin-theme', theme);
})();
JS, \yii\web\View::POS_HEAD, 'admin-theme-init');
$this->registerJs(<<<JS
(function() {
    var storageKey = 'admin-theme';
    var themeToggleButtons = document.querySelectorAll('.js-admin-theme-toggle');

    function getTheme() {
        return document.documentElement.getAttribute('data-admin-theme') === 'light' ? 'light' : 'dark';
    }

    function updateThemeButtons() {
        var nextTheme = getTheme() === 'dark' ? 'light' : 'dark';
        var label = nextTheme === 'dark' ? 'Тёмная тема' : 'Светлая тема';
        themeToggleButtons.forEach(function(button) {
            button.textContent = label;
            button.setAttribute('aria-label', 'Включить ' + label.toLowerCase());
            button.setAttribute('title', 'Включить ' + label.toLowerCase());
        });
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-admin-theme', theme);
        try {
            window.localStorage.setItem(storageKey, theme);
        } catch (e) {}
        updateThemeButtons();
    }

    themeToggleButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            applyTheme(getTheme() === 'dark' ? 'light' : 'dark');
        });
    });

    updateThemeButtons();
})();
JS);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="admin-theme admin-theme-login">
<?php $this->beginBody() ?>
<div class="container py-5 admin-login-shell">
    <div class="admin-login-toolbar">
        <?= Html::button('', [
            'class' => 'btn btn-sm admin-theme-toggle js-admin-theme-toggle',
            'type' => 'button',
            'aria-live' => 'polite',
        ]) ?>
    </div>
    <?= Alert::widget() ?>
    <?= $content ?>
</div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
