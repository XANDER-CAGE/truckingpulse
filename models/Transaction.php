<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "transaction".
 *
 * @property int $id
 * @property int $transaction_id WEX transaction ID
 * @property int $card_id
 * @property int $client_id
 * @property int $transaction_date
 * @property int|null $post_date
 * @property int|null $location_id
 * @property string|null $location_name
 * @property string|null $location_city
 * @property string|null $location_state
 * @property string|null $location_country
 * @property string|null $product_code
 * @property string|null $product_description
 * @property float|null $quantity
 * @property float|null $retail_price
 * @property float|null $discounted_price
 * @property float|null $client_price
 * @property float|null $retail_amount
 * @property float|null $funded_amount
 * @property float|null $client_amount
 * @property float|null $our_profit
 * @property string|null $driver_id
 * @property int|null $odometer
 * @property string|null $invoice_number
 * @property string|null $auth_code
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Card $card
 * @property Client $client
 * @property InvoiceItem[] $invoiceItems
 */
class Transaction extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'transaction';
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
            [['transaction_id', 'card_id', 'client_id', 'transaction_date'], 'required'],
            [['transaction_id', 'card_id', 'client_id', 'transaction_date', 'post_date', 'location_id', 'odometer', 'created_at', 'updated_at'], 'integer'],
            [['quantity', 'retail_price', 'discounted_price', 'client_price', 'retail_amount', 'funded_amount', 'client_amount', 'our_profit'], 'number'],
            [['location_name'], 'string', 'max' => 255],
            [['location_city'], 'string', 'max' => 100],
            [['location_state'], 'string', 'max' => 2],
            [['location_country'], 'string', 'max' => 3],
            [['product_code'], 'string', 'max' => 10],
            [['product_description'], 'string', 'max' => 100],
            [['driver_id'], 'string', 'max' => 24],
            [['invoice_number', 'auth_code'], 'string', 'max' => 20],
            [['transaction_id'], 'unique'],
            [['card_id'], 'exist', 'skipOnError' => true, 'targetClass' => Card::class, 'targetAttribute' => ['card_id' => 'id']],
            [['client_id'], 'exist', 'skipOnError' => true, 'targetClass' => Client::class, 'targetAttribute' => ['client_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'transaction_id' => 'ID транзакции WEX',
            'card_id' => 'Карта',
            'client_id' => 'Клиент',
            'transaction_date' => 'Дата транзакции',
            'post_date' => 'Дата проводки',
            'location_id' => 'ID локации',
            'location_name' => 'Название локации',
            'location_city' => 'Город',
            'location_state' => 'Штат',
            'location_country' => 'Страна',
            'product_code' => 'Код продукта',
            'product_description' => 'Описание продукта',
            'quantity' => 'Количество',
            'retail_price' => 'Розничная цена',
            'discounted_price' => 'Цена со скидкой WEX',
            'client_price' => 'Цена для клиента',
            'retail_amount' => 'Розничная сумма',
            'funded_amount' => 'Сумма со скидкой WEX',
            'client_amount' => 'Сумма для клиента',
            'our_profit' => 'Наша прибыль',
            'driver_id' => 'ID водителя',
            'odometer' => 'Одометр',
            'invoice_number' => 'Номер счета',
            'auth_code' => 'Код авторизации',
            'created_at' => 'Дата создания',
            'updated_at' => 'Дата обновления',
        ];
    }

    /**
     * Получить карту
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCard()
    {
        return $this->hasOne(Card::class, ['id' => 'card_id']);
    }

    /**
     * Получить клиента
     *
     * @return \yii\db\ActiveQuery
     */
    public function getClient()
    {
        return $this->hasOne(Client::class, ['id' => 'client_id']);
    }

    /**
     * Получить позиции счетов
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoiceItems()
    {
        return $this->hasMany(InvoiceItem::class, ['transaction_id' => 'id']);
    }
    
    /**
     * Получить форматированную дату транзакции
     *
     * @return string
     */
    public function getTransactionDateFormatted()
    {
        return Yii::$app->formatter->asDatetime($this->transaction_date);
    }
    
    /**
     * Получить полное название локации
     *
     * @return string
     */
    public function getFullLocationName()
    {
        $parts = array_filter([
            $this->location_name,
            $this->location_city,
            $this->location_state,
        ]);
        
        return implode(', ', $parts);
    }
    
    /**
     * Получить разницу цен (скидка WEX)
     *
     * @return float
     */
    public function getWexDiscountAmount()
    {
        return $this->retail_amount - $this->funded_amount;
    }
    
    /**
     * Получить разницу цен в процентах (скидка WEX)
     *
     * @return float
     */
    public function getWexDiscountPercent()
    {
        if (!$this->retail_amount || $this->retail_amount == 0) {
            return 0;
        }
        
        return ($this->getWexDiscountAmount() / $this->retail_amount) * 100;
    }
    
    /**
     * Получить разницу цен (скидка клиенту)
     *
     * @return float
     */
    public function getClientDiscountAmount()
    {
        return $this->retail_amount - $this->client_amount;
    }
    
    /**
     * Получить разницу цен в процентах (скидка клиенту)
     *
     * @return float
     */
    public function getClientDiscountPercent()
    {
        if (!$this->retail_amount || $this->retail_amount == 0) {
            return 0;
        }
        
        return ($this->getClientDiscountAmount() / $this->retail_amount) * 100;
    }
    
    /**
     * Рассчитать прибыль от транзакции
     *
     * @return float
     */
    public function calculateProfit()
    {
        return $this->client_amount - $this->funded_amount;
    }
    
    /**
     * Обновить прибыль перед сохранением
     *
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Рассчитываем прибыль на основе текущих значений
            $this->our_profit = $this->calculateProfit();
            return true;
        }
        
        return false;
    }
}