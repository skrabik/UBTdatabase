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

$this->registerCssFile('https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css');
$this->registerJsFile('https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js', ['position' => \yii\web\View::POS_END]);

$this->registerCss(<<<CSS
.js-auto-resize-textarea {
    overflow-y: hidden;
    resize: none;
}

.zen-post-editor-font {
    font-size: 1.125rem;
}

.zen-post-title-input {
    font-weight: 700;
}

.zen-post-quill-field .ql-toolbar.ql-snow,
.zen-post-quill-field .ql-container.ql-snow {
    border-color: #ced4da;
}

.zen-post-quill-editor {
    min-height: 260px;
    background: #fff;
}

.zen-post-quill-editor .ql-editor {
    font-size: 1.125rem;
}

.zen-post-quill-field.is-invalid .ql-toolbar.ql-snow,
.zen-post-quill-field.is-invalid .ql-container.ql-snow {
    border-color: #dc3545;
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

    var hiddenInput = document.getElementById('zenpost-content');
    var editorElement = document.getElementById('zen-post-content-editor');
    if (!hiddenInput || !editorElement || typeof Quill === 'undefined') {
        return;
    }

    var quill = new Quill(editorElement, {
        theme: 'snow',
        placeholder: 'Введите текст поста',
        modules: {
            toolbar: [
                [{ header: [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['blockquote', 'link'],
                [{ align: [] }],
                ['clean']
            ]
        }
    });

    var initialValue = hiddenInput.value || '';
    if (/<[a-z][\s\S]*>/i.test(initialValue)) {
        quill.root.innerHTML = initialValue;
    } else if (initialValue !== '') {
        quill.setText(initialValue);
    }

    function syncEditorValue() {
        var html = quill.root.innerHTML;
        hiddenInput.value = quill.getText().trim() === '' ? '' : html;
    }

    quill.on('text-change', syncEditorValue);
    syncEditorValue();

    var form = hiddenInput.closest('form');
    if (form) {
        form.addEventListener('submit', syncEditorValue);
    }
})();
JS);
?>
<div class="zen-post-form">
    <h1><?= Html::encode($this->title) ?></h1>
    <p class="text-muted">Тема (сценарий) задаёт основу и смысл будущего поста, а в тексте можно сохранить уже готовый вариант публикации.</p>

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'scenario')->textarea([
        'rows' => 4,
        'class' => 'form-control js-auto-resize-textarea zen-post-editor-font',
    ]) ?>
    <?= $form->field($model, 'title')->textInput([
        'maxlength' => true,
        'class' => 'form-control zen-post-title-input zen-post-editor-font',
    ]) ?>
    <div class="mb-3">
        <?= Html::activeLabel($model, 'content', ['class' => 'form-label']) ?>
        <div class="zen-post-quill-field<?= $model->hasErrors('content') ? ' is-invalid' : '' ?>">
            <div id="zen-post-content-editor" class="zen-post-quill-editor"></div>
        </div>
        <?= Html::activeTextarea($model, 'content', [
            'id' => 'zenpost-content',
            'class' => 'd-none',
        ]) ?>
        <?= Html::error($model, 'content', ['class' => 'invalid-feedback d-block']) ?>
    </div>
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