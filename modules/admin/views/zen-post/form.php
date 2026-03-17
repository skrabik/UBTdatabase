<?php
/** @var yii\web\View $this */
/** @var app\models\ZenPost $model */
/** @var int $accountId */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$accountId = $accountId ?? $model->account_id;
$this->title = $model->isNewRecord ? 'Новый пост' : 'Редактировать пост';
$this->params['breadcrumbs'][] = ['label' => 'Аккаунты Яндекс.Дзен', 'url' => ['/admin/zen-account/index']];
$this->params['breadcrumbs'][] = ['label' => 'Посты', 'url' => ['/admin/zen-post/index', 'account_id' => $accountId]];
$this->params['breadcrumbs'][] = $this->title;

$this->registerCss(<<<CSS
.js-auto-resize-textarea {
    overflow-y: hidden;
    resize: none;
}
CSS);

$this->registerJs(<<<JS
(function () {
    function resizeTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    }

    function initAutoResize(textarea) {
        if (!textarea) {
            return;
        }

        resizeTextarea(textarea);
        textarea.addEventListener('input', function () {
            resizeTextarea(textarea);
        });
    }

    document.querySelectorAll('.js-auto-resize-textarea').forEach(initAutoResize);
})();
JS);
?>
<div class="zen-post-form">
    <h1><?= Html::encode($this->title) ?></h1>
    <p class="text-muted">Тема (сценарий) задаёт основу и смысл будущего поста, а в тексте можно сохранить уже готовый вариант публикации.</p>

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'scenario')->textarea([
        'rows' => 4,
        'class' => 'form-control js-auto-resize-textarea',
    ]) ?>
    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'content')->textarea([
        'rows' => 6,
        'class' => 'form-control js-auto-resize-textarea',
    ]) ?>
    <?php if (!$model->isNewRecord): ?>
        <?= $form->field($model, 'status')->checkbox([
            'label' => 'Опубликован',
            'value' => \app\models\ZenPost::STATUS_POSTED,
            'uncheck' => \app\models\ZenPost::STATUS_PENDING,
            'checked' => $model->status === \app\models\ZenPost::STATUS_POSTED,
        ]) ?>
    <?php endif; ?>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
        <?php if (!$model->isNewRecord): ?>
            <?= Html::a('Запустить workflow', ['/admin/zen-post/run-workflow', 'account_id' => $accountId, 'id' => $model->id], [
                'class' => 'btn btn-outline-primary',
                'data-method' => 'post',
                'data-confirm-title' => 'Запустить workflow',
                'data-confirm-modal' => 'Запустить workflow для этого поста? Будут отправлены scenario, post_id и channel_id.',
            ]) ?>
        <?php endif; ?>
        <?= Html::a('Отмена', ['/admin/zen-post/index', 'account_id' => $accountId], ['class' => 'btn btn-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>