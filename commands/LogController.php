<?php

namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;
use app\models\ApiLog;

class LogController extends Controller
{
    /**
     * Очистка устаревших API-логов
     * 
     * @param int $days Количество дней хранения логов
     * @return int
     */
    public function actionCleanApiLogs($days = 30)
    {
        $timestamp = strtotime("-{$days} days");
        
        $count = ApiLog::deleteAll(['<', 'created_at', $timestamp]);
        
        $this->stdout("Удалено старых API-логов: {$count}\n");
        
        return ExitCode::OK;
    }
}