<?php
/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use app\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;

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
$this->registerCss(<<<CSS
#logoutModal .modal-dialog,
#confirmModal .modal-dialog { margin: 1.75rem auto; }
#logoutModal .modal-header,
#confirmModal .modal-header { padding: 1rem 1.25rem; }
#logoutModal .modal-body,
#confirmModal .modal-body { padding: 1.25rem; font-size: 1rem; }
#logoutModal .modal-footer,
#confirmModal .modal-footer { padding: 1rem 1.25rem; gap: 0.5rem; }
CSS
);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <title><?= Html::encode($this->title) ?> — Админка</title>
    <?php $this->head() ?>
</head>
<body class="admin-theme">
<?php $this->beginBody() ?>
<?php
NavBar::begin([
    'brandLabel' => 'Панель управления',
    'brandUrl' => ['/admin/default/index'],
    'options' => ['class' => 'navbar-expand-md navbar-dark admin-navbar'],
]);
echo Nav::widget([
    'options' => ['class' => 'navbar-nav me-auto'],
    'items' => [
        ['label' => 'Аккаунты Дзен', 'url' => ['/admin/zen-account/index']],
        ['label' => 'Тематики каналов', 'url' => ['/admin/theme/index']],
        ['label' => 'Пользователи и роли', 'url' => ['/admin/user/index']],
        '<li class="nav-item d-flex align-items-center me-2">'
            . Html::button('', [
                'class' => 'btn btn-sm admin-theme-toggle js-admin-theme-toggle',
                'type' => 'button',
                'aria-live' => 'polite',
            ])
            . '</li>',
        '<li class="nav-item">'
            . Html::beginForm(['/admin/logout'], 'post', ['class' => 'd-flex', 'id' => 'logout-form'])
            . Html::button('Выход (' . Html::encode(Yii::$app->user->identity->username) . ')', [
                'class' => 'nav-link btn btn-link',
                'type' => 'button',
                'data-bs-toggle' => 'modal',
                'data-bs-target' => '#logoutModal',
            ])
            . Html::endForm()
            . '</li>',
    ],
]);
NavBar::end();

// Модальное окно подтверждения выхода
echo Html::beginTag('div', [
    'class' => 'modal fade',
    'id' => 'logoutModal',
    'tabindex' => -1,
    'aria-labelledby' => 'logoutModalLabel',
    'aria-hidden' => 'true',
]);
echo Html::beginTag('div', ['class' => 'modal-dialog modal-dialog-centered']);
echo Html::beginTag('div', ['class' => 'modal-content']);
echo Html::beginTag('div', ['class' => 'modal-header']);
echo Html::tag('h5', 'Выход из аккаунта', ['class' => 'modal-title', 'id' => 'logoutModalLabel']);
echo Html::button('', [
    'type' => 'button',
    'class' => 'btn-close',
    'data-bs-dismiss' => 'modal',
    'aria-label' => 'Закрыть',
]);
echo Html::endTag('div');
echo Html::beginTag('div', ['class' => 'modal-body']);
echo Html::tag('p', 'Вы уверены, что хотите выйти?', ['class' => 'mb-0']);
echo Html::endTag('div');
echo Html::beginTag('div', ['class' => 'modal-footer']);
echo Html::button('Отмена', [
    'type' => 'button',
    'class' => 'btn btn-secondary',
    'data-bs-dismiss' => 'modal',
]);
echo Html::button('Выйти', [
    'type' => 'button',
    'class' => 'btn btn-primary',
    'id' => 'logout-confirm-btn',
]);
echo Html::endTag('div');
echo Html::endTag('div');
echo Html::endTag('div');
echo Html::endTag('div');
echo Html::endTag('div');

// Универсальное модальное окно подтверждения (как для выхода)
echo Html::beginTag('div', [
    'class' => 'modal fade',
    'id' => 'confirmModal',
    'tabindex' => -1,
    'aria-labelledby' => 'confirmModalLabel',
    'aria-hidden' => 'true',
]);
echo Html::beginTag('div', ['class' => 'modal-dialog modal-dialog-centered']);
echo Html::beginTag('div', ['class' => 'modal-content']);
echo Html::beginTag('div', ['class' => 'modal-header']);
echo Html::tag('h5', 'Подтверждение', ['class' => 'modal-title', 'id' => 'confirmModalLabel']);
echo Html::button('', [
    'type' => 'button',
    'class' => 'btn-close',
    'data-bs-dismiss' => 'modal',
    'aria-label' => 'Закрыть',
]);
echo Html::endTag('div');
echo Html::beginTag('div', ['class' => 'modal-body']);
echo Html::tag('p', '', ['class' => 'mb-0', 'id' => 'confirmModalBody']);
echo Html::endTag('div');
echo Html::beginTag('div', ['class' => 'modal-footer']);
echo Html::button('Отмена', [
    'type' => 'button',
    'class' => 'btn btn-secondary',
    'data-bs-dismiss' => 'modal',
]);
echo Html::button('Подтвердить', [
    'type' => 'button',
    'class' => 'btn btn-primary',
    'id' => 'confirm-modal-submit',
]);
echo Html::endTag('div');
echo Html::endTag('div');
echo Html::endTag('div');
echo Html::endTag('div');

$this->registerJs(<<<JS
(function() {
    var storageKey = 'admin-theme';
    var logoutConfirmBtn = document.getElementById('logout-confirm-btn');
    var logoutForm = document.getElementById('logout-form');
    var confirmModal = document.getElementById('confirmModal');
    var confirmTitle = document.getElementById('confirmModalLabel');
    var confirmBody = document.getElementById('confirmModalBody');
    var confirmSubmit = document.getElementById('confirm-modal-submit');
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

    if (logoutConfirmBtn && logoutForm) {
        logoutConfirmBtn.addEventListener('click', function() {
            logoutForm.submit();
        });
    }

    if (!confirmModal || !confirmTitle || !confirmBody || !confirmSubmit) {
        return;
    }

    var modal = bootstrap.Modal.getOrCreateInstance(confirmModal);
    var pendingAction = null;
    var isSubmitting = false;

    document.body.addEventListener('click', function(e) {
        var el = e.target.closest('a[data-confirm-modal], a[data-confirm]');
        if (!el) {
            return;
        }

        // Полностью останавливаем исходный клик, чтобы Yii не выполнил data-method
        // раньше, чем пользователь нажмет кнопку подтверждения в модальном окне.
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        pendingAction = {
            href: el.getAttribute('href'),
            method: (el.getAttribute('data-method') || 'get').toLowerCase(),
        };
        isSubmitting = false;
        confirmSubmit.disabled = false;
        confirmTitle.textContent = el.getAttribute('data-confirm-title') || 'Подтверждение';
        confirmBody.textContent = el.getAttribute('data-confirm-modal') || el.getAttribute('data-confirm') || 'Вы уверены?';
        modal.show();
    }, true);

    confirmModal.addEventListener('hidden.bs.modal', function() {
        if (!isSubmitting) {
            pendingAction = null;
        }
        isSubmitting = false;
        confirmSubmit.disabled = false;
    });

    confirmSubmit.addEventListener('click', function(e) {
        e.preventDefault();

        if (!pendingAction || isSubmitting) {
            return;
        }

        isSubmitting = true;
        confirmSubmit.disabled = true;

        if (pendingAction.method === 'post') {
            var form = document.createElement('form');
            form.method = 'post';
            form.action = pendingAction.href;
            form.style.display = 'none';
            var csrfParam = document.querySelector('meta[name="csrf-param"]');
            var csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfParam && csrfToken) {
                var input = document.createElement('input');
                input.name = csrfParam.getAttribute('content');
                input.value = csrfToken.getAttribute('content');
                input.type = 'hidden';
                form.appendChild(input);
            }
            document.body.appendChild(form);
            modal.hide();
            form.submit();
            return;
        }

        var href = pendingAction.href;
        pendingAction = null;
        modal.hide();
        window.location.href = href;
    });
})();
JS
);
?>
<main class="container py-4 admin-shell">
    <?= Breadcrumbs::widget(['links' => $this->params['breadcrumbs'] ?? []]) ?>
    <?= Alert::widget() ?>
    <?= $content ?>
</main>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
