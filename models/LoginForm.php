<?php

namespace app\models;

use Yii;
use yii\base\Model;

class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;

    private $_user = false;

    public function rules()
    {
        return [
            [['username', 'password'], 'required'],
            ['rememberMe', 'boolean'],
            ['password', 'validatePassword'],
        ];
    }

    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            // Проверка защиты от брутфорс
            $bruteforceProtection = Yii::$app->bruteforceProtection;
            $clientIp = Yii::$app->request->userIP;

            if (!$bruteforceProtection->canLogin($this->username)) {
                $blockTime = $bruteforceProtection->getBlockTime($this->username);
                $minutes = ceil($blockTime / 60);
                $this->addError('username', "Слишком много неудачных попыток. Повторите через {$minutes} минут.");
                return false;
            }

            $user = $this->getUser();

            if (!$user || !$user->validatePassword($this->password)) {
                // Регистрация неудачной попытки
                $bruteforceProtection->registerFailedAttempt($this->username, $clientIp);
                $this->addError($attribute, 'Неправильное имя пользователя или пароль.');
                return false;
            }
        }
    }

    public function login()
    {
        if ($this->validate()) {
            return Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600*24*30 : 0);
        }
        return false;
    }

    public function getUser()
    {
        if ($this->_user === false) {
            $this->_user = User::findByUsername($this->username);
        }

        return $this->_user;
    }
}