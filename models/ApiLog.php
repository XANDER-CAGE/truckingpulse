<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "api_log".
 *
 * @property int $id
 * @property string $method
 * @property string|null $request
 * @property string|null $response
 * @property string $status
 * @property string|null $error
 * @property int|null $duration
 * @property int $created_at
 */
class ApiLog extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'api_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['method', 'status', 'created_at'], 'required'],
            [['request', 'response', 'error'], 'string'],
            [['duration', 'created_at'], 'integer'],
            [['method'], 'string', 'max' => 50],
            [['status'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'method' => 'Метод',
            'request' => 'Запрос',
            'response' => 'Ответ',
            'status' => 'Статус',
            'error' => 'Ошибка',
            'duration' => 'Длительность (мс)',
            'created_at' => 'Дата создания',
        ];
    }
    
    /**
     * Получить красивую дату создания
     * 
     * @return string
     */
    public function getCreatedAtFormatted()
    {
        return Yii::$app->formatter->asDatetime($this->created_at);
    }

    /**
     * Получить краткое описание запроса
     * 
     * @param int $length Максимальная длина
     * @return string
     */
    public function getRequestShort($length = 100)
    {
        return $this->request ? mb_substr($this->request, 0, $length) . (mb_strlen($this->request) > $length ? '...' : '') : '';
    }

    /**
     * Получить краткое описание ответа
     * 
     * @param int $length Максимальная длина
     * @return string
     */
    public function getResponseShort($length = 100)
    {
        return $this->response ? mb_substr($this->response, 0, $length) . (mb_strlen($this->response) > $length ? '...' : '') : '';
    }
}