<?php

/* @var $this yii\web\View */

use yii\helpers\Html;
use app\models\Client;
use app\models\Card;
use app\models\Transaction;

$this->title = 'Панель управления';

// Получаем статистику
$activeClients = Client::find()->where(['status' => Client::STATUS_ACTIVE])->count();
$totalCards = Card::find()->count();
$activeCards = Card::find()->where(['status' => Card::STATUS_ACTIVE])->count();

// Считаем транзакции за последние 30 дней
$thirtyDaysAgo = strtotime('-30 days');
$transactionCount = Transaction::find()->where(['>=', 'transaction_date', $thirtyDaysAgo])->count();
$transactionTotal = Transaction::find()->where(['>=', 'transaction_date', $thirtyDaysAgo])->sum('client_amount');
$transactionProfit = Transaction::find()->where(['>=', 'transaction_date', $thirtyDaysAgo])->sum('our_profit');

// Средняя прибыль на галлон
$profitPerGallon = 0;
$totalGallons = Transaction::find()
    ->where(['>=', 'transaction_date', $thirtyDaysAgo])
    ->sum('quantity');

if ($totalGallons > 0) {
    $profitPerGallon = $transactionProfit / $totalGallons;
}

// Получаем 5 последних транзакций
$recentTransactions = Transaction::find()
    ->orderBy(['transaction_date' => SORT_DESC])
    ->limit(5)
    ->all();

?>
<div class="site-index">

    <div class="jumbotron text-center bg-light p-5 mb-4 rounded">
        <h1>Система управления топливными картами WEX</h1>
        <p class="lead">Добро пожаловать в панель управления топливными картами и транзакциями.</p>
    </div>

    <div class="body-content">
        
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="card border-primary mb-3">
                    <div class="card-header bg-primary text-white">Активные клиенты</div>
                    <div class="card-body">
                        <h1 class="card-title"><?= $activeClients ?></h1>
                        <p class="card-text">
                            <?= Html::a('Управление клиентами <i class="bi bi-arrow-right"></i>', ['/client/index'], ['class' => 'btn btn-outline-primary']) ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card border-success mb-3">
                    <div class="card-header bg-success text-white">Активные карты</div>
                    <div class="card-body">
                        <h1 class="card-title"><?= $activeCards ?> / <?= $totalCards ?></h1>
                        <p class="card-text">
                            <?= Html::a('Управление картами <i class="bi bi-arrow-right"></i>', ['/card/index'], ['class' => 'btn btn-outline-success']) ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card border-info mb-3">
                    <div class="card-header bg-info text-white">Транзакции (30 дней)</div>
                    <div class="card-body">
                        <h1 class="card-title"><?= $transactionCount ?></h1>
                        <p class="card-text">
                            <?= Html::a('Просмотр транзакций <i class="bi bi-arrow-right"></i>', ['/transaction/index'], ['class' => 'btn btn-outline-info']) ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card border-danger mb-3">
                    <div class="card-header bg-danger text-white">Прибыль (30 дней)</div>
                    <div class="card-body">
                        <h1 class="card-title"><?= Yii::$app->formatter->asCurrency($transactionProfit) ?></h1>
                        <p class="card-text">
                            <?= Html::a('Отчеты <i class="bi bi-arrow-right"></i>', ['/report/profit-by-client'], ['class' => 'btn btn-outline-danger']) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Последние транзакции</h5>
                        <?= Html::a('Все транзакции', ['/transaction/index'], ['class' => 'btn btn-sm btn-primary']) ?>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Дата</th>
                                        <th>Клиент</th>
                                        <th>Карта</th>
                                        <th>Локация</th>
                                        <th>Сумма</th>
                                        <th>Прибыль</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentTransactions as $transaction): ?>
                                    <tr>
                                        <td><?= Yii::$app->formatter->asDatetime($transaction->transaction_date) ?></td>
                                        <td><?= Html::a(
                                            $transaction->client->company_name, 
                                            ['/client/view', 'id' => $transaction->client_id]
                                        ) ?></td>
                                        <td><?= Html::a(
                                            substr($transaction->card->card_number, -4), 
                                            ['/card/view', 'id' => $transaction->card_id]
                                        ) ?></td>
                                        <td><?= $transaction->location_name ?>, <?= $transaction->location_state ?></td>
                                        <td><?= Yii::$app->formatter->asCurrency($transaction->client_amount) ?></td>
                                        <td><?= Yii::$app->formatter->asCurrency($transaction->our_profit) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($recentTransactions)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Транзакции не найдены</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Статистика за 30 дней</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6>Объем продаж</h6>
                            <h3><?= Yii::$app->formatter->asCurrency($transactionTotal) ?></h3>
                        </div>
                        
                        <div class="mb-3">
                            <h6>Количество галлонов</h6>
                            <h3><?= Yii::$app->formatter->asDecimal($totalGallons, 2) ?></h3>
                        </div>
                        
                        <div class="mb-3">
                            <h6>Средняя прибыль на галлон</h6>
                            <h3><?= Yii::$app->formatter->asCurrency($profitPerGallon) ?></h3>
                        </div>
                        
                        <div class="mb-3">
                            <h6>Процент прибыли</h6>
                            <h3><?= $transactionTotal > 0 
                                ? number_format(($transactionProfit / $transactionTotal) * 100, 2) . '%' 
                                : '0.00%' ?></h3>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Быстрые действия</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?= Html::a('<i class="bi bi-person-plus"></i> Добавить клиента', ['/client/create'], ['class' => 'btn btn-outline-primary']) ?>
                            <?= Html::a('<i class="bi bi-credit-card"></i> Заказать карты', ['/card/order'], ['class' => 'btn btn-outline-success']) ?>
                            <?= Html::a('<i class="bi bi-arrow-repeat"></i> Импорт транзакций', ['/wex-api/index'], ['class' => 'btn btn-outline-info']) ?>
                            <?= Html::a('<i class="bi bi-file-earmark-bar-graph"></i> Сформировать отчет', ['/report/index'], ['class' => 'btn btn-outline-secondary']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>