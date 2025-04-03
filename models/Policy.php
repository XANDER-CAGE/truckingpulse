<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "policy".
 *
 * @property int $id
 * @property int $policy_number WEX policy number
 * @property string $description
 * @property int|null $contract_id
 * @property bool $hand_enter
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Card[] $cards
 * @property PolicyLimit[] $policyLimits
 */
class Policy extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'policy';
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
            [['policy_number', 'description'], 'required'],
            [['policy_number', 'contract_id', 'created_at', 'updated_at'], 'integer'],
            [['hand_enter'], 'boolean'],
            [['description'], 'string', 'max' => 255],
            [['policy_number'], 'unique'],
            [['hand_enter'], 'default', 'value' => false],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'policy_number' => 'Номер политики',
            'description' => 'Описание',
            'contract_id' => 'ID контракта',
            'hand_enter' => 'Ручной ввод',
            'created_at' => 'Дата создания',
            'updated_at' => 'Дата обновления',
        ];
    }

    /**
     * Получить карты, привязанные к политике
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCards()
    {
        return $this->hasMany(Card::class, ['policy_id' => 'id']);
    }

    /**
     * Получить лимиты политики
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPolicyLimits()
    {
        return $this->hasMany(PolicyLimit::class, ['policy_id' => 'id']);
    }
    
    /**
     * Получить текстовое представление ручного ввода
     *
     * @return string
     */
    public function getHandEnterText()
    {
        return $this->hand_enter ? 'Разрешен' : 'Запрещен';
    }
    
    /**
     * Получить количество активных карт, привязанных к политике
     *
     * @return int
     */
    public function getActiveCardsCount()
    {
        return Card::find()
            ->where(['policy_id' => $this->id, 'status' => Card::STATUS_ACTIVE])
            ->count();
    }
}