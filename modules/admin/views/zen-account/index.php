<?php
/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array<int, string> $ownerOptions */
/** @var int|null $selectedOwnerId */

use app\models\ZenAccount;
use yii\bootstrap5\Html;
use yii\grid\GridView;
use yii\helpers\Url;

$this->title = 'Аккаунты Яндекс.Дзен';
$this->params['breadcrumbs'][] = $this->title;

$channelUrl = static function (?string $url): ?string {
    $url = trim((string) $url);
    if ($url === '') {
        return null;
    }

    if (!preg_match('~^https?://~i', $url)) {
        $url = 'https://' . ltrim($url, '/');
    }

    return $url;
};

$this->registerCss(<<<CSS
.zen-account-index .table > tbody > tr > td {
    transition: background-color 0.12s linear;
}

.zen-account-index .table > tbody > tr:hover > td {
    background-color: var(--admin-hover, #e1efff);
}

.zen-account-index .table > tbody > tr:hover > td:hover {
    background-color: var(--admin-hover-strong, #cfe4ff);
}

.zen-account-filter {
    display: flex;
    gap: 12px;
    align-items: end;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.zen-account-filter-field {
    min-width: 260px;
}
CSS);
?>
<div class="zen-account-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить аккаунт', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <form method="get" class="zen-account-filter">
        <div class="zen-account-filter-field">
            <?= Html::label('Владелец', 'zen-account-owner-filter', ['class' => 'form-label']) ?>
            <?= Html::dropDownList('owner_id', $selectedOwnerId, $ownerOptions, [
                'id' => 'zen-account-owner-filter',
                'class' => 'form-select',
                'prompt' => 'Все владельцы',
            ]) ?>
        </div>
        <div>
            <?= Html::submitButton('Фильтровать', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Сбросить', Url::to(['index']), ['class' => 'btn btn-outline-secondary']) ?>
        </div>
    </form>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'rowOptions' => function ($model) {
            return [
                'class' => 'js-zen-account-row',
                'data-id' => $model->id,
            ];
        },
        'columns' => [
            'id',
            'name',
            'slug',
            [
                'attribute' => 'description',
                'value' => fn ($m) => $m->description ? (mb_strlen($m->description) > 50 ? mb_substr($m->description, 0, 50) . '…' : $m->description) : '—',
            ],
            [
                'attribute' => 'url',
                'format' => 'raw',
                'value' => static function ($m) use ($channelUrl) {
                    $href = $channelUrl($m->url);

                    return $href
                        ? Html::a($m->url, $href, [
                            'target' => '_blank',
                            'rel' => 'noopener noreferrer',
                            'title' => $m->url,
                        ])
                        : '—';
                },
                'contentOptions' => ['style' => 'max-width: 200px; overflow: hidden; text-overflow: ellipsis;'],
            ],
            [
                'attribute' => 'owner_id',
                'label' => 'Владелец',
                'value' => static fn ($m) => $m->owner?->username ?: '—',
            ],
            [
                'attribute' => 'themeIds',
                'value' => fn ($m) => implode(', ', array_column($m->themeRelations, 'name')) ?: '—',
                'label' => 'Тематики',
            ],
            [
                'attribute' => 'login_type',
                'value' => fn ($m) => ZenAccount::loginTypeLabels()[$m->login_type] ?? $m->login_type,
                'label' => 'Тип входа',
            ],
            [
                'attribute' => 'login',
                'value' => fn ($m) => $m->login ? (mb_strlen($m->login) > 30 ? mb_substr($m->login, 0, 30) . '…' : $m->login) : '—',
            ],
            [
                'attribute' => 'proxy_ip',
                'value' => fn ($m) => $m->proxy_ip ?: '—',
            ],
            [
                'attribute' => 'workflow_id',
                'value' => fn ($m) => $m->workflow_id ?: '—',
                'contentOptions' => ['style' => 'max-width: 220px; overflow: hidden; text-overflow: ellipsis;'],
            ],
            [
                'attribute' => 'created_at',
                'format' => ['date', 'php:d.m.Y H:i'],
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{posts} {update} {delete}',
                'buttons' => [
                    'posts' => function ($url, $model) {
                        return Html::a('Посты', ['/admin/zen-post/index', 'account_id' => $model->id], ['class' => 'btn btn-sm btn-outline-primary']);
                    },
                    'update' => function ($url, $model) {
                        return Html::a('Редактировать', ['update', 'id' => $model->id], ['class' => 'btn btn-sm btn-outline-secondary']);
                    },
                    'delete' => function ($url, $model, $key) {
                        return Html::a('Удалить', ['delete', 'id' => $model->id], [
                            'title' => 'Удалить',
                            'data-confirm-modal' => 'Удалить этот аккаунт и все его посты?',
                            'data-confirm-title' => 'Удалить аккаунт',
                            'data-method' => 'post',
                            'data-pjax' => '0',
                        ]);
                    },
                ],
                'urlCreator' => function ($action, $model) {
                    if ($action === 'update') return ['update', 'id' => $model->id];
                    if ($action === 'delete') return ['delete', 'id' => $model->id];
                    return '#';
                },
            ],
        ],
    ]) ?>
</div>
