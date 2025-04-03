<?php

namespace app\controllers;

use app\models\Card;
use app\models\Client;
use app\models\Transaction;
use app\models\search\ClientSearch;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * ClientController реализует CRUD-операции для модели Client.
 */
class ClientController extends Controller
{
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
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Список всех клиентов.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ClientSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Просмотр одного клиента.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        
        // Получаем все карты клиента
        $cards = Card::find()->where(['client_id' => $id])->all();
        
        // Получаем последние 10 транзакций клиента
        $transactions = Transaction::find()
            ->where(['client_id' => $id])
            ->orderBy(['transaction_date' => SORT_DESC])
            ->limit(10)
            ->all();
        
        // Считаем общую сумму транзакций за последние 30 дней
        $thirtyDaysAgo = strtotime('-30 days');
        $totalAmount = Transaction::find()
            ->where(['client_id' => $id])
            ->andWhere(['>=', 'transaction_date', $thirtyDaysAgo])
            ->sum('client_amount');
        
        // Считаем общую прибыль за последние 30 дней
        $totalProfit = Transaction::find()
            ->where(['client_id' => $id])
            ->andWhere(['>=', 'transaction_date', $thirtyDaysAgo])
            ->sum('our_profit');
        
        return $this->render('view', [
            'model' => $model,
            'cards' => $cards,
            'transactions' => $transactions,
            'totalAmount' => $totalAmount,
            'totalProfit' => $totalProfit,
        ]);
    }

    /**
     * Создание нового клиента.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Client();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Редактирование существующего клиента.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }
    
    /**
     * Установка скидки для клиента.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionSetRebate($id)
    {
        $model = $this->findModel($id);
        
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Скидка успешно установлена.');
            return $this->redirect(['view', 'id' => $model->id]);
        }
        
        return $this->render('set-rebate', [
            'model' => $model,
        ]);
    }

    /**
     * Удаление клиента.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     * @throws \Throwable in case delete failed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }
    
    /**
     * Отображение транзакций клиента.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionTransactions($id)
    {
        $model = $this->findModel($id);
        
        // Получаем параметры фильтрации
        $startDate = Yii::$app->request->get('startDate', date('Y-m-d', strtotime('-30 days')));
        $endDate = Yii::$app->request->get('endDate', date('Y-m-d'));
        
        // Преобразуем даты в timestamps
        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate . ' 23:59:59');
        
        // Получаем транзакции
        $transactions = Transaction::find()
            ->where(['client_id' => $id])
            ->andWhere(['>=', 'transaction_date', $startTimestamp])
            ->andWhere(['<=', 'transaction_date', $endTimestamp])
            ->orderBy(['transaction_date' => SORT_DESC])
            ->all();
        
        // Считаем общие суммы
        $totalRetailAmount = 0;
        $totalClientAmount = 0;
        $totalProfit = 0;
        
        foreach ($transactions as $transaction) {
            $totalRetailAmount += $transaction->retail_amount;
            $totalClientAmount += $transaction->client_amount;
            $totalProfit += $transaction->our_profit;
        }
        
        return $this->render('transactions', [
            'model' => $model,
            'transactions' => $transactions,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalRetailAmount' => $totalRetailAmount,
            'totalClientAmount' => $totalClientAmount,
            'totalProfit' => $totalProfit,
        ]);
    }
    
    /**
     * Отображение карт клиента.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionCards($id)
    {
        $model = $this->findModel($id);
        
        // Получаем все карты клиента
        $cards = Card::find()->where(['client_id' => $id])->all();
        
        return $this->render('cards', [
            'model' => $model,
            'cards' => $cards,
        ]);
    }

    /**
     * Finds the Client model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Client the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Client::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Запрашиваемая страница не существует.');
    }
}