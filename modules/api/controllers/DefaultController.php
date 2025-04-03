<?php

namespace app\modules\api\controllers;

use yii\rest\Controller;
use yii\web\UnauthorizedHttpException;
use app\modules\api\models\LoginForm;

class DefaultController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        // Отключаем аутентификацию для логина
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
            'except' => ['login', 'options']
        ];
        
        // Поддержка CORS
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
        ];
        
        return $behaviors;
    }

    public function actionLogin()
    {
        $model = new LoginForm();
        
        if ($model->load(\Yii::$app->request->post(), '') && $model->login()) {
            $user = $model->getUser();
            return [
                'token' => $user->auth_key,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'role' => $user->role,
                ]
            ];
        } else {
            throw new UnauthorizedHttpException('Invalid username or password');
        }
    }
    
    public function actionLogout()
    {
        \Yii::$app->user->logout();
        return ['success' => true];
    }
}