<?php

namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;
use Yii;

class WexSyncController extends Controller
{
    /**
     * Синхронизация дочерних перевозчиков
     * 
     * @return int
     */
    public function actionSyncCarriers()
    {
        $syncService = Yii::$app->get('wexSyncService');
        $stats = $syncService->syncChildCarriers();

        $this->stdout("Синхронизация перевозчиков:\n");
        $this->stdout("Всего обработано: {$stats['total']}\n");
        $this->stdout("Создано: {$stats['created']}\n");
        $this->stdout("Обновлено: {$stats['updated']}\n");
        $this->stdout("Длительность: {$stats['duration']} сек.\n");

        if (!empty($stats['errors'])) {
            $this->stderr("Ошибки:\n");
            foreach ($stats['errors'] as $error) {
                $this->stderr(print_r($error, true) . "\n");
            }
        }

        return ExitCode::OK;
    }

    /**
     * Синхронизация транзакций
     * 
     * @param int $minutes Количество минут для импорта
     * @return int
     */
    public function actionSyncTransactions($minutes = 60)
    {
        $syncService = Yii::$app->get('wexSyncService');
        $stats = $syncService->syncTransactions($minutes);

        $this->stdout("Синхронизация транзакций:\n");
        $this->stdout("Всего обработано: {$stats['total']}\n");
        $this->stdout("Импортировано: {$stats['imported']}\n");
        $this->stdout("Пропущено: {$stats['skipped']}\n");
        $this->stdout("Длительность: {$stats['duration']} сек.\n");

        if (!empty($stats['errors'])) {
            $this->stderr("Ошибки:\n");
            foreach ($stats['errors'] as $error) {
                $this->stderr(print_r($error, true) . "\n");
            }
        }

        return ExitCode::OK;
    }
}