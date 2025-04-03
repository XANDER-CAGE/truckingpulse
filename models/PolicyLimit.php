<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "policy_limit".
 *
 * @property int $id
 * @property int $policy_id
 * @property string $limit_id
 * @property int $limit_value
 * @property int $hours
 * @property int $min_hours
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Policy $policy
 */
class PolicyLimit extends ActiveRecord
{
    /**
     * Список доступных лимитов
     */
    const LIMIT_ULSD = 'ULSD';      // Ultra Low Sulfur Diesel
    const LIMIT_GAS = 'GAS';        // Gasoline
    const LIMIT_RFR = 'RFR';        // Reefer
    const LIMIT_DEF = 'DEF';        // Diesel Exhaust Fluid
    const LIMIT_CASH = 'CADV';      // Cash Advance
    const LIMIT_REPAIRS = 'REPR';   // Repairs
    const LIMIT_TIRES = 'TIRE';     // Tires and related
    const LIMIT_SCALES = 'SCLE';    // Weigh Scales
    const LIMIT_ANY = 'ANY';        // Any product
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'policy_limit';
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
            [['policy_id', 'limit_id', 'limit_value'], 'required'],
            [['policy_id', 'limit_value', 'hours', 'min_hours', 'created_at', 'updated_at'], 'integer'],
            [['limit_id'], 'string', 'max' => 10],
            [['limit_id'], 'in', 'range' => array_keys(self::getLimitTypesList())],
            [['hours'], 'default', 'value' => 0],
            [['min_hours'], 'default', 'value' => 0],
            [['policy_id'], 'exist', 'skipOnError' => true, 'targetClass' => Policy::class, 'targetAttribute' => ['policy_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'policy_id' => 'Политика',
            'limit_id' => 'Тип лимита',
            'limit_value' => 'Значение лимита',
            'hours' => 'Период (часы)',
            'min_hours' => 'Мин. часов между использованиями',
            'created_at' => 'Дата создания',
            'updated_at' => 'Дата обновления',
        ];
    }

    /**
     * Получить политику
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPolicy()
    {
        return $this->hasOne(Policy::class, ['id' => 'policy_id']);
    }
    
    /**
     * Получить список типов лимитов
     *
     * @return array
     */
    public static function getLimitTypesList()
    {
        return [
            self::LIMIT_ULSD => 'Дизель (ULSD)',
            self::LIMIT_GAS => 'Бензин',
            self::LIMIT_RFR => 'Рефрижератор',
            self::LIMIT_DEF => 'Жидкость DEF',
            self::LIMIT_CASH => 'Наличные',
            self::LIMIT_REPAIRS => 'Ремонт',
            self::LIMIT_TIRES => 'Шины',
            self::LIMIT_SCALES => 'Весы',
            self::LIMIT_ANY => 'Любые покупки',
        ];
    }
    
    /**
     * Получить текстовое представление типа лимита
     *
     * @return string
     */
    public function getLimitTypeText()
    {
        return self::getLimitTypesList()[$this->limit_id] ?? $this->limit_id;
    }
    
    /**
     * Получить форматированное значение лимита
     *
     * @return string
     */
    public function getFormattedLimitValue()
    {
        // Для топливных лимитов отображаем в галлонах
        $fuelLimits = [self::LIMIT_ULSD, self::LIMIT_GAS, self::LIMIT_RFR, self::LIMIT_DEF];
        
        if (in_array($this->limit_id, $fuelLimits)) {
            return $this->limit_value . ' галл.';
        }
        
        // Для денежных лимитов отображаем в долларах
        return '$' . number_format($this->limit_value, 2);
    }
    
    /**
     * Получить текстовое описание периода лимита
     *
     * @return string
     */
    public function getPeriodText()
    {
        if ($this->hours == 0) {
            return 'Без ограничений';
        }
        
        if ($this->hours == 24) {
            return 'Ежедневно';
        }
        
        if ($this->hours == 168) {
            return 'Еженедельно';
        }
        
        if ($this->hours == 720) {
            return 'Ежемесячно';
        }
        
        return 'Каждые ' . $this->hours . ' ч.';
    }
}