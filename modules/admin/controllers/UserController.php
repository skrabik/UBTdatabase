<?php

namespace app\modules\admin\controllers;

use app\models\User;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

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

    public function actionCreate(): string|Response
    {
        $model = new User();
        $model->setScenario('create');
        $auth = Yii::$app->authManager;
        $allRoles = $auth->getRoles();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $newRole = Yii::$app->request->post('role');
            if ($newRole !== null && isset($allRoles[$newRole])) {
                $auth->assign($allRoles[$newRole], (string) $model->id);
            }
            Yii::$app->session->setFlash('success', 'Пользователь создан.');
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
            'allRoles' => $allRoles,
            'userRoleNames' => [],
        ]);
    }

    public function actionUpdate(int $id): string|Response
    {
        $model = $this->findModel($id);
        $auth = Yii::$app->authManager;
        $allRoles = $auth->getRoles();
        $assignments = $auth->getAssignments((string) $id);
        $userRoleNames = array_keys($assignments);

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            $newRole = Yii::$app->request->post('role');
            if ($newRole !== null && isset($allRoles[$newRole])) {
                $auth->revokeAll($id);
                $auth->assign($allRoles[$newRole], (string) $id);
            }
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Пользователь сохранён.');
                return $this->redirect(['index']);
            }
        }

        return $this->render('update', [
            'model' => $model,
            'allRoles' => $allRoles,
            'userRoleNames' => $userRoleNames,
        ]);
    }

    public function actionDelete(int $id): Response
    {
        $this->request->validateCsrfToken();
        $model = $this->findModel($id);
        $currentId = (string) Yii::$app->user->id;
        if ($currentId === (string) $id) {
            Yii::$app->session->setFlash('error', 'Нельзя удалить самого себя.');
            return $this->redirect(['index']);
        }
        Yii::$app->authManager->revokeAll($id);
        $model->delete();
        Yii::$app->session->setFlash('success', 'Пользователь удалён.');
        return $this->redirect(['index']);
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
