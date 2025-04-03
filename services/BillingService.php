<?php

namespace app\services;

use app\models\Client;
use app\models\Invoice;
use app\models\InvoiceItem;
use app\models\Transaction;
use Yii;
use yii\base\Component;
use yii\db\Query;

class BillingService extends Component
{
    const SERVICE_FEE_PERCENTAGE = 0.03; // 3% сервисный сбор
    const TAX_RATE = 0.1; // 10% НДС

    /**
     * Генерация счета для клиента
     * 
     * @param Client $client Клиент
     * @param int $startDate Начальная дата периода
     * @param int $endDate Конечная дата периода
     * @return Invoice
     */
    public function generateInvoice(Client $client, $startDate, $endDate)
    {
        // Находим все транзакции клиента за период
        $transactions = Transaction::find()
            ->where(['client_id' => $client->id])
            ->andWhere(['>=', 'transaction_date', $startDate])
            ->andWhere(['<=', 'transaction_date', $endDate])
            ->all();

        if (empty($transactions)) {
            return null;
        }

        $invoice = new Invoice();
        $invoice->client_id = $client->id;
        $invoice->start_date = $startDate;
        $invoice->end_date = $endDate;

        // Группируем транзакции по типам продуктов
        $productGroups = $this->groupTransactionsByProduct($transactions);

        // Рассчитываем суммы
        $fuelAmount = array_sum(array_column($productGroups, 'total'));
        $serviceFee = $fuelAmount * self::SERVICE_FEE_PERCENTAGE;
        $subtotal = $fuelAmount + $serviceFee;
        $tax = $subtotal * self::TAX_RATE;
        $total = $subtotal + $tax;

        $invoice->fuel_amount = $fuelAmount;
        $invoice->service_fee = $serviceFee;
        $invoice->subtotal = $subtotal;
        $invoice->tax = $tax;
        $invoice->total = $total;
        $invoice->status = Invoice::STATUS_DRAFT;
        $invoice->due_date = strtotime('+14 days', $endDate);

        if ($invoice->save()) {
            // Создаем позиции счета
            $this->createInvoiceItems($invoice, $productGroups, $transactions);
            return $invoice;
        }

        return null;
    }

    /**
     * Группировка транзакций по продуктам
     * 
     * @param Transaction[] $transactions
     * @return array
     */
    private function groupTransactionsByProduct($transactions)
    {
        $groups = [];

        foreach ($transactions as $transaction) {
            $productCode = $transaction->product_code ?? 'MISC';
            
            if (!isset($groups[$productCode])) {
                $groups[$productCode] = [
                    'description' => $transaction->product_description ?? 'Прочее',
                    'quantity' => 0,
                    'total' => 0
                ];
            }

            $groups[$productCode]['quantity'] += $transaction->quantity ?? 1;
            $groups[$productCode]['total'] += $transaction->client_amount;
        }

        return $groups;
    }

    /**
     * Создание позиций счета
     * 
     * @param Invoice $invoice
     * @param array $productGroups
     * @param Transaction[] $transactions
     */
    private function createInvoiceItems(Invoice $invoice, $productGroups, $transactions)
    {
        // Добавляем групповые позиции
        foreach ($productGroups as $productCode => $group) {
            $item = new InvoiceItem();
            $item->invoice_id = $invoice->id;
            $item->description = $group['description'];
            $item->quantity = $group['quantity'];
            $item->price = $group['total'] / $group['quantity'];
            $item->amount = $group['total'];
            $item->save();
        }

        // Добавляем сервисный сбор
        $serviceFeeItem = new InvoiceItem();
        $serviceFeeItem->invoice_id = $invoice->id;
        $serviceFeeItem->description = 'Сервисный сбор';
        $serviceFeeItem->quantity = 1;
        $serviceFeeItem->price = $invoice->service_fee;
        $serviceFeeItem->amount = $invoice->service_fee;
        $serviceFeeItem->save();

        // Привязываем транзакции к позициям счета
        foreach ($transactions as $transaction) {
            $invoiceItem = InvoiceItem::find()
                ->where(['invoice_id' => $invoice->id])
                ->andWhere(['description' => $transaction->product_description ?? 'Прочее'])
                ->one();

            if ($invoiceItem) {
                $invoiceItemTransaction = new \app\models\InvoiceItemTransaction();
                $invoiceItemTransaction->invoice_item_id = $invoiceItem->id;
                $invoiceItemTransaction->transaction_id = $transaction->id;
                $invoiceItemTransaction->save();
            }
        }
    }

    /**
     * Генерация счетов для всех клиентов за указанный период
     * 
     * @param int|null $startDate Начальная дата
     * @param int|null $endDate Конечная дата
     * @return array Статистика генерации
     */
    public function generateInvoicesForAllClients($startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? strtotime('first day of last month');
        $endDate = $endDate ?? strtotime('last day of last month');

        $clients = Client::find()->where(['status' => Client::STATUS_ACTIVE])->all();

        $stats = [
            'total_clients' => count($clients),
            'invoices_generated' => 0,
            'total_amount' => 0,
            'errors' => []
        ];

        foreach ($clients as $client) {
            try {
                $invoice = $this->generateInvoice($client, $startDate, $endDate);
                
                if ($invoice) {
                    $stats['invoices_generated']++;
                    $stats['total_amount'] += $invoice->total;
                }
            } catch (\Exception $e) {
                $stats['errors'][] = [
                    'client_id' => $client->id,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $stats;
    }
}