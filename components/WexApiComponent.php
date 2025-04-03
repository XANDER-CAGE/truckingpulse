<?php

namespace app\components;

use app\services\WexApiService;
use yii\base\Component;

/**
 * Компонент для работы с WEX API
 */
class WexApiComponent extends Component
{
    /**
     * @var string Имя пользователя для API
     */
    public $username;
    
    /**
     * @var string Пароль для API
     */
    public $password;
    
    /**
     * @var bool Использовать ли продакшн-среду
     */
    public $isProduction = false;
    
    /**
     * @var bool Логировать ли запросы в БД
     */
    public $enableLogging = true;
    
    /**
     * @var int Таймаут для соединения (в секундах)
     */
    public $connectionTimeout = 30;
    
    /**
     * @var WexApiService Сервис для работы с API
     */
    private $_service;
    
    /**
     * Инициализирует компонент
     */
    public function init()
    {
        parent::init();
        
        $this->_service = new WexApiService([
            'username' => $this->username,
            'password' => $this->password,
            'isProduction' => $this->isProduction,
            'enableLogging' => $this->enableLogging,
            'connectionTimeout' => $this->connectionTimeout,
        ]);
    }
    
    /**
     * PHP magic method для вызова методов сервиса WexApiService
     * 
     * @param string $method Имя метода
     * @param array $params Параметры
     * @return mixed
     */
    public function __call($method, $params)
    {
        if (method_exists($this->_service, $method)) {
            return call_user_func_array([$this->_service, $method], $params);
        }
        
        return parent::__call($method, $params);
    }
}