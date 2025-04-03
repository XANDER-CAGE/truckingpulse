<?php

namespace app\modules\api\controllers;

use app\models\Client;

class ClientController extends BaseController
{
    public $modelClass = 'app\models\Client';
    
    public function actions()
    {
        $actions = parent::actions();
        
        // Настраиваем дополнительные действия или переопределяем существующие
        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];
        
        return $actions;
    }
    
    public function prepareDataProvider()
    {
        $searchModel = new \app\models\search\ClientSearch();
        return $searchModel->search(\Yii::$app->request->queryParams);
    }
}