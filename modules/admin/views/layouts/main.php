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
$this->registerCss(<<<CSS
.navbar .navbar-nav .nav-link:hover,
.navbar .navbar-nav .nav-link:focus,
.navbar .navbar-nav .nav-item .btn-link.nav-link:hover,
.navbar .navbar-nav .nav-item .btn-link.nav-link:focus {
    background-color: rgba(255, 255, 255, 0.15);
    border-radius: 0.25rem;
}
#logoutModal .modal-dialog,
#confirmModal .modal-dialog { margin: 1.75rem auto; }
#logoutModal .modal-content,
#confirmModal .modal-content { border: none; border-radius: 0.5rem; box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.2); }
#logoutModal .modal-header,
#confirmModal .modal-header { border-bottom: 1px solid #dee2e6; padding: 1rem 1.25rem; }
#logoutModal .modal-body,
#confirmModal .modal-body { padding: 1.25rem; font-size: 1rem; }
#logoutModal .modal-footer,
#confirmModal .modal-footer { border-top: 1px solid #dee2e6; padding: 1rem 1.25rem; gap: 0.5rem; }
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
<body>
<?php $this->beginBody() ?>
<?php
NavBar::begin([
    'brandLabel' => 'Панель управления',
    'brandUrl' => ['/admin/default/index'],
    'options' => ['class' => 'navbar-expand-md navbar-dark bg-dark'],
]);
echo Nav::widget([
    'options' => ['class' => 'navbar-nav me-auto'],
    'items' => [
        ['label' => 'Аккаунты Дзен', 'url' => ['/admin/zen-account/index']],
        ['label' => 'Тематики каналов', 'url' => ['/admin/theme/index']],
        ['label' => 'Пользователи и роли', 'url' => ['/admin/user/index']],
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
    document.getElementById('logout-confirm-btn').addEventListener('click', function() {
        document.getElementById('logout-form').submit();
    });

    var confirmModal = document.getElementById('confirmModal');
    var confirmTitle = document.getElementById('confirmModalLabel');
    var confirmBody = document.getElementById('confirmModalBody');
    var confirmSubmit = document.getElementById('confirm-modal-submit');
    var pendingLink = null;

    document.body.addEventListener('click', function(e) {
        var el = e.target.closest('a[data-confirm-modal], a[data-confirm]');
        if (!el) return;
        e.preventDefault();
        pendingLink = el;
        confirmTitle.textContent = el.getAttribute('data-confirm-title') || 'Подтверждение';
        confirmBody.textContent = el.getAttribute('data-confirm-modal') || el.getAttribute('data-confirm') || 'Вы уверены?';
        var modal = new bootstrap.Modal(confirmModal);
        modal.show();
    }, true);

    confirmSubmit.addEventListener('click', function() {
        if (!pendingLink) return;
        var href = pendingLink.getAttribute('href');
        var method = (pendingLink.getAttribute('data-method') || 'get').toLowerCase();
        if (method === 'post') {
            var form = document.createElement('form');
            form.method = 'post';
            form.action = href;
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
            form.submit();
        } else {
            window.location.href = href;
        }
        bootstrap.Modal.getInstance(confirmModal).hide();
        pendingLink = null;
    });
})();
JS
);
?>
<main class="container py-4">
    <?= Breadcrumbs::widget(['links' => $this->params['breadcrumbs'] ?? []]) ?>
    <?= Alert::widget() ?>
    <?= $content ?>
</main>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
