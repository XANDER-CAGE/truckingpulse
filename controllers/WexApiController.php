<?php

namespace app\controllers;

use app\models\Card;
use app\models\Client;
use app\models\Policy;
use app\models\Transaction;
use app\services\WexApiService;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * WexApiController реализует действия для работы с WEX API.
 */
class WexApiController extends Controller
{
    /**
     * @var WexApiService
     */
    private $wexApiService;

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'import-transactions' => ['post'],
                    'sync-child-carriers' => ['post'],
                    'create-card-order' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->wexApiService = Yii::$app->wexApi;
    }

    /**
     * Отображает страницу тестирования API.
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Тестирует соединение с WEX API.
     * @return Response
     */
    public function actionTestConnection()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $clientId = $this->wexApiService->login();
            $this->wexApiService->logout();
            
            return [
                'success' => true,
                'message' => 'Соединение с WEX API успешно установлено. Client ID: ' . $clientId,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка соединения с WEX API: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Импортирует транзакции из WEX API.
     * @return Response
     */
    public function actionImportTransactions()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $minutes = Yii::$app->request->post('minutes', 60);
        $endDate = new \DateTime();
        $begDate = (clone $endDate)->modify("-{$minutes} minutes");
        
        $begDateFormatted = $begDate->format('Y-m-d\TH:i:s');
        $endDateFormatted = $endDate->format('Y-m-d\TH:i:s');
        
        try {
            // Логин в API
            $this->wexApiService->login();
            
            // Получаем транзакции
            $transactions = $this->wexApiService->getChildTransactions($begDateFormatted, $endDateFormatted);
            
            // Разлогиниваемся
            $this->wexApiService->logout();
            
            // Если транзакций нет
            if (empty($transactions)) {
                return [
                    'success' => true,
                    'message' => 'Новых транзакций не найдено',
                    'count' => 0,
                ];
            }
            
            // Счетчики
            $total = count($transactions);
            $imported = 0;
            $skipped = 0;
            
            foreach ($transactions as $transData) {
                // Проверяем, существует ли транзакция
                $exists = Transaction::findOne(['transaction_id' => $transData->transactionId]);
                if ($exists) {
                    $skipped++;
                    continue;
                }
                
                // Ищем карту по номеру
                $card = Card::findOne(['card_number' => $transData->cardNumber]);
                if (!$card) {
                    // Если карты нет, создаем временную запись
                    $card = new Card();
                    $card->card_number = $transData->cardNumber;
                    $card->status = Card::STATUS_ACTIVE;
                    
                    // Пытаемся найти клиента по carrier_id
                    $client = Client::findOne(['carrier_id' => $transData->carrierId]);
                    if ($client) {
                        $card->client_id = $client->id;
                    }
                    
                    $card->save();
                }
                
                // Ищем или создаем клиента
                $client = $card->client ?? Client::findOne(['carrier_id' => $transData->carrierId]);
                if (!$client) {
                    // Если клиента нет, создаем временную запись
                    $client = new Client();
                    $client->carrier_id = $transData->carrierId;
                    $client->company_name = "Клиент #" . $transData->carrierId;
                    $client->contact_name = "Неизвестно";
                    $client->email = "unknown@example.com";
                    $client->phone = "0000000000";
                    $client->status = Client::STATUS_ACTIVE;
                    $client->save();
                    
                    // Обновляем карту клиентом
                    if ($card && !$card->client_id) {
                        $card->client_id = $client->id;
                        $card->save();
                    }
                }
                
                // Создаем новую транзакцию
                $transaction = new Transaction();
                $transaction->transaction_id = $transData->transactionId;
                $transaction->card_id = $card->id;
                $transaction->client_id = $client->id;
                $transaction->transaction_date = strtotime($transData->transactionDate);
                $transaction->post_date = isset($transData->postDate) ? strtotime($transData->postDate) : null;
                
                // Информация о локации
                if (isset($transData->locationId)) {
                    $transaction->location_id = $transData->locationId;
                }
                
                if (isset($transData->locationName)) {
                    $transaction->location_name = $transData->locationName;
                }
                
                if (isset($transData->locationCity)) {
                    $transaction->location_city = $transData->locationCity;
                }
                
                if (isset($transData->locationState)) {
                    $transaction->location_state = $transData->locationState;
                }
                
                if (isset($transData->locationCountry)) {
                    $transaction->location_country = $transData->locationCountry;
                }
                
                // Информация о продукте и ценах
                if (isset($transData->lineItems) && is_array($transData->lineItems) && !empty($transData->lineItems)) {
                    $lineItem = $transData->lineItems[0];
                    
                    $transaction->product_code = $lineItem->category ?? null;
                    $transaction->product_description = $lineItem->description ?? null;
                    $transaction->quantity = $lineItem->quantity ?? null;
                    $transaction->retail_price = $lineItem->retailPPU ?? null;
                    $transaction->discounted_price = $lineItem->ppu ?? null;
                    
                    // Расчет сумм
                    $transaction->retail_amount = $lineItem->retailPPU * $lineItem->quantity;
                    $transaction->funded_amount = $lineItem->ppu * $lineItem->quantity;
                    
                    // Расчет цены для клиента на основе скидки клиента
                    if ($client->rebate_type == Client::REBATE_TYPE_FIXED && $client->rebate_value) {
                        // Фиксированная скидка (например, $0.15 за галлон)
                        $clientDiscountedPrice = $lineItem->retailPPU - $client->rebate_value;
                        $transaction->client_price = max(0, $clientDiscountedPrice);
                        $transaction->client_amount = $transaction->client_price * $lineItem->quantity;
                    } elseif ($client->rebate_type == Client::REBATE_TYPE_PERCENTAGE && $client->rebate_value) {
                        // Процентная скидка (например, 5% от розничной цены)
                        $discountAmount = $lineItem->retailPPU * ($client->rebate_value / 100);
                        $transaction->client_price = $lineItem->retailPPU - $discountAmount;
                        $transaction->client_amount = $transaction->client_price * $lineItem->quantity;
                    } else {
                        // Если скидка не настроена, используем розничную цену
                        $transaction->client_price = $lineItem->retailPPU;
                        $transaction->client_amount = $transaction->retail_amount;
                    }
                }
                
                // Дополнительная информация
                if (isset($transData->infos) && is_array($transData->infos)) {
                    foreach ($transData->infos as $info) {
                        if ($info->type == 'DRID') {
                            $transaction->driver_id = $info->value;
                        } elseif ($info->type == 'ODRD') {
                            $transaction->odometer = intval($info->value);
                        }
                    }
                }
                
                $transaction->invoice_number = $transData->invoice ?? null;
                $transaction->auth_code = $transData->authCode ?? null;
                
                // Сохраняем транзакцию
                if ($transaction->save()) {
                    $imported++;
                } else {
                    $skipped++;
                    Yii::error('Ошибка импорта транзакции: ' . json_encode($transaction->errors), 'wex-api');
                }
            }
            
            return [
                'success' => true,
                'message' => "Импорт завершен. Импортировано: {$imported}, пропущено: {$skipped}",
                'count' => $imported,
                'total' => $total,
                'skipped' => $skipped,
            ];
            
        } catch (\Exception $e) {
            Yii::error('Ошибка импорта транзакций: ' . $e->getMessage(), 'wex-api');
            
            return [
                'success' => false,
                'message' => 'Ошибка импорта транзакций: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Синхронизирует дочерних клиентов (child carriers) из WEX API.
     * @return Response
     */
    public function actionSyncChildCarriers()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            // Логин в API
            $this->wexApiService->login();
            
            // Получаем дочерних клиентов
            $carriers = $this->wexApiService->getChildCarriers();
            
            // Разлогиниваемся
            $this->wexApiService->logout();
            
            // Если клиентов нет
            if (empty($carriers)) {
                return [
                    'success' => true,
                    'message' => 'Дочерних клиентов не найдено',
                    'count' => 0,
                ];
            }
            
            // Счетчики
            $total = count($carriers);
            $imported = 0;
            $updated = 0;
            
            foreach ($carriers as $carrierData) {
                // Проверяем, существует ли клиент
                $client = Client::findOne(['carrier_id' => $carrierData->carrierId]);
                
                if (!$client) {
                    // Создаем нового клиента
                    $client = new Client();
                    $client->carrier_id = $carrierData->carrierId;
                    $client->company_name = $carrierData->name;
                    $client->contact_name = $carrierData->contactName ?? 'Не указано';
                    $client->email = $carrierData->email ?? 'unknown@example.com';
                    $client->phone = $carrierData->phone ?? '0000000000';
                    $client->address = $carrierData->address ?? null;
                    $client->city = $carrierData->city ?? null;
                    $client->state = $carrierData->state ?? null;
                    $client->zip = $carrierData->zip ?? null;
                    $client->country = $carrierData->country ?? 'USA';
                    
                    if ($client->save()) {
                        $imported++;
                    }
                } else {
                    // Обновляем существующего клиента
                    $client->company_name = $carrierData->name;
                    
                    if (isset($carrierData->contactName)) {
                        $client->contact_name = $carrierData->contactName;
                    }
                    
                    if (isset($carrierData->email)) {
                        $client->email = $carrierData->email;
                    }
                    
                    if (isset($carrierData->phone)) {
                        $client->phone = $carrierData->phone;
                    }
                    
                    if (isset($carrierData->address)) {
                        $client->address = $carrierData->address;
                    }
                    
                    if (isset($carrierData->city)) {
                        $client->city = $carrierData->city;
                    }
                    
                    if (isset($carrierData->state)) {
                        $client->state = $carrierData->state;
                    }
                    
                    if (isset($carrierData->zip)) {
                        $client->zip = $carrierData->zip;
                    }
                    
                    if (isset($carrierData->country)) {
                        $client->country = $carrierData->country;
                    }
                    
                    if ($client->save()) {
                        $updated++;
                    }
                }
            }
            
            return [
                'success' => true,
                'message' => "Синхронизация завершена. Добавлено: {$imported}, обновлено: {$updated}",
                'count' => $imported + $updated,
                'total' => $total,
                'imported' => $imported,
                'updated' => $updated,
            ];
            
        } catch (\Exception $e) {
            Yii::error('Ошибка синхронизации клиентов: ' . $e->getMessage(), 'wex-api');
            
            return [
                'success' => false,
                'message' => 'Ошибка синхронизации клиентов: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Создает заказ карт в WEX API.
     * @return Response
     */
    public function actionCreateCardOrder()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $model = new \app\models\forms\CardOrderForm();
        
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            try {
                // Логин в API
                $this->wexApiService->login();
                
                // Подготовка данных для заказа
                $orderData = [
                    'policyNumber' => $model->policyNumber,
                    'orderType' => $model->orderType,
                    'cardStyle' => $model->cardStyle,
                    'orderQty' => $model->quantity,
                    'embossedName' => $model->embossedName,
                    'shipToFirst' => $model->shipToFirst,
                    'shipToLast' => $model->shipToLast,
                    'shipToAddress1' => $model->shipToAddress1,
                    'shipToAddress2' => $model->shipToAddress2,
                    'shipToCity' => $model->shipToCity,
                    'shipToState' => $model->shipToState,
                    'shipToZip' => $model->shipToZip,
                    'shipToCountry' => $model->shipToCountry,
                    'shippingMethod' => $model->shippingMethod,
                    'rushProcessing' => $model->rushProcessing ? 'Y' : 'N',
                    'cardCarrier' => $model->cardCarrier ? 'Y' : 'N',
                    'props' => $model->prepareProps(),
                    'cards' => $model->prepareCards(),
                ];
                
                // Создаем заказ карт
                $result = $this->wexApiService->createAndSubmitOrder($orderData);
                
                // Разлогиниваемся
                $this->wexApiService->logout();
                
                // Сохраняем заказ в нашей БД
                $cardOrder = new \app\models\CardOrder();
                $cardOrder->order_id = $result->cardOrderID;
                $cardOrder->client_id = $model->clientId;
                $cardOrder->policy_id = $model->policyId;
                $cardOrder->card_style = $model->cardStyle;
                $cardOrder->quantity = $model->quantity;
                $cardOrder->embossed_name = $model->embossedName;
                $cardOrder->shipping_method = $model->shippingMethod;
                $cardOrder->rush_processing = $model->rushProcessing;
                $cardOrder->status = 'created';
                
                if ($cardOrder->save()) {
                    return [
                        'success' => true,
                        'message' => 'Заказ карт успешно создан. ID заказа: ' . $result->cardOrderID,
                        'orderID' => $result->cardOrderID,
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Ошибка сохранения заказа в БД: ' . json_encode($cardOrder->errors),
                    ];
                }
                
            } catch (\Exception $e) {
                Yii::error('Ошибка создания заказа карт: ' . $e->getMessage(), 'wex-api');
                
                return [
                    'success' => false,
                    'message' => 'Ошибка создания заказа карт: ' . $e->getMessage(),
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'Ошибка валидации данных заказа: ' . json_encode($model->errors),
            ];
        }
    }
}