<?php

namespace app\modules\api\controllers;

use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;

class BaseController extends ActiveController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        // Аутентификация через Bearer токен
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => ['options']
        ];
        
        // Поддержка CORS
        $behaviors['corsFilter'] = [
            'class' => Cors::class,
        ];
        
        return $behaviors;
    }
}