<?php

namespace app\components;

use Yii;
use yii\base\Component;
use yii\base\Security;
use app\models\LoginAttempt;

class BruteforceProtection extends Component
{
    /**
     * Максимальное количество попыток входа
     */
    public $maxAttempts = 5;

    /**
     * Время блокировки в секундах (1 час)
     */
    public $blockDuration = 3600;

    /**
     * Проверка возможности входа
     * 
     * @param string $username Имя пользователя
     * @return bool
     */
    public function canLogin($username)
    {
        // Очищаем старые попытки
        $this->cleanupOldAttempts();

        // Получаем количество попыток за последний час
        $attempts = LoginAttempt::find()
            ->where([
                'username' => $username,
                'status' => 'failed'
            ])
            ->andWhere(['>', 'created_at', time() - $this->blockDuration])
            ->count();

        // Проверяем блокировку
        return $attempts < $this->maxAttempts;
    }

    /**
     * Регистрация неудачной попытки входа
     * 
     * @param string $username Имя пользователя
     * @param string $ip IP-адрес
     */
    public function registerFailedAttempt($username, $ip)
    {
        $attempt = new LoginAttempt();
        $attempt->username = $username;
        $attempt->ip = $ip;
        $attempt->status = 'failed';
        $attempt->created_at = time();
        $attempt->save();
    }

    /**
     * Очистка старых попыток входа
     */
    private function cleanupOldAttempts()
    {
        LoginAttempt::deleteAll([
            '<', 'created_at', time() - $this->blockDuration
        ]);
    }

    /**
     * Получение времени до разблокировки
     * 
     * @param string $username Имя пользователя
     * @return int Время до разблокировки в секундах
     */
    public function getBlockTime($username)
    {
        $lastAttempt = LoginAttempt::find()
            ->where([
                'username' => $username,
                'status' => 'failed'
            ])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();

        if ($lastAttempt) {
            $timeLeft = $this->blockDuration - (time() - $lastAttempt->created_at);
            return max(0, $timeLeft);
        }

        return 0;
    }
}