<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Модель платежа
 *
 * @property int $id
 * @property int $invoice_id ID счета
 * @property int $client_id ID клиента
 * @property float $amount Сумма платежа
 * @property string $payment_method Метод оплаты
 * @property string $reference_number Референс номер транзакции
 * @property string $status Статус платежа
 * @property string $notes Примечания
 * @property int $payment_date Дата платежа
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Invoice $invoice
 * @property Client $client
 */
class Payment extends ActiveRecord
{
    // Методы оплаты
    const METHOD_ONLINE = 'online';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_CASH = 'cash';
    const METHOD_CHECK = 'check';
    const METHOD_CREDIT_CARD = 'credit_card';

    // Статусы платежа
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%payment}}';
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
            [['invoice_id', 'client_id', 'amount', 'payment_method', 'payment_date'], 'required'],
            [['invoice_id', 'client_id', 'payment_date', 'created_at', 'updated_at'], 'integer'],
            [['amount'], 'number', 'min' => 0],
            [['payment_method'], 'in', 'range' => array_keys(self::getPaymentMethodList())],
            [['status'], 'in', 'range' => array_keys(self::getStatusList())],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['reference_number'], 'string', 'max' => 50],
            [['notes'], 'string'],
            [['invoice_id'], 'exist', 'skipOnError' => true, 'targetClass' => Invoice::class, 'targetAttribute' => ['invoice_id' => 'id']],
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
            'invoice_id' => 'Счет',
            'client_id' => 'Клиент',
            'amount' => 'Сумма',
            'payment_method' => 'Метод оплаты',
            'reference_number' => 'Референс номер',
            'status' => 'Статус',
            'notes' => 'Примечания',
            'payment_date' => 'Дата платежа',
            'created_at' => 'Дата создания',
            'updated_at' => 'Дата обновления',
        ];
    }

    /**
     * Получить счет
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoice()
    {
        return $this->hasOne(Invoice::class, ['id' => 'invoice_id']);
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
     * Список методов оплаты
     *
     * @return array
     */
    public static function getPaymentMethodList()
    {
        return [
            self::METHOD_ONLINE => 'Онлайн-оплата',
            self::METHOD_BANK_TRANSFER => 'Банковский перевод',
            self::METHOD_CASH => 'Наличный расчет',
            self::METHOD_CHECK => 'Чек',
            self::METHOD_CREDIT_CARD => 'Кредитная карта',
        ];
    }

    /**
     * Список статусов платежа
     *
     * @return array
     */
    public static function getStatusList()
    {
        return [
            self::STATUS_PENDING => 'В обработке',
            self::STATUS_COMPLETED => 'Завершен',
            self::STATUS_FAILED => 'Не удался',
            self::STATUS_REFUNDED => 'Возвращен',
        ];
    }

    /**
     * Получить текстовое представление метода оплаты
     *
     * @return string
     */
    public function getPaymentMethodText()
    {
        return self::getPaymentMethodList()[$this->payment_method] ?? $this->payment_method;
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
     * Форматированная дата платежа
     *
     * @return string
     */
    public function getPaymentDateFormatted()
    {
        return Yii::$app->formatter->asDate($this->payment_date);
    }

    /**
     * Генерация референс-номера для платежа
     *
     * @return string
     */
    public function generateReferenceNumber()
    {
        // Формат: YYYYMMDD-CLIENTID-RANDOM
        $prefix = date('Ymd') . '-' . $this->client_id . '-';
        return $prefix . strtoupper(substr(md5(uniqid()), 0, 6));
    }

    /**
     * Действия перед сохранением
     *
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Автоматическая генерация референс-номера
            if ($insert && empty($this->reference_number)) {
                $this->reference_number = $this->generateReferenceNumber();
            }

            return true;
        }
        return false;
    }

    /**
     * Действия после сохранения
     *
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        // Если платеж завершен, обновляем статус счета
        if ($this->status === self::STATUS_COMPLETED) {
            $invoice = $this->invoice;
            $invoice->status = Invoice::STATUS_PAID;
            $invoice->paid_date = $this->payment_date;
            $invoice->save();
        }
    }
}