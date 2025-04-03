<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "card".
 *
 * @property int $id
 * @property string $card_number
 * @property int|null $client_id
 * @property int|null $policy_id
 * @property string $status
 * @property string|null $company_xref
 * @property string|null $driver_id
 * @property string|null $driver_name
 * @property string|null $unit_number
 * @property string $hand_enter
 * @property string|null $pin
 * @property int|null $issue_date
 * @property int|null $last_used_date
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Client $client
 * @property Policy $policy
 * @property Transaction[] $transactions
 */
class Card extends ActiveRecord
{
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_HOLD = 'hold';
    const STATUS_DELETED = 'deleted';
    
    const HAND_ENTER_POLICY = 'POLICY';
    const HAND_ENTER_ALLOW = 'ALLOW';
    const HAND_ENTER_DISALLOW = 'DISALLOW';
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'card';
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
            [['card_number'], 'required'],
            [['client_id', 'policy_id', 'issue_date', 'last_used_date', 'created_at', 'updated_at'], 'integer'],
            [['card_number'], 'string', 'max' => 25],
            [['status'], 'string', 'max' => 20],
            [['company_xref'], 'string', 'max' => 15],
            [['driver_id', 'unit_number'], 'string', 'max' => 24],
            [['driver_name'], 'string', 'max' => 50],
            [['hand_enter'], 'string', 'max' => 10],
            [['pin'], 'string', 'max' => 255],
            [['card_number'], 'unique'],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
            [['hand_enter'], 'default', 'value' => self::HAND_ENTER_POLICY],
            [['status'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_HOLD, self::STATUS_DELETED]],
            [['hand_enter'], 'in', 'range' => [self::HAND_ENTER_POLICY, self::HAND_ENTER_ALLOW, self::HAND_ENTER_DISALLOW]],
            [['client_id'], 'exist', 'skipOnError' => true, 'targetClass' => Client::class, 'targetAttribute' => ['client_id' => 'id']],
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
            'card_number' => 'Номер карты',
            'client_id' => 'Клиент',
            'policy_id' => 'Политика',
            'status' => 'Статус',
            'company_xref' => 'Код компании',
            'driver_id' => 'ID водителя',
            'driver_name' => 'Имя водителя',
            'unit_number' => 'Номер транспорта',
            'hand_enter' => 'Ручной ввод',
            'pin' => 'PIN-код',
            'issue_date' => 'Дата выпуска',
            'last_used_date' => 'Последнее использование',
            'created_at' => 'Дата создания',
            'updated_at' => 'Дата обновления',
        ];
    }

    /**
     * Получить клиента, привязанного к карте
     *
     * @return \yii\db\ActiveQuery
     */
    public function getClient()
    {
        return $this->hasOne(Client::class, ['id' => 'client_id']);
    }

    /**
     * Получить политику, привязанную к карте
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPolicy()
    {
        return $this->hasOne(Policy::class, ['id' => 'policy_id']);
    }

    /**
     * Получить транзакции по карте
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTransactions()
    {
        return $this->hasMany(Transaction::class, ['card_id' => 'id']);
    }
    
    /**
     * Получить список статусов
     *
     * @return array
     */
    public static function getStatusList()
    {
        return [
            self::STATUS_ACTIVE => 'Активна',
            self::STATUS_INACTIVE => 'Неактивна',
            self::STATUS_HOLD => 'На удержании',
            self::STATUS_DELETED => 'Удалена',
        ];
    }
    
    /**
     * Получить список режимов ручного ввода
     *
     * @return array
     */
    public static function getHandEnterList()
    {
        return [
            self::HAND_ENTER_POLICY => 'По политике',
            self::HAND_ENTER_ALLOW => 'Разрешен',
            self::HAND_ENTER_DISALLOW => 'Запрещен',
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
     * Получить текстовое представление режима ручного ввода
     *
     * @return string
     */
    public function getHandEnterText()
    {
        return self::getHandEnterList()[$this->hand_enter] ?? $this->hand_enter;
    }
    
    /**
     * Получить маскированный номер карты
     *
     * @return string
     */
    public function getMaskedCardNumber()
    {
        $length = strlen($this->card_number);
        if ($length <= 4) {
            return $this->card_number;
        }
        
        return str_repeat('*', $length - 4) . substr($this->card_number, -4);
    }
    
    /**
     * Получить последние 4 цифры карты
     *
     * @return string
     */
    public function getLast4()
    {
        return substr($this->card_number, -4);
    }
    
    /**
     * Получить отформатированную дату выпуска
     *
     * @return string
     */
    public function getIssueDateFormatted()
    {
        return $this->issue_date ? Yii::$app->formatter->asDate($this->issue_date) : 'Н/Д';
    }
    
    /**
     * Получить отформатированную дату последнего использования
     *
     * @return string
     */
    public function getLastUsedDateFormatted()
    {
        return $this->last_used_date ? Yii::$app->formatter->asDatetime($this->last_used_date) : 'Не использовалась';
    }
}