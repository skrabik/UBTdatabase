<?php

namespace app\modules\admin\controllers;

use app\models\User;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class UserController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['admin'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => User::find()->orderBy(['id' => SORT_ASC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionUpdate(int $id): string|\yii\web\Response
    {
        $model = $this->findModel($id);
        $auth = Yii::$app->authManager;
        $allRoles = $auth->getRoles();
        $assignments = $auth->getAssignments((string) $id);
        $userRoleNames = array_keys($assignments);

        if (Yii::$app->request->isPost) {
            $newRole = Yii::$app->request->post('role');
            if ($newRole !== null && isset($allRoles[$newRole])) {
                $auth->revokeAll($id);
                $auth->assign($allRoles[$newRole], (string) $id);
                Yii::$app->session->setFlash('success', 'Роль пользователя обновлена.');
                return $this->redirect(['index']);
            }
        }

        return $this->render('update', [
            'model' => $model,
            'allRoles' => $allRoles,
            'userRoleNames' => $userRoleNames,
        ]);
    }

    protected function findModel(int $id): User
    {
        $model = User::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Пользователь не найден.');
        }
        return $model;
    }
}
