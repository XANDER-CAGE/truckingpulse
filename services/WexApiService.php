<?php

namespace app\services;

use app\models\ApiLog;
use Yii;
use Exception;
use SoapClient;
use SoapFault;
use yii\base\Component;

/**
 * WexApiService
 * 
 * Сервис для работы с WEX/EFS Card Management API
 */
class WexApiService extends Component
{
    /**
     * @var string URL WSDL для тестовой среды 
     */
    private $testWsdlUrl = 'https://ws.partner.efsllc.com/axis2/services/CardManagementWS?wsdl';
    
    /**
     * @var string URL WSDL для продакшн среды
     */
    private $productionWsdlUrl = 'https://ws.efsllc.com/axis2/services/CardManagementWS?wsdl';
    
    /**
     * @var SoapClient SOAP-клиент
     */
    private $soapClient;
    
    /**
     * @var string Client ID из метода login
     */
    private $clientId;
    
    /**
     * @var string Имя пользователя для API
     */
    public $username;
    
    /**
     * @var string Пароль для API
     */
    public $password;
    
    /**
     * @var bool Флаг использования продакшн-среды
     */
    public $isProduction = false;

    /**
     * @var int Таймаут для соединения (в секундах)
     */
    public $connectionTimeout = 30;

    /**
     * @var bool Логировать ли запросы в БД
     */
    public $enableLogging = true;
    
    /**
     * Инициализирует компонент
     */
    public function init()
    {
        parent::init();
        
        // Использовать параметры из конфигурации, если не указаны явно
        if ($this->username === null) {
            $this->username = Yii::$app->params['wex']['username'] ?? null;
        }
        
        if ($this->password === null) {
            $this->password = Yii::$app->params['wex']['password'] ?? null;
        }
        
        if ($this->username === null || $this->password === null) {
            throw new Exception('WEX API username or password not configured');
        }

        $this->initSoapClient();
    }

    /**
     * Инициализирует SOAP-клиент
     */
    protected function initSoapClient()
    {
        $wsdlUrl = $this->isProduction 
            ? $this->productionWsdlUrl 
            : $this->testWsdlUrl;
        
        // Настройки SOAP-клиента
        $options = [
            'exceptions' => true,
            'trace' => true,
            'connection_timeout' => $this->connectionTimeout,
            'cache_wsdl' => WSDL_CACHE_NONE
        ];
        
        try {
            $this->soapClient = new SoapClient($wsdlUrl, $options);
        } catch (SoapFault $e) {
            Yii::error('Failed to initialize WEX SOAP client: ' . $e->getMessage(), 'wex-api');
            throw $e;
        }
    }
    
    /**
     * Авторизация в API
     * 
     * @return string Client ID для использования в других методах
     * @throws Exception
     */
    public function login()
    {
        try {
            $startTime = microtime(true);
            
            $params = [
                'user' => $this->username,
                'password' => $this->password
            ];
            
            // Вызов метода login
            $result = $this->soapClient->login($params);
            
            $duration = round((microtime(true) - $startTime) * 1000);
            
            // Логирование успешного запроса
            if ($this->enableLogging) {
                $this->logApiCall('login', $params, $result, 'success', null, $duration);
            }
            
            if (isset($result->clientId)) {
                $this->clientId = $result->clientId;
                return $this->clientId;
            }
            
            throw new Exception('Login failed: Invalid response format');
        } catch (SoapFault $e) {
            // Логирование ошибки
            if ($this->enableLogging) {
                $this->logApiCall('login', $params, null, 'error', $e->getMessage(), $duration ?? 0);
            }
            
            Yii::error('WEX API login failed: ' . $e->getMessage(), 'wex-api');
            throw $e;
        }
    }
    
    /**
     * Выход из системы (освобождение client ID)
     * 
     * @return bool
     */
    public function logout()
    {
        if (!$this->clientId) {
            return true;
        }
        
        try {
            $startTime = microtime(true);
            
            $params = [
                'clientId' => $this->clientId
            ];
            
            $this->soapClient->logout($params);
            
            $duration = round((microtime(true) - $startTime) * 1000);
            
            if ($this->enableLogging) {
                $this->logApiCall('logout', $params, null, 'success', null, $duration);
            }
            
            $this->clientId = null;
            return true;
        } catch (SoapFault $e) {
            if ($this->enableLogging) {
                $this->logApiCall('logout', $params ?? [], null, 'error', $e->getMessage(), $duration ?? 0);
            }
            
            Yii::error('WEX API logout failed: ' . $e->getMessage(), 'wex-api');
            return false;
        }
    }
    
    /**
     * Получение информации о клиентах (child carriers)
     * 
     * @return array
     */
    public function getChildCarriers()
    {
        return $this->callMethod('getChildCarriers', [
            'parentClientId' => $this->clientId
        ]);
    }
    
    /**
     * Создание клиента (child carrier)
     * 
     * @param array $carrierData Данные клиента
     * @return mixed
     */
    public function createChildCarrier($carrierData)
    {
        return $this->callMethod('createChildCarrier', [
            'parentClientId' => $this->clientId,
            'carrierData' => $carrierData
        ]);
    }
    
    /**
     * Получение информации о карте
     * 
     * @param string $cardNumber Номер карты
     * @return mixed
     */
    public function getCard($cardNumber)
    {
        return $this->callMethod('getCard', [
            'clientId' => $this->clientId,
            'cardNumber' => $cardNumber
        ]);
    }
    
    /**
     * Получение информации о карте по Driver ID
     * 
     * @param string $driverId ID водителя
     * @return mixed
     */
    public function getCardByDriverId($driverId)
    {
        return $this->callMethod('getCardByDriverId', [
            'clientId' => $this->clientId,
            'driverId' => $driverId
        ]);
    }
    
    /**
     * Обновление данных карты
     * 
     * @param string $cardNumber Номер карты
     * @param array $cardData Данные карты
     * @return mixed
     */
    public function setCard($cardNumber, $cardData)
    {
        // Добавляем обязательные параметры
        $cardData['cardNumber'] = $cardNumber;
        
        return $this->callMethod('setCard', array_merge([
            'clientId' => $this->clientId
        ], $cardData));
    }
    
    /**
     * Создание и отправка заказа карт
     * 
     * @param array $orderData Данные заказа
     * @return mixed
     */
    public function createAndSubmitOrder($orderData)
    {
        return $this->callMethod('createAndSubmitOrder', array_merge([
            'clientId' => $this->clientId
        ], $orderData));
    }
    
    /**
     * Получение транзакций
     * 
     * @param string $begDate Начальная дата в формате ISO (yyyy-mm-ddThh:mm:ss)
     * @param string $endDate Конечная дата в формате ISO (yyyy-mm-ddThh:mm:ss)
     * @return mixed
     */
    public function getTransactions($begDate, $endDate)
    {
        return $this->callMethod('getTransactionsExt', [
            'clientId' => $this->clientId,
            'begDate' => $begDate,
            'endDate' => $endDate
        ]);
    }
    
    /**
     * Получение дочерних транзакций
     * 
     * @param string $begDate Начальная дата в формате ISO (yyyy-mm-ddThh:mm:ss)
     * @param string $endDate Конечная дата в формате ISO (yyyy-mm-ddThh:mm:ss)
     * @return mixed
     */
    public function getChildTransactions($begDate, $endDate)
    {
        return $this->callMethod('getChildTransactionsNew', [
            'parentClientId' => $this->clientId,
            'begDate' => $begDate,
            'endDate' => $endDate
        ]);
    }
    
    /**
     * Получение информации о скидках для дочерних клиентов
     * 
     * @param string $lookupValue ID дочернего клиента или "ALL" для всех
     * @return mixed
     */
    public function getDiscountData($lookupValue = 'ALL')
    {
        return $this->callMethod('pullCarrierGetsDiscountData', [
            'clientId' => $this->clientId,
            'lookupValue' => $lookupValue
        ]);
    }
    
    /**
     * Установка скидки для дочернего клиента
     * 
     * @param int $carrierId ID дочернего клиента
     * @param string $getsDiscount "Y" - получает скидку, "N" - не получает
     * @return mixed
     */
    public function setCarrierDiscount($carrierId, $getsDiscount = 'Y')
    {
        return $this->callMethod('setCarrierDiscount', [
            'clientId' => $this->clientId,
            'carrierDiscount' => [
                'carrier' => $carrierId,
                'getsDiscount' => $getsDiscount
            ]
        ]);
    }
    
    /**
     * Универсальный метод для вызова API
     * 
     * @param string $method Имя метода
     * @param array $params Параметры
     * @param bool $autoLogin Автоматически выполнять логин при ошибке авторизации
     * @return mixed
     * @throws Exception
     */
    public function callMethod($method, $params, $autoLogin = true)
    {
        // Проверка наличия clientId, если метод не login
        if ($method !== 'login' && !isset($this->clientId) && $autoLogin) {
            $this->login();
        }
        
        try {
            $startTime = microtime(true);
            
            $result = $this->soapClient->$method($params);
            
            $duration = round((microtime(true) - $startTime) * 1000);
            
            if ($this->enableLogging) {
                $this->logApiCall($method, $params, $result, 'success', null, $duration);
            }
            
            return $result;
        } catch (SoapFault $e) {
            $duration = isset($startTime) ? round((microtime(true) - $startTime) * 1000) : 0;
            
            if ($this->enableLogging) {
                $this->logApiCall($method, $params, null, 'error', $e->getMessage(), $duration);
            }
            
            // Если ошибка связана с авторизацией и установлен флаг автологина
            if (stripos($e->getMessage(), 'InvalidClientId') !== false && $autoLogin && $method !== 'login') {
                Yii::warning("WEX API session expired, trying to re-login", 'wex-api');
                $this->login();
                // Повторный вызов метода без автологина
                return $this->callMethod($method, $params, false);
            }
            
            Yii::error("WEX API method {$method} failed: " . $e->getMessage(), 'wex-api');
            throw $e;
        }
    }
    
    /**
     * Логирование вызова API
     * 
     * @param string $method Имя метода
     * @param array $request Параметры запроса
     * @param mixed $response Ответ
     * @param string $status Статус выполнения
     * @param string|null $error Сообщение об ошибке
     * @param int $duration Длительность выполнения в мс
     */
    protected function logApiCall($method, $request, $response, $status, $error = null, $duration = 0)
    {
        // Не логируем пароль
        if (isset($request['password'])) {
            $request['password'] = '********';
        }
        
        $log = new ApiLog();
        $log->method = $method;
        $log->request = json_encode($request);
        $log->response = $response ? json_encode($response) : null;
        $log->status = $status;
        $log->error = $error;
        $log->duration = $duration;
        $log->created_at = time();
        
        $log->save();
    }
}