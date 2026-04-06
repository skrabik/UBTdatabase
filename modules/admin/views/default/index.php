<?php
/** @var yii\web\View $this */

use yii\bootstrap5\Html;

$this->title = 'Админ-панель';
$this->params['breadcrumbs'][] = $this->title;

$sections = [
    [
        'number' => '01',
        'title' => 'Аккаунты Яндекс.Дзен',
        'description' => 'Управление аккаунтами, доступами и привязанными публикациями.',
        'url' => ['/admin/zen-account/index'],
    ],
    [
        'number' => '02',
        'title' => 'Тематики каналов',
        'description' => 'Категории, структура и быстрый переход к настройкам контента.',
        'url' => ['/admin/theme/index'],
    ],
    [
        'number' => '03',
        'title' => 'Пользователи и роли',
        'description' => 'Доступы, роли пользователей и управление правами внутри панели.',
        'url' => ['/admin/user/index'],
    ],
];
?>
<div class="admin-default-index">
    <div class="admin-dashboard-header mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <p class="lead mb-0">Добро пожаловать в панель управления. Выберите нужный раздел ниже.</p>
    </div>

    <div class="row g-4 admin-dashboard-grid">
        <?php foreach ($sections as $section): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <?= Html::a(
                    Html::tag('span', Html::encode($section['number']), ['class' => 'admin-dashboard-card__number'])
                    . Html::tag(
                        'div',
                        Html::tag('h2', Html::encode($section['title']), ['class' => 'admin-dashboard-card__title'])
                        . Html::tag('p', Html::encode($section['description']), ['class' => 'admin-dashboard-card__description'])
                        . Html::tag('span', 'Открыть раздел', ['class' => 'admin-dashboard-card__action']),
                        ['class' => 'admin-dashboard-card__content']
                    ),
                    $section['url'],
                    ['class' => 'admin-dashboard-card']
                ) ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
