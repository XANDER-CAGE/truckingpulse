<?php

namespace app\commands;

use app\models\Card;
use app\models\Client;
use app\models\Transaction;
use app\services\WexApiService;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * ImportController реализует консольные команды для импорта данных из WEX API.
 */
class ImportController extends Controller
{
    /**
     * Импортирует транзакции из WEX API.
     * 
     * @param int $minutes Период в минутах (по умолчанию - 60 минут)
     * @return int Код завершения
     */
    public function actionTransactions($minutes = 60)
    {
        $this->stdout("Импорт транзакций за последние $minutes минут...\n");
        
        $maxMinutes = Yii::$app->params['import']['maxMinutes'] ?? 1440;
        if ($minutes > $maxMinutes) {
            $this->stderr("Ошибка: период не может быть больше $maxMinutes минут.\n");
            return ExitCode::DATAERR;
        }
        
        $endDate = new \DateTime();
        $begDate = (clone $endDate)->modify("-{$minutes} minutes");
        
        $begDateFormatted = $begDate->format('Y-m-d\TH:i:s');
        $endDateFormatted = $endDate->format('Y-m-d\TH:i:s');
        
        $this->stdout("Период: с $begDateFormatted по $endDateFormatted\n");
        
        try {
            // Инициализируем сервис WEX API
            $wexApiService = new WexApiService([
                'username' => Yii::$app->params['wex']['username'],
                'password' => Yii::$app->params['wex']['password'],
                'isProduction' => Yii::$app->params['wex']['isProduction'],
            ]);
            
            // Логин в API
            $this->stdout("Вход в WEX API...\n");
            $wexApiService->login();
            
            // Получаем транзакции
            $this->stdout("Запрос транзакций...\n");
            $transactions = $wexApiService->getChildTransactions($begDateFormatted, $endDateFormatted);
            
            // Разлогиниваемся
            $wexApiService->logout();
            
            // Если транзакций нет
            if (empty($transactions)) {
                $this->stdout("Новых транзакций не найдено\n");
                return ExitCode::OK;
            }
            
            // Счетчики
            $total = count($transactions);
            $imported = 0;
            $skipped = 0;
            
            $this->stdout("Найдено транзакций: $total\n");
            
            foreach ($transactions as $index => $transData) {
                $this->stdout("\rОбработка транзакции " . ($index + 1) . " из $total...");
                
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
                    
                    if (!$card->save()) {
                        $this->stderr("\nОшибка сохранения карты: " . json_encode($card->errors) . "\n");
                        continue;
                    }
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
                    
                    if (!$client->save()) {
                        $this->stderr("\nОшибка сохранения клиента: " . json_encode($client->errors) . "\n");
                        continue;
                    }
                    
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
                    $this->stderr("\nОшибка сохранения транзакции: " . json_encode($transaction->errors) . "\n");
                }
            }
            
            $this->stdout("\nИмпорт завершен. Импортировано: $imported, пропущено: $skipped\n");
            return ExitCode::OK;
            
        } catch (\Exception $e) {
            $this->stderr("Ошибка импорта транзакций: " . $e->getMessage() . "\n");
            Yii::error('Ошибка импорта транзакций: ' . $e->getMessage(), 'import');
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
    
    /**
     * Синхронизирует дочерних клиентов (child carriers) из WEX API.
     * 
     * @return int Код завершения
     */
    public function actionSyncClients()
    {
        $this->stdout("Синхронизация клиентов из WEX API...\n");
        
        try {
            // Инициализируем сервис WEX API
            $wexApiService = new WexApiService([
                'username' => Yii::$app->params['wex']['username'],
                'password' => Yii::$app->params['wex']['password'],
                'isProduction' => Yii::$app->params['wex']['isProduction'],
            ]);
            
            // Логин в API
            $this->stdout("Вход в WEX API...\n");
            $wexApiService->login();
            
            // Получаем дочерних клиентов
            $this->stdout("Запрос клиентов...\n");
            $carriers = $wexApiService->getChildCarriers();
            
            // Разлогиниваемся
            $wexApiService->logout();
            
            // Если клиентов нет
            if (empty($carriers)) {
                $this->stdout("Дочерних клиентов не найдено\n");
                return ExitCode::OK;
            }
            
            // Счетчики
            $total = count($carriers);
            $imported = 0;
            $updated = 0;
            
            $this->stdout("Найдено клиентов: $total\n");
            
            foreach ($carriers as $index => $carrierData) {
                $this->stdout("\rОбработка клиента " . ($index + 1) . " из $total...");
                
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
                    } else {
                        $this->stderr("\nОшибка сохранения нового клиента: " . json_encode($client->errors) . "\n");
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
                    } else {
                        $this->stderr("\nОшибка обновления клиента: " . json_encode($client->errors) . "\n");
                    }
                }
            }
            
            $this->stdout("\nСинхронизация завершена. Добавлено: $imported, обновлено: $updated\n");
            return ExitCode::OK;
            
        } catch (\Exception $e) {
            $this->stderr("Ошибка синхронизации клиентов: " . $e->getMessage() . "\n");
            Yii::error('Ошибка синхронизации клиентов: ' . $e->getMessage(), 'import');
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}