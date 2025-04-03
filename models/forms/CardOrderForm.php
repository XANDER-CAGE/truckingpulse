<?php

namespace app\models\forms;

use app\models\Client;
use app\models\Policy;
use Yii;
use yii\base\Model;

/**
 * Форма для заказа карт
 */
class CardOrderForm extends Model
{
    /**
     * Типы заказов
     */
    const ORDER_TYPE_NEW = 1;
    const ORDER_TYPE_REPLACEMENT = 2;
    const ORDER_TYPE_RENEW = 3;
    
    /**
     * @var int ID клиента в нашей системе
     */
    public $clientId;
    
    /**
     * @var int ID политики в нашей системе
     */
    public $policyId;
    
    /**
     * @var int Номер политики в WEX
     */
    public $policyNumber;
    
    /**
     * @var int Тип заказа
     */
    public $orderType = self::ORDER_TYPE_NEW;
    
    /**
     * @var int Стиль карты
     */
    public $cardStyle = 50; // EFS PRIMARY (Silver) по умолчанию
    
    /**
     * @var int Количество карт
     */
    public $quantity = 1;
    
    /**
     * @var string Имя на карте
     */
    public $embossedName;
    
    /**
     * @var string Имя получателя
     */
    public $shipToFirst;
    
    /**
     * @var string Фамилия получателя
     */
    public $shipToLast;
    
    /**
     * @var string Адрес доставки (строка 1)
     */
    public $shipToAddress1;
    
    /**
     * @var string Адрес доставки (строка 2)
     */
    public $shipToAddress2;
    
    /**
     * @var string Город доставки
     */
    public $shipToCity;
    
    /**
     * @var string Штат доставки
     */
    public $shipToState;
    
    /**
     * @var string Индекс доставки
     */
    public $shipToZip;
    
    /**
     * @var string Страна доставки
     */
    public $shipToCountry = 'USA';
    
    /**
     * @var int Метод доставки
     */
    public $shippingMethod = 1; // Standard Shipping по умолчанию
    
    /**
     * @var bool Срочная обработка
     */
    public $rushProcessing = false;
    
    /**
     * @var bool Отправлять с носителем карты
     */
    public $cardCarrier = true;
    
    /**
     * @var array Свойства заказа
     */
    public $properties = [];
    
    /**
     * @var array Свойства карт
     */
    public $cardProperties = [];

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['clientId', 'policyId', 'policyNumber', 'embossedName', 'shipToFirst', 'shipToLast', 'shipToAddress1', 'shipToCity', 'shipToState', 'shipToZip'], 'required'],
            [['clientId', 'policyId', 'policyNumber', 'orderType', 'cardStyle', 'quantity', 'shippingMethod'], 'integer'],
            [['rushProcessing', 'cardCarrier'], 'boolean'],
            [['embossedName'], 'string', 'max' => 25],
            [['shipToFirst', 'shipToLast', 'shipToAddress1', 'shipToAddress2', 'shipToCity'], 'string', 'max' => 30],
            [['shipToState'], 'string', 'max' => 2],
            [['shipToZip'], 'string', 'max' => 10],
            [['shipToCountry'], 'string', 'max' => 3],
            [['shipToCountry'], 'in', 'range' => ['USA', 'CAN']],
            [['orderType'], 'in', 'range' => [self::ORDER_TYPE_NEW, self::ORDER_TYPE_REPLACEMENT, self::ORDER_TYPE_RENEW]],
            [['quantity'], 'integer', 'min' => 1, 'max' => 100],
            [['properties', 'cardProperties'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'clientId' => 'Клиент',
            'policyId' => 'Политика',
            'policyNumber' => 'Номер политики',
            'orderType' => 'Тип заказа',
            'cardStyle' => 'Стиль карты',
            'quantity' => 'Количество карт',
            'embossedName' => 'Имя на карте',
            'shipToFirst' => 'Имя получателя',
            'shipToLast' => 'Фамилия получателя',
            'shipToAddress1' => 'Адрес (строка 1)',
            'shipToAddress2' => 'Адрес (строка 2)',
            'shipToCity' => 'Город',
            'shipToState' => 'Штат',
            'shipToZip' => 'Индекс',
            'shipToCountry' => 'Страна',
            'shippingMethod' => 'Способ доставки',
            'rushProcessing' => 'Срочная обработка',
            'cardCarrier' => 'Отправлять с носителем карты',
        ];
    }

    /**
     * Получить список типов заказов
     *
     * @return array
     */
    public static function getOrderTypeList()
    {
        return [
            self::ORDER_TYPE_NEW => 'Новые карты',
            self::ORDER_TYPE_REPLACEMENT => 'Замена утерянных/украденных',
            self::ORDER_TYPE_RENEW => 'Продление истекших',
        ];
    }
    
    /**
     * Получить список стилей карт
     *
     * @return array
     */
    public static function getCardStyleList()
    {
        // Это краткий список наиболее распространенных стилей карт
        return [
            50 => 'EFS PRIMARY (Silver)',
            51 => 'EFS SECONDARY (Red)',
            31 => 'PILOT FLYING J',
            54 => 'IMPERIAL',
            60 => 'DRIVER',
            63 => 'VEHICLE FUEL ONLY',
        ];
    }
    
    /**
     * Получить список методов доставки
     *
     * @return array
     */
    public static function getShippingMethodList()
    {
        return [
            1 => 'Standard Shipping',
            2 => 'Ground Shipping',
            3 => 'Overnight Shipping',
            4 => 'Rush Overnight Shipping',
        ];
    }
    
    /**
     * Подготовить свойства заказа карт для API
     *
     * @return array
     */
    public function prepareProps()
    {
        // Базовые свойства, которые мы всегда устанавливаем
        $props = [
            ['key' => 'defCardStatus', 'value' => 'A'], // Активный статус карты
            ['key' => 'infosrc', 'value' => 'P'],      // Источник промптов - политика
            ['key' => 'lmtsrc', 'value' => 'P'],       // Источник лимитов - политика
            ['key' => 'timesrc', 'value' => 'P'],      // Источник временных ограничений - политика
            ['key' => 'locsrc', 'value' => 'P'],       // Источник локаций - политика
        ];
        
        // Добавляем пользовательские свойства
        if (!empty($this->properties)) {
            foreach ($this->properties as $key => $value) {
                $props[] = ['key' => $key, 'value' => $value];
            }
        }
        
        return $props;
    }
    
    /**
     * Подготовить свойства карт для API
     *
     * @return array
     */
    public function prepareCards()
    {
        $cards = [];
        
        for ($i = 0; $i < $this->quantity; $i++) {
            $card = [
                'idx' => $i,
                'props' => []
            ];
            
            // Добавляем пользовательские свойства для каждой карты
            if (!empty($this->cardProperties[$i])) {
                foreach ($this->cardProperties[$i] as $key => $value) {
                    $card['props'][] = ['key' => $key, 'value' => $value];
                }
            }
            
            $cards[] = $card;
        }
        
        return $cards;
    }
    
    /**
     * Заполнить форму данными клиента
     *
     * @param Client $client
     * @return void
     */
    public function fillClientData(Client $client)
    {
        $this->clientId = $client->id;
        $this->embossedName = $client->company_name;
        $this->shipToFirst = strtok($client->contact_name, ' ');
        $this->shipToLast = substr($client->contact_name, strlen($this->shipToFirst) + 1);
        $this->shipToAddress1 = $client->address;
        $this->shipToCity = $client->city;
        $this->shipToState = $client->state;
        $this->shipToZip = $client->zip;
        $this->shipToCountry = $client->country;
    }
    
    /**
     * Заполнить форму данными политики
     *
     * @param Policy $policy
     * @return void
     */
    public function fillPolicyData(Policy $policy)
    {
        $this->policyId = $policy->id;
        $this->policyNumber = $policy->policy_number;
    }
}