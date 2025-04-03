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
            [['request', 'response'], 'string'],
            [['duration', 'created_at', 'status'], 'integer'],
            [['method'], 'string', 'max' => 100],
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
            'status' => 'Код статуса',
            'duration' => 'Время выполнения (мс)',
            'created_at' => 'Дата запроса',
        ];
    }
    
    /**
     * Получить дату создания в красивом формате
     * 
     * @return string
     */
    public function getCreatedAtFormatted()
    {
        return Yii::$app->formatter->asDatetime($this->created_at);
    }

    /**
     * Получить URL из запроса
     * 
     * @return string
     */
    public function getRequestUrl()
    {
        try {
            $requestData = json_decode($this->request, true);
            return $requestData['url'] ?? 'N/A';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    /**
     * Получить HTTP-метод
     * 
     * @return string
     */
    public function getRequestMethod()
    {
        try {
            $requestData = json_decode($this->request, true);
            return $requestData['method'] ?? 'N/A';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    /**
     * Получить краткую информацию о запросе
     * 
     * @param int $length Максимальная длина
     * @return string
     */
    public function getRequestPreview($length = 200)
    {
        try {
            $requestData = json_decode($this->request, true);
            $preview = json_encode($requestData['body'] ?? [], JSON_UNESCAPED_UNICODE);
            return mb_strlen($preview) > $length 
                ? mb_substr($preview, 0, $length) . '...' 
                : $preview;
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    /**
     * Получить уровень важности лога
     * 
     * @return string
     */
    public function getSeverityLevel()
    {
        if ($this->status >= 500) return 'critical';
        if ($this->status >= 400) return 'error';
        if ($this->status >= 300) return 'warning';
        return 'info';
    }
}