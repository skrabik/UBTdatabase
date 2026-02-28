<?php
/** @var yii\web\View $this */
/** @var app\models\User $model */
/** @var array $allRoles */
/** @var string[] $userRoleNames */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Роль пользователя: ' . $model->username;
$this->params['breadcrumbs'][] = ['label' => 'Пользователи и роли', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->username;
?>
<div class="admin-user-update">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin(); ?>
    <div class="mb-3">
        <label class="form-label">Роль</label>
        <select name="role" class="form-select">
            <?php foreach ($allRoles as $name => $role): ?>
                <option value="<?= Html::encode($name) ?>" <?= in_array($name, $userRoleNames, true) ? 'selected' : '' ?>>
                    <?= Html::encode($role->description ?: $name) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
    <?= Html::a('Отмена', ['index'], ['class' => 'btn btn-secondary']) ?>
    <?php ActiveForm::end(); ?>
</div>
