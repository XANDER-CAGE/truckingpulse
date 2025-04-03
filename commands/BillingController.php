<?php

namespace app\commands;

use app\services\BillingService;
use yii\console\Controller;
use yii\console\ExitCode;
use Yii;

class BillingController extends Controller
{
    /**
     * Генерация счетов за прошлый месяц
     * 
     * @param string|null $startDate Начальная дата в формате Y-m-d
     * @param string|null $endDate Конечная дата в формате Y-m-d
     * @return int
     */
    public function actionGenerateMonthlyInvoices($startDate = null, $endDate = null)
    {
        $this->stdout("Начало генерации счетов...\n");

        // Преобразуем даты, если они не указаны
        $start = $startDate 
            ? strtotime($startDate) 
            : strtotime('first day of last month');
        
        $end = $endDate 
            ? strtotime($endDate) 
            : strtotime('last day of last month');

        /** @var BillingService $billingService */
        $billingService = Yii::$app->get('billingService');

        try {
            $stats = $billingService->generateInvoicesForAllClients($start, $end);

            $this->stdout("Отчет о генерации счетов:\n");
            $this->stdout("Всего клиентов: {$stats['total_clients']}\n");
            $this->stdout("Сгенерировано счетов: {$stats['invoices_generated']}\n");
            $this->stdout("Общая сумма: $" . number_format($stats['total_amount'], 2) . "\n");

            if (!empty($stats['errors'])) {
                $this->stderr("Ошибки при генерации:\n");
                foreach ($stats['errors'] as $error) {
                    $this->stderr("Клиент ID {$error['client_id']}: {$error['error']}\n");
                }
            }

            return ExitCode::OK;

        } catch (\Exception $e) {
            $this->stderr("Ошибка: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Отправка счетов по Email
     * 
     * @param string|null $invoiceDate Дата счетов для отправки
     * @return int
     */
    public function actionSendInvoices($invoiceDate = null)
    {
        $this->stdout("Начало отправки счетов...\n");

        // Получаем неотправленные счета за указанный период
        $date = $invoiceDate 
            ? strtotime($invoiceDate) 
            : strtotime('last month');

        $invoices = \app\models\Invoice::find()
            ->where([
                'status' => \app\models\Invoice::STATUS_DRAFT,
                'AND', 
                ['>=', 'created_at', strtotime('first day of', $date)],
                ['<=', 'created_at', strtotime('last day of', $date)]
            ])
            ->all();

        $sentCount = 0;
        $errorCount = 0;

        foreach ($invoices as $invoice) {
            try {
                $this->sendInvoiceEmail($invoice);
                
                $invoice->status = \app\models\Invoice::STATUS_SENT;
                $invoice->save();

                $sentCount++;
                $this->stdout("Счет {$invoice->invoice_number} отправлен клиенту {$invoice->client->company_name}\n");

            } catch (\Exception $e) {
                $errorCount++;
                $this->stderr("Ошибка отправки счета {$invoice->invoice_number}: " . $e->getMessage() . "\n");
            }
        }

        $this->stdout("Отправлено счетов: {$sentCount}\n");
        $this->stdout("Ошибок: {$errorCount}\n");

        return ExitCode::OK;
    }

    /**
     * Отправка счета по электронной почте
     * 
     * @param \app\models\Invoice $invoice
     */
    private function sendInvoiceEmail($invoice)
    {
        if (!$invoice->client->email) {
            throw new \Exception("У клиента {$invoice->client->company_name} не указан email");
        }

        Yii::$app->mailer->compose('invoice', ['invoice' => $invoice])
            ->setFrom(Yii::$app->params['senderEmail'])
            ->setTo($invoice->client->email)
            ->setSubject("Счет {$invoice->invoice_number} от " . date('d.m.Y', $invoice->created_at))
            ->send();
    }

    /**
     * Проверка просроченных счетов
     * 
     * @return int
     */
    public function actionCheckOverdueInvoices()
    {
        $this->stdout("Проверка просроченных счетов...\n");

        $overdueInvoices = \app\models\Invoice::find()
            ->where([
                'status' => \app\models\Invoice::STATUS_SENT
            ])
            ->andWhere(['<', 'due_date', time()])
            ->all();

        $overdueCount = 0;

        foreach ($overdueInvoices as $invoice) {
            $invoice->status = \app\models\Invoice::STATUS_OVERDUE;
            
            if ($invoice->save()) {
                $overdueCount++;
                
                // Отправляем уведомление о просрочке
                $this->sendOverdueNotification($invoice);
            }
        }

        $this->stdout("Обновлено просроченных счетов: {$overdueCount}\n");

        return ExitCode::OK;
    }

    /**
     * Отправка уведомления о просроченном счете
     * 
     * @param \app\models\Invoice $invoice
     */
    private function sendOverdueNotification($invoice)
    {
        if (!$invoice->client->email) {
            return;
        }

        Yii::$app->mailer->compose('overdue-invoice', ['invoice' => $invoice])
            ->setFrom(Yii::$app->params['senderEmail'])
            ->setTo($invoice->client->email)
            ->setSubject("Просрочен счет {$invoice->invoice_number}")
            ->send();
    }
}