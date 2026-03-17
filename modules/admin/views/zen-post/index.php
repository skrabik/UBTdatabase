<?php
/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var int|null $accountId */

use app\models\ZenPostPublishAttempt;
use yii\bootstrap5\Html;
use yii\grid\GridView;
use yii\helpers\Url;

$this->title = $accountId ? 'Посты аккаунта' : 'Все посты';
$this->params['breadcrumbs'][] = ['label' => 'Аккаунты Яндекс.Дзен', 'url' => ['/admin/zen-account/index']];
if ($accountId) {
    $account = \app\models\ZenAccount::findOne($accountId);
    $this->params['breadcrumbs'][] = $account ? $account->name : (string) $accountId;
}
$this->params['breadcrumbs'][] = $this->title;

$this->registerCss(<<<CSS
.zen-post-scenario-cell {
    position: relative;
    padding-right: 34px;
}

.zen-post-scenario-copy {
    position: absolute;
    top: 0;
    right: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border: 0;
    background: transparent;
    color: #6c757d;
    line-height: 1;
    padding: 0;
    cursor: pointer;
}

.zen-post-scenario-copy svg {
    width: 18px;
    height: 18px;
}

.zen-post-scenario-copy:hover {
    color: #0d6efd;
}

.zen-post-scenario-copy.is-copied {
    color: #198754;
}
CSS);

$this->registerJs(<<<JS
(function () {
    document.body.addEventListener('change', function (event) {
        var checkbox = event.target.closest('.js-post-status-toggle');
        if (!checkbox) {
            return;
        }

        var form = document.createElement('form');
        var csrfParam = document.querySelector('meta[name="csrf-param"]');
        var csrfToken = document.querySelector('meta[name="csrf-token"]');

        form.method = 'post';
        form.action = checkbox.checked ? checkbox.dataset.urlPosted : checkbox.dataset.urlPending;
        form.style.display = 'none';

        if (csrfParam && csrfToken) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = csrfParam.getAttribute('content');
            input.value = csrfToken.getAttribute('content');
            form.appendChild(input);
        }

        document.body.appendChild(form);
        form.submit();
    });

    document.body.addEventListener('click', function (event) {
        var button = event.target.closest('.js-copy-scenario');
        var textarea;

        if (!button) {
            return;
        }

        event.preventDefault();

        function showCopiedState() {
            var initialTitle = button.getAttribute('data-title') || button.getAttribute('title') || '';
            button.classList.add('is-copied');
            button.disabled = true;
            setTimeout(function () {
                button.classList.remove('is-copied');
                button.disabled = false;
                if (initialTitle) {
                    button.setAttribute('title', initialTitle);
                }
            }, 1200);
        }

        function fallbackCopy(text) {
            textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        }

        var text = button.dataset.copyText || '';
        if (!text) {
            return;
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(showCopiedState).catch(function () {
                fallbackCopy(text);
                showCopiedState();
            });
            return;
        }

        fallbackCopy(text);
        showCopiedState();
    });
})();
JS);
?>
<div class="zen-post-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить пост', ['/admin/zen-post/create', 'account_id' => $accountId], ['class' => 'btn btn-success']) ?>
        <?= Html::a('К аккаунтам', ['/admin/zen-account/index'], ['class' => 'btn btn-outline-secondary']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'id',
            [
                'attribute' => 'title',
                'contentOptions' => ['style' => 'max-width: 250px;'],
            ],
            [
                'attribute' => 'scenario',
                'label' => 'Тема (сценарий)',
                'format' => 'raw',
                'value' => function ($m) {
                    $scenario = (string) ($m->scenario ?? '');
                    $text = nl2br(Html::encode($scenario));
                    $icon = '<svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">'
                        . '<path d="M10 1.5H4A1.5 1.5 0 0 0 2.5 3v8A1.5 1.5 0 0 0 4 12.5h6A1.5 1.5 0 0 0 11.5 11V3A1.5 1.5 0 0 0 10 1.5Zm.5 9.5a.5.5 0 0 1-.5.5H4a.5.5 0 0 1-.5-.5V3a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 .5.5v8Z"/>'
                        . '<path d="M12 4.5a.5.5 0 0 1 .5.5V12A2.5 2.5 0 0 1 10 14.5H5a.5.5 0 0 1 0-1h5A1.5 1.5 0 0 0 11.5 12V5a.5.5 0 0 1 .5-.5Z"/>'
                        . '</svg>';
                    $button = Html::button($icon, [
                        'type' => 'button',
                        'class' => 'zen-post-scenario-copy js-copy-scenario',
                        'title' => 'Скопировать текст сценария',
                        'data-title' => 'Скопировать текст сценария',
                        'data-copy-text' => $scenario,
                        'aria-label' => 'Скопировать текст сценария',
                    ]);

                    return Html::tag('div', $button . $text, [
                        'class' => 'zen-post-scenario-cell',
                    ]);
                },
                'contentOptions' => ['style' => 'white-space: pre-wrap; min-width: 320px;'],
            ],
            [
                'label' => 'Опубликован',
                'format' => 'raw',
                'value' => function ($m) {
                    return Html::checkbox('is_posted', $m->status === \app\models\ZenPost::STATUS_POSTED, [
                        'class' => 'form-check-input js-post-status-toggle',
                        'aria-label' => 'Переключить статус публикации',
                        'data-url-posted' => Url::to(['/admin/zen-post/set-status', 'account_id' => $m->account_id, 'id' => $m->id, 'status' => \app\models\ZenPost::STATUS_POSTED]),
                        'data-url-pending' => Url::to(['/admin/zen-post/set-status', 'account_id' => $m->account_id, 'id' => $m->id, 'status' => \app\models\ZenPost::STATUS_PENDING]),
                    ]);
                },
                'contentOptions' => ['class' => 'text-center align-middle'],
                'headerOptions' => ['class' => 'text-center'],
            ],
            [
                'label' => 'Удалённая публикация',
                'format' => 'raw',
                'value' => function ($m) {
                    $attempt = $m->latestPublishAttempt;
                    $status = $attempt?->status ?? ZenPostPublishAttempt::STATUS_NEW;
                    $labels = ZenPostPublishAttempt::statusLabels();
                    $label = $labels[$status] ?? $status;
                    $classMap = [
                        ZenPostPublishAttempt::STATUS_NEW => 'secondary',
                        ZenPostPublishAttempt::STATUS_QUEUED => 'warning',
                        ZenPostPublishAttempt::STATUS_RUNNING => 'info',
                        ZenPostPublishAttempt::STATUS_SUCCESS => 'success',
                        ZenPostPublishAttempt::STATUS_ERROR => 'danger',
                    ];
                    $class = $classMap[$status] ?? 'secondary';
                    $badge = Html::tag('span', $label, ['class' => "badge bg-{$class}"]);
                    if ($attempt === null) {
                        return $badge;
                    }
                    $link = Html::a('Результат', ['/admin/zen-post/send-log', 'account_id' => $m->account_id, 'id' => $m->id], ['class' => 'ms-2']);
                    return $badge . $link;
                },
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{update}',
                'buttons' => [
                    'send' => function ($url, $model) {
                        $label = $model->latestPublishAttempt === null ? 'Отправить' : 'Отправить снова';
                        return Html::a($label, ['/admin/zen-post/send', 'account_id' => $model->account_id, 'id' => $model->id], [
                            'class' => 'btn btn-sm btn-primary',
                            'data-method' => 'post',
                        ]);
                    },
                ],
                'urlCreator' => function ($action, $model) {
                    if ($action === 'update') {
                        return ['/admin/zen-post/' . $action, 'account_id' => $model->account_id, 'id' => $model->id];
                    }
                    return '#';
                },
            ],
        ],
    ]) ?>
</div>
