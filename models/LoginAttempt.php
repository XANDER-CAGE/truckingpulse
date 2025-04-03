<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Модель для хранения попыток входа
 * 
 * @property int $id
 * @property string $username
 * @property string $ip
 * @property string $status
 * @property int $created_at
 */
class LoginAttempt extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%login_attempts}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['username', 'ip', 'status', 'created_at'], 'required'],
            [['created_at'], 'integer'],
            [['username'], 'string', 'max' => 255],
            [['ip'], 'string', 'max' => 45],
            [['status'], 'in', 'range' => ['failed', 'success']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Имя пользователя',
            'ip' => 'IP-адрес',
            'status' => 'Статус',
            'created_at' => 'Время попытки',
        ];
    }
}