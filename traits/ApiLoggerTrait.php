<?php

namespace app\traits;

use app\models\ApiLog;
use Yii;
use yii\helpers\Json;
use yii\web\Request;
use yii\web\Response;

trait ApiLoggerTrait
{
    /**
     * Логирование API-запроса
     * 
     * @param Request $request Объект запроса
     * @param Response $response Объект ответа
     * @param float $startTime Время начала запроса
     * @return void
     */
    protected function logApiRequest(Request $request, Response $response, float $startTime)
    {
        try {
            $apiLog = new ApiLog();
            $apiLog->method = Yii::$app->controller->id . '/' . Yii::$app->controller->action->id;
            $apiLog->request = $this->formatRequestData($request);
            $apiLog->response = $this->formatResponseData($response);
            $apiLog->status = $response->statusCode;
            $apiLog->duration = intval((microtime(true) - $startTime) * 1000); // Длительность в миллисекундах
            $apiLog->created_at = time();

            // Логируем только если есть значимый контент
            if (!empty($apiLog->request) || !empty($apiLog->response)) {
                $apiLog->save(false);
            }
        } catch (\Exception $e) {
            // Логируем ошибки логирования, но не прерываем основной процесс
            Yii::error('Ошибка логирования API: ' . $e->getMessage(), 'api-logger');
        }
    }

    /**
     * Форматирование данных запроса
     * 
     * @param Request $request
     * @return string
     */
    private function formatRequestData(Request $request): string
    {
        $requestData = [
            'url' => $request->absoluteUrl,
            'method' => $request->method,
            'headers' => $this->filterSensitiveHeaders($request->headers->toArray()),
            'body' => $this->getSafeRequestBody($request)
        ];

        return Json::encode($requestData);
    }

    /**
     * Форматирование данных ответа
     * 
     * @param Response $response
     * @return string
     */
    private function formatResponseData(Response $response): string
    {
        // Безопасное получение контента ответа
        $content = $response->data ?? $response->content;
        
        // Маскируем чувствительные данные
        if (is_array($content)) {
            $content = $this->maskSensitiveData($content);
        }

        $responseData = [
            'status' => $response->statusCode,
            'headers' => $this->filterSensitiveHeaders($response->headers->toArray()),
            'body' => $content
        ];

        return Json::encode($responseData);
    }

    /**
     * Получение безопасного тела запроса
     * 
     * @param Request $request
     * @return array
     */
    private function getSafeRequestBody(Request $request): array
    {
        $bodyParams = $request->bodyParams;
        return $this->maskSensitiveData($bodyParams);
    }

    /**
     * Маскирование чувствительных данных
     * 
     * @param array $data
     * @return array
     */
    private function maskSensitiveData(array $data): array
    {
        $sensitiveFields = [
            'password', 'token', 'access_token', 'refresh_token', 
            'api_key', 'secret', 'credentials'
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***MASKED***';
            }
        }

        return $data;
    }

    /**
     * Фильтрация заголовков от чувствительной информации
     * 
     * @param array $headers
     * @return array
     */
    private function filterSensitiveHeaders(array $headers): array
    {
        $sensitiveHeaders = [
            'authorization', 'x-access-token', 'cookie', 
            'set-cookie', 'x-csrf-token'
        ];

        foreach ($headers as $key => $value) {
            if (in_array(strtolower($key), $sensitiveHeaders)) {
                $headers[$key] = '***MASKED***';
            }
        }

        return $headers;
    }
}