<?php
/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var int|null $accountId */

use yii\bootstrap5\Html;
use yii\grid\GridView;
use yii\helpers\HtmlPurifier;
use yii\helpers\Url;

$this->title = $accountId ? 'Посты аккаунта' : 'Все посты';
$this->params['breadcrumbs'][] = ['label' => 'Аккаунты Яндекс.Дзен', 'url' => ['/admin/zen-account/index']];
if ($accountId) {
    $account = \app\models\ZenAccount::findOne($accountId);
    $this->params['breadcrumbs'][] = $account ? $account->name : (string) $accountId;
}
$this->params['breadcrumbs'][] = $this->title;

$this->registerCss(<<<CSS
.zen-post-index .table > tbody > tr > td {
    transition: background-color 0.12s linear;
}

.zen-post-index .table > tbody > tr:hover > td {
    background-color: #e1efff;
}

.zen-post-index .table > tbody > tr:hover > td:hover {
    background-color: #cfe4ff;
}

.zen-post-scenario-cell {
    position: relative;
    padding-right: 34px;
}

.zen-post-clamp-cell {
    position: relative;
    padding-right: 34px;
}

.zen-post-title-cell {
    display: -webkit-box;
    max-width: 250px;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: var(--zen-line-clamp, 2);
    line-height: 1.35;
    max-height: calc(var(--zen-line-clamp, 2) * 1.35em);
    word-break: break-word;
}

.zen-post-content-cell {
    max-width: 360px;
    overflow: hidden;
    line-height: 1.35;
    max-height: calc(var(--zen-line-clamp, 2) * 1.35em);
    word-break: break-word;
}

.zen-post-content-cell > *:first-child {
    margin-top: 0;
}

.zen-post-content-cell > *:last-child {
    margin-bottom: 0;
}

.zen-post-content-cell p,
.zen-post-content-cell ul,
.zen-post-content-cell ol,
.zen-post-content-cell blockquote {
    margin-bottom: 0.5em;
}

.zen-post-content-cell ul,
.zen-post-content-cell ol {
    padding-left: 1.25rem;
}

.zen-post-content-cell strong {
    font-weight: 700;
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
    function syncClampedCells() {
        document.querySelectorAll('tr.js-zen-post-row').forEach(function (row) {
            var scenarioCell = row.querySelector('.zen-post-cell-scenario .zen-post-scenario-cell');
            if (!scenarioCell) {
                return;
            }

            var availableHeight = scenarioCell.getBoundingClientRect().height;
            if (!availableHeight) {
                return;
            }

            row.querySelectorAll('.zen-post-clamp-text').forEach(function (textNode) {
                textNode.style.removeProperty('--zen-line-clamp');
                textNode.style.removeProperty('max-height');

                var lineHeight = parseFloat(window.getComputedStyle(textNode).lineHeight);
                if (!lineHeight || !isFinite(lineHeight)) {
                    lineHeight = 19;
                }

                var lines = Math.max(1, Math.floor(availableHeight / lineHeight));
                textNode.style.setProperty('--zen-line-clamp', String(lines));
                textNode.style.maxHeight = (lines * lineHeight) + 'px';
            });
        });
    }

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

    syncClampedCells();
    window.addEventListener('load', syncClampedCells);
    window.addEventListener('resize', syncClampedCells);

})();
JS);

$copyIcon = '<svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">'
    . '<path d="M10 1.5H4A1.5 1.5 0 0 0 2.5 3v8A1.5 1.5 0 0 0 4 12.5h6A1.5 1.5 0 0 0 11.5 11V3A1.5 1.5 0 0 0 10 1.5Zm.5 9.5a.5.5 0 0 1-.5.5H4a.5.5 0 0 1-.5-.5V3a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 .5.5v8Z"/>'
    . '<path d="M12 4.5a.5.5 0 0 1 .5.5V12A2.5 2.5 0 0 1 10 14.5H5a.5.5 0 0 1 0-1h5A1.5 1.5 0 0 0 11.5 12V5a.5.5 0 0 1 .5-.5Z"/>'
    . '</svg>';
?>
<div class="zen-post-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить пост', ['/admin/zen-post/create', 'account_id' => $accountId], ['class' => 'btn btn-success']) ?>
        <?= Html::a('К аккаунтам', ['/admin/zen-account/index'], ['class' => 'btn btn-outline-secondary']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'rowOptions' => function ($model) {
            return [
                'class' => 'js-zen-post-row',
                'data-id' => $model->id,
            ];
        },
        'columns' => [
            'id',
            [
                'attribute' => 'title',
                'format' => 'raw',
                'value' => function ($m) use ($copyIcon) {
                    $title = (string) ($m->title ?? '');
                    $button = Html::button($copyIcon, [
                        'type' => 'button',
                        'class' => 'zen-post-scenario-copy js-copy-scenario',
                        'title' => 'Скопировать заголовок',
                        'data-title' => 'Скопировать заголовок',
                        'data-copy-text' => $title,
                        'aria-label' => 'Скопировать заголовок',
                    ]);

                    return Html::tag('div', $button . Html::tag('span', Html::encode($title), [
                        'class' => 'zen-post-title-cell zen-post-clamp-text',
                        'title' => $title,
                    ]), [
                        'class' => 'zen-post-clamp-cell',
                    ]);
                },
                'contentOptions' => ['class' => 'zen-post-cell-title', 'style' => 'max-width: 250px;'],
            ],
            [
                'attribute' => 'scenario',
                'label' => 'Тема (сценарий)',
                'format' => 'raw',
                'value' => function ($m) use ($copyIcon) {
                    $scenario = (string) ($m->scenario ?? '');
                    $text = nl2br(Html::encode($scenario));
                    $button = Html::button($copyIcon, [
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
                'contentOptions' => ['class' => 'zen-post-cell-scenario', 'style' => 'white-space: pre-wrap; min-width: 320px;'],
            ],
            [
                'attribute' => 'content',
                'label' => 'Текст',
                'format' => 'raw',
                'value' => function ($m) use ($copyIcon) {
                    $content = (string) ($m->content ?? '');
                    $plainContent = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $content))));
                    $renderedContent = $content === '' ? '' : HtmlPurifier::process($content);
                    $button = Html::button($copyIcon, [
                        'type' => 'button',
                        'class' => 'zen-post-scenario-copy js-copy-scenario',
                        'title' => 'Скопировать текст статьи',
                        'data-title' => 'Скопировать текст статьи',
                        'data-copy-text' => $plainContent,
                        'aria-label' => 'Скопировать текст статьи',
                    ]);

                    return Html::tag('div', $button . Html::tag('div', $renderedContent, [
                        'class' => 'zen-post-content-cell zen-post-clamp-text',
                        'title' => $plainContent,
                    ]), [
                        'class' => 'zen-post-clamp-cell',
                    ]);
                },
                'contentOptions' => ['class' => 'zen-post-cell-content', 'style' => 'max-width: 360px;'],
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{run-workflow} {update}',
                'buttons' => [
                    'run-workflow' => function ($url, $model) {
                        return Html::a('Запустить workflow', ['/admin/zen-post/run-workflow', 'account_id' => $model->account_id, 'id' => $model->id], [
                            'class' => 'btn btn-sm btn-outline-primary me-1',
                            'data-method' => 'post',
                            'data-confirm-title' => 'Запустить workflow',
                            'data-confirm-modal' => 'Запустить workflow для этого поста? Будут отправлены scenario, post_id и channel_id.',
                        ]);
                    },
                ],
                'urlCreator' => function ($action, $model) {
                    if ($action === 'update' || $action === 'run-workflow') {
                        return ['/admin/zen-post/' . $action, 'account_id' => $model->account_id, 'id' => $model->id];
                    }
                    return '#';
                },
            ],
        ],
    ]) ?>
</div>
