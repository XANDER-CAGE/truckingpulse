<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "client".
 *
 * @property int $id
 * @property int $carrier_id WEX child carrier ID
 * @property string $company_name
 * @property string $contact_name
 * @property string $email
 * @property string $phone
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip
 * @property string $country
 * @property string $status
 * @property string|null $rebate_type
 * @property float|null $rebate_value
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Card[] $cards
 * @property Invoice[] $invoices
 * @property Transaction[] $transactions
 */
class Client extends ActiveRecord
{
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_BLOCKED = 'blocked';
    
    const REBATE_TYPE_FIXED = 'fixed';
    const REBATE_TYPE_PERCENTAGE = 'percentage';
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'client';
    }
    
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['carrier_id', 'company_name', 'contact_name', 'email', 'phone'], 'required'],
            [['carrier_id', 'created_at', 'updated_at'], 'integer'],
            [['rebate_value'], 'number'],
            [['company_name', 'contact_name', 'email', 'address'], 'string', 'max' => 255],
            [['phone'], 'string', 'max' => 20],
            [['city'], 'string', 'max' => 100],
            [['state'], 'string', 'max' => 2],
            [['zip'], 'string', 'max' => 10],
            [['country'], 'string', 'max' => 3],
            [['country'], 'default', 'value' => 'USA'],
            [['status'], 'string', 'max' => 20],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
            [['status'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_BLOCKED]],
            [['rebate_type'], 'string', 'max' => 20],
            [['rebate_type'], 'in', 'range' => [self::REBATE_TYPE_FIXED, self::REBATE_TYPE_PERCENTAGE]],
            [['carrier_id'], 'unique'],
            [['email'], 'email'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'carrier_id' => 'WEX ID',
            'company_name' => 'Название компании',
            'contact_name' => 'Контактное лицо',
            'email' => 'Email',
            'phone' => 'Телефон',
            'address' => 'Адрес',
            'city' => 'Город',
            'state' => 'Штат',
            'zip' => 'Почтовый индекс',
            'country' => 'Страна',
            'status' => 'Статус',
            'rebate_type' => 'Тип скидки',
            'rebate_value' => 'Размер скидки',
            'created_at' => 'Дата создания',
            'updated_at' => 'Дата обновления',
        ];
    }

    /**
     * Получить все карты клиента
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCards()
    {
        return $this->hasMany(Card::class, ['client_id' => 'id']);
    }

    /**
     * Получить все счета клиента
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoices()
    {
        return $this->hasMany(Invoice::class, ['client_id' => 'id']);
    }

    /**
     * Получить все транзакции клиента
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTransactions()
    {
        return $this->hasMany(Transaction::class, ['client_id' => 'id']);
    }
    
    /**
     * Получить список статусов
     *
     * @return array
     */
    public static function getStatusList()
    {
        return [
            self::STATUS_ACTIVE => 'Активен',
            self::STATUS_INACTIVE => 'Неактивен',
            self::STATUS_BLOCKED => 'Заблокирован',
        ];
    }
    
    /**
     * Получить список типов скидок
     *
     * @return array
     */
    public static function getRebateTypeList()
    {
        return [
            self::REBATE_TYPE_FIXED => 'Фиксированная ($)',
            self::REBATE_TYPE_PERCENTAGE => 'Процентная (%)',
        ];
    }
    
    /**
     * Получить текстовое представление статуса
     *
     * @return string
     */
    public function getStatusText()
    {
        return self::getStatusList()[$this->status] ?? $this->status;
    }
    
    /**
     * Получить текстовое представление типа скидки
     *
     * @return string
     */
    public function getRebateTypeText()
    {
        return self::getRebateTypeList()[$this->rebate_type] ?? '';
    }
    
    /**
     * Получить форматированную скидку
     *
     * @return string
     */
    public function getFormattedRebate()
    {
        if (!$this->rebate_type || !$this->rebate_value) {
            return 'Не установлена';
        }
        
        if ($this->rebate_type == self::REBATE_TYPE_FIXED) {
            return sprintf('$%.4f за галлон', $this->rebate_value);
        } else {
            return sprintf('%.2f%% от стоимости', $this->rebate_value);
        }
    }
    
    /**
     * Получить полный адрес
     *
     * @return string
     */
    public function getFullAddress()
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->zip,
            $this->country != 'USA' ? $this->country : null,
        ]);
        
        return implode(', ', $parts);
    }
}