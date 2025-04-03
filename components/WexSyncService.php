<?php

namespace app\services;

use app\models\Client;
use app\models\Card;
use app\models\Transaction;
use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;

class WexSyncService extends Component
{
    private $wexApiService;

    public function init()
    {
        parent::init();
        $this->wexApiService = Yii::$app->wexApi;
    }

    /**
     * Синхронизация дочерних перевозчиков
     * 
     * @return array Статистика синхронизации
     */
    public function syncChildCarriers()
    {
        $startTime = microtime(true);
        $stats = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => []
        ];

        try {
            $this->wexApiService->login();
            $carriers = $this->wexApiService->getChildCarriers();
            $this->wexApiService->logout();

            foreach ($carriers as $carrierData) {
                $stats['total']++;

                $client = Client::findOne(['carrier_id' => $carrierData->carrierId]);

                if (!$client) {
                    $client = new Client();
                    $client->carrier_id = $carrierData->carrierId;
                    $stats['created']++;
                } else {
                    $stats['updated']++;
                }

                $client->company_name = $carrierData->name;
                $client->contact_name = $carrierData->contactName ?? 'Не указано';
                $client->email = $carrierData->email ?? 'unknown@example.com';
                $client->phone = $carrierData->phone ?? '0000000000';
                $client->address = $carrierData->address ?? null;
                $client->city = $carrierData->city ?? null;
                $client->state = $carrierData->state ?? null;
                $client->zip = $carrierData->zip ?? null;
                $client->country = $carrierData->country ?? 'USA';

                if (!$client->save()) {
                    $stats['errors'][] = [
                        'carrierId' => $carrierData->carrierId,
                        'errors' => $client->errors
                    ];
                }
            }

        } catch (\Exception $e) {
            $stats['errors'][] = $e->getMessage();
            Yii::error('Ошибка синхронизации перевозчиков: ' . $e->getMessage(), 'wex-sync');
        }

        $stats['duration'] = round(microtime(true) - $startTime, 2);
        return $stats;
    }

    /**
     * Синхронизация транзакций
     * 
     * @param int $minutes Количество минут для импорта
     * @return array Статистика синхронизации
     */
    public function syncTransactions($minutes = 60)
    {
        $startTime = microtime(true);
        $stats = [
            'total' => 0,
            'imported' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        try {
            $this->wexApiService->login();

            $endDate = new \DateTime();
            $begDate = (clone $endDate)->modify("-{$minutes} minutes");

            $transactions = $this->wexApiService->getChildTransactions(
                $begDate->format('Y-m-d\TH:i:s'), 
                $endDate->format('Y-m-d\TH:i:s')
            );

            $this->wexApiService->logout();

            foreach ($transactions as $transData) {
                $stats['total']++;

                // Проверка существования транзакции
                $existingTransaction = Transaction::findOne(['transaction_id' => $transData->transactionId]);
                if ($existingTransaction) {
                    $stats['skipped']++;
                    continue;
                }

                // Поиск или создание карты
                $card = Card::findOne(['card_number' => $transData->cardNumber]);
                if (!$card) {
                    $card = new Card();
                    $card->card_number = $transData->cardNumber;
                    $card->status = Card::STATUS_ACTIVE;
                }

                // Поиск или создание клиента
                $client = Client::findOne(['carrier_id' => $transData->carrierId]);
                if (!$client) {
                    $client = new Client();
                    $client->carrier_id = $transData->carrierId;
                    $client->company_name = "Клиент #" . $transData->carrierId;
                    $client->contact_name = "Неизвестно";
                    $client->email = "unknown@example.com";
                    $client->save();
                }

                $card->client_id = $client->id;
                $card->save();

                // Создание транзакции
                $transaction = new Transaction();
                $transaction->transaction_id = $transData->transactionId;
                $transaction->card_id = $card->id;
                $transaction->client_id = $client->id;
                $transaction->transaction_date = strtotime($transData->transactionDate);

                // Заполнение остальных полей
                $this->fillTransactionDetails($transaction, $transData);

                if ($transaction->save()) {
                    $stats['imported']++;
                } else {
                    $stats['errors'][] = [
                        'transactionId' => $transData->transactionId,
                        'errors' => $transaction->errors
                    ];
                }
            }

        } catch (\Exception $e) {
            $stats['errors'][] = $e->getMessage();
            Yii::error('Ошибка синхронизации транзакций: ' . $e->getMessage(), 'wex-sync');
        }

        $stats['duration'] = round(microtime(true) - $startTime, 2);
        return $stats;
    }

    /**
     * Заполнение деталей транзакции
     * 
     * @param Transaction $transaction
     * @param object $transData
     */
    private function fillTransactionDetails(Transaction &$transaction, $transData)
    {
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

        // Информация о продукте
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
        }

        // Дополнительная информация
        $transaction->invoice_number = $transData->invoice ?? null;
        $transaction->auth_code = $transData->authCode ?? null;
    }
}