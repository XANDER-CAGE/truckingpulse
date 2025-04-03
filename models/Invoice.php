<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Модель счета
 *
 * @property int $id
 * @property string $invoice_number Уникальный номер счета
 * @property int $client_id ID клиента
 * @property int $start_date Начало периода
 * @property int $end_date Конец периода
 * @property float $subtotal Промежуточный итог
 * @property float $tax НДС
 * @property float $total Общая сумма
 * @property float $fuel_amount Сумма за топливо
 * @property float $service_fee Сервисный сбор
 * @property string $status Статус счета
 * @property int $due_date Дата оплаты
 * @property int $paid_date Дата оплаты
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Client $client
 * @property InvoiceItem[] $invoiceItems
 */
class Invoice extends ActiveRecord
{
    // Статусы счета
    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELED = 'canceled';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%invoice}}';
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
            [['client_id', 'start_date', 'end_date', 'total'], 'required'],
            [['client_id', 'start_date', 'end_date', 'due_date', 'paid_date'], 'integer'],
            [['subtotal', 'tax', 'total', 'fuel_amount', 'service_fee'], 'number'],
            [['status'], 'string', 'max' => 20],
            [['invoice_number'], 'string', 'max' => 50],
            [['invoice_number'], 'unique'],
            [['status'], 'in', 'range' => array_keys(self::getStatusList())],
            [['status'], 'default', 'value' => self::STATUS_DRAFT],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'invoice_number' => 'Номер счета',
            'client_id' => 'Клиент',
            'start_date' => 'Начало периода',
            'end_date' => 'Конец периода',
            'subtotal' => 'Промежуточный итог',
            'tax' => 'НДС',
            'total' => 'Итого',
            'fuel_amount' => 'Сумма за топливо',
            'service_fee' => 'Сервисный сбор',
            'status' => 'Статус',
            'due_date' => 'Дата оплаты',
            'paid_date' => 'Дата оплаты',
        ];
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
     * Получить позиции счета
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoiceItems()
    {
        return $this->hasMany(InvoiceItem::class, ['invoice_id' => 'id']);
    }

    /**
     * Получить список статусов
     *
     * @return array
     */
    public static function getStatusList()
    {
        return [
            self::STATUS_DRAFT => 'Черновик',
            self::STATUS_SENT => 'Отправлен',
            self::STATUS_PAID => 'Оплачен',
            self::STATUS_OVERDUE => 'Просрочен',
            self::STATUS_CANCELED => 'Отменен',
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
     * Форматированная дата создания
     *
     * @return string
     */
    public function getCreatedAtFormatted()
    {
        return Yii::$app->formatter->asDate($this->created_at);
    }

    /**
     * Форматированная дата оплаты
     *
     * @return string
     */
    public function getPaidAtFormatted()
    {
        return $this->paid_date ? Yii::$app->formatter->asDate($this->paid_date) : 'Не оплачен';
    }

    /**
     * Сгенерировать уникальный номер счета
     *
     * @return string
     */
    public function generateInvoiceNumber()
    {
        $prefix = 'INV-' . date('Ym');
        $lastInvoice = self::find()
            ->where(['like', 'invoice_number', $prefix])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        $number = $lastInvoice 
            ? intval(substr($lastInvoice->invoice_number, -4)) + 1 
            : 1;

        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
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
            // Автоматическая генерация номера счета
            if ($insert && empty($this->invoice_number)) {
                $this->invoice_number = $this->generateInvoiceNumber();
            }

            // Автоматическая установка даты оплаты
            if ($this->status === self::STATUS_PAID && !$this->paid_date) {
                $this->paid_date = time();
            }

            return true;
        }
        return false;
    }
       /**
     * Получить платежи
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPayments()
    {
        return $this->hasMany(Payment::class, ['invoice_id' => 'id']);
    }

    /**
     * Получить сумму оплаченную по счету
     *
     * @return float
     */
    public function getPaidAmount()
    {
        return $this->getPayments()
            ->andWhere(['status' => Payment::STATUS_COMPLETED])
            ->sum('amount');
    }

    /**
     * Проверить полностью ли оплачен счет
     *
     * @return bool
     */
    public function isFullyPaid()
    {
        return $this->getPaidAmount() >= $this->total;
    }

    /**
     * Получить остаток к оплате
     *
     * @return float
     */
    public function getBalanceDue()
    {
        return max(0, $this->total - $this->getPaidAmount());
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

        // Автоматическое обновление статуса при полной оплате
        if ($this->isFullyPaid() && $this->status !== self::STATUS_PAID) {
            $this->status = self::STATUS_PAID;
            $this->paid_date = time();
            $this->save(false);
        }
    }

    /**
     * Сценарии для валидации
     *
     * @return array
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['payment'] = ['status', 'paid_date']; // Сценарий для обновления при оплате
        return $scenarios;
    }
}