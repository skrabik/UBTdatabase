<?php
/** @var yii\web\View $this */
/** @var app\models\User $model */
/** @var array $allRoles */
/** @var string[] $userRoleNames */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Новый пользователь';
$this->params['breadcrumbs'][] = ['label' => 'Пользователи и роли', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-user-create">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'username')->textInput(['maxlength' => true, 'autofocus' => true]) ?>
    <?= $form->field($model, 'password')->passwordInput(['minlength' => 3]) ?>

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

    <div class="form-group">
        <?= Html::submitButton('Создать', ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Отмена', ['index'], ['class' => 'btn btn-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
