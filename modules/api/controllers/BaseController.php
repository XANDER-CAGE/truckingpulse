<?php

namespace app\modules\api\controllers;

use app\traits\ApiLoggerTrait;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use Yii;

class BaseController extends ActiveController
{
    use ApiLoggerTrait;

    /**
     * Время начала запроса
     * @var float 
     */
    private $requestStartTime;

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

    /**
     * Перехват перед выполнением действия
     */
    public function beforeAction($action)
    {
        $this->requestStartTime = microtime(true);
        return parent::beforeAction($action);
    }

    /**
     * Перехват после выполнения действия
     */
    public function afterAction($action, $result)
    {
        // Логируем API-запрос
        $this->logApiRequest(
            Yii::$app->request, 
            Yii::$app->response, 
            $this->requestStartTime
        );

        return parent::afterAction($action, $result);
    }
}