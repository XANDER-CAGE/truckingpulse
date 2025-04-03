<?php

namespace app\modules\api\controllers;

use app\models\Invoice;
use app\models\Payment;
use yii\data\ActiveDataProvider;
use Yii;

class InvoiceController extends BaseController
{
    public $modelClass = Invoice::class;

    /**
     * Список счетов
     * 
     * @return ActiveDataProvider
     */
    public function actionIndex()
    {
        $query = Invoice::find();

        // Фильтры
        $clientId = Yii::$app->request->get('client_id');
        $status = Yii::$app->request->get('status');
        $startDate = Yii::$app->request->get('start_date');
        $endDate = Yii::$app->request->get('end_date');

        if ($clientId) {
            $query->andWhere(['client_id' => $clientId]);
        }

        if ($status) {
            $query->andWhere(['status' => $status]);
        }

        if ($startDate) {
            $query->andWhere(['>=', 'created_at', strtotime($startDate)]);
        }

        if ($endDate) {
            $query->andWhere(['<=', 'created_at', strtotime($endDate)]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC]
            ],
            'pagination' => [
                'pageSize' => 20,
            ],
            'expand' => ['client', 'invoiceItems']
        ]);
    }

    /**
     * Просмотр детализации счета
     * 
     * @param int $id ID счета
     * @return Invoice
     */
    public function actionView($id)
    {
        $invoice = Invoice::findOne($id);
        
        if (!$invoice) {
            throw new \yii\web\NotFoundHttpException('Счет не найден');
        }

        // Загружаем связанные данные
        $invoice->populateRelation('invoiceItems', $invoice->invoiceItems);
        $invoice->populateRelation('client', $invoice->client);
        $invoice->populateRelation('payments', $invoice->payments);
        
        return $invoice;
    }

    /**
     * Создание счета
     * 
     * @return Invoice
     */
    public function actionCreate()
    {
        $billingService = Yii::$app->get('billingService');
        
        $clientId = Yii::$app->request->post('client_id');
        $startDate = Yii::$app->request->post('start_date');
        $endDate = Yii::$app->request->post('end_date');

        $client = \app\models\Client::findOne($clientId);
        
        if (!$client) {
            throw new \yii\web\BadRequestHttpException('Клиент не найден');
        }

        $invoice = $billingService->generateInvoice(
            $client, 
            strtotime($startDate), 
            strtotime($endDate)
        );

        if (!$invoice) {
            throw new \yii\web\ServerErrorHttpException('Не удалось создать счет');
        }

        return $invoice;
    }

    /**
     * Оплата счета
     * 
     * @param int $id ID счета
     * @return Payment
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionPay($id)
    {
        $invoice = Invoice::findOne($id);

        if (!$invoice) {
            throw new \yii\web\NotFoundHttpException('Счет не найден');
        }

        // Проверяем статус счета
        if ($invoice->status === Invoice::STATUS_PAID) {
            throw new \yii\web\BadRequestHttpException('Счет уже оплачен');
        }

        $paymentMethod = Yii::$app->request->post('payment_method', Payment::METHOD_ONLINE);
        $paymentAmount = Yii::$app->request->post('amount', $invoice->total);
        $notes = Yii::$app->request->post('notes', '');

        // Проверяем сумму платежа
        if ($paymentAmount > $invoice->total) {
            throw new \yii\web\BadRequestHttpException('Сумма платежа превышает сумму счета');
        }

        // Создаем платеж
        $payment = new Payment();
        $payment->invoice_id = $invoice->id;
        $payment->client_id = $invoice->client_id;
        $payment->amount = $paymentAmount;
        $payment->payment_method = $paymentMethod;
        $payment->payment_date = time();
        $payment->notes = $notes;
        $payment->status = Payment::STATUS_COMPLETED;

        if (!$payment->save()) {
            throw new \yii\web\ServerErrorHttpException('Не удалось создать платеж: ' . 
                json_encode($payment->errors));
        }

        return $payment;
    }

    /**
     * Отмена счета
     * 
     * @param int $id ID счета
     * @return Invoice
     */
    public function actionCancel($id)
    {
        $invoice = Invoice::findOne($id);

        if (!$invoice) {
            throw new \yii\web\NotFoundHttpException('Счет не найден');
        }

        // Проверяем возможность отмены
        if ($invoice->status === Invoice::STATUS_PAID) {
            throw new \yii\web\BadRequestHttpException('Оплаченный счет нельзя отменить');
        }

        $invoice->status = Invoice::STATUS_CANCELED;
        
        if (!$invoice->save()) {
            throw new \yii\web\ServerErrorHttpException('Не удалось отменить счет');
        }

        return $invoice;
    }

    /**
     * Получение платежей по счету
     * 
     * @param int $id ID счета
     * @return ActiveDataProvider
     */
    public function actionPayments($id)
    {
        $invoice = Invoice::findOne($id);

        if (!$invoice) {
            throw new \yii\web\NotFoundHttpException('Счет не найден');
        }

        $query = Payment::find()->where(['invoice_id' => $id]);

        return new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['payment_date' => SORT_DESC]
            ],
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);
    }
}