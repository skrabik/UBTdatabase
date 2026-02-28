<?php
/** @var yii\web\View $this */

use yii\bootstrap5\Html;

$this->title = 'Админ-панель';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-default-index">
    <h1><?= Html::encode($this->title) ?></h1>
    <p class="lead">Добро пожаловать в панель управления.</p>
    <ul>
        <li><?= Html::a('Аккаунты Яндекс.Дзен', ['/admin/zen-account/index']) ?></li>
        <li><?= Html::a('Пользователи и роли', ['/admin/user/index']) ?></li>
    </ul>
</div>
