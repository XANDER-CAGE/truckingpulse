<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Client */
/* @var $transactions app\models\Transaction[] */
/* @var $startDate string */
/* @var $endDate string */
/* @var $totalRetailAmount float */
/* @var $totalClientAmount float */
/* @var $totalProfit float */

$this->title = 'Транзакции: ' . $model->company_name;
$this->params['breadcrumbs'][] = ['label' => 'Клиенты', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->company_name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Транзакции';
?>
<div class="client-transactions">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <div>
            <?= Html::a('<i class="bi bi-arrow-left"></i> Назад к клиенту', ['view', 'id' => $model->id], ['class' => 'btn btn-secondary']) ?>
            <?= Html::a('<i class="bi bi-file-excel"></i> Экспорт в Excel', ['export-transactions', 'id' => $model->id, 'startDate' => $startDate, 'endDate' => $endDate], ['class' => 'btn btn-success']) ?>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Фильтр по дате</h5>
        </div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label for="startDate" class="form-label">Начальная дата</label>
                    <input type="date" class="form-control" id="startDate" name="startDate" value="<?= $startDate ?>">
                </div>
                <div class="col-md-4">
                    <label for="endDate" class="form-label">Конечная дата</label>
                    <input type="date" class="form-control" id="endDate" name="endDate" value="<?= $endDate ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Применить</button>
                    <?= Html::a('Сбросить', ['transactions', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
                </div>
            </form>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Розничная сумма</h6>
                    <div class="display-6"><?= Yii::$app->formatter->asCurrency($totalRetailAmount) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Сумма для клиента</h6>
                    <div class="display-6"><?= Yii::$app->formatter->asCurrency($totalClientAmount) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Наша прибыль</h6>
                    <div class="display-6"><?= Yii::$app->formatter->asCurrency($totalProfit) ?></div>
                    <small class="text-muted">
                        Процент прибыли: 
                        <?= $totalRetailAmount > 0 
                            ? number_format(($totalProfit / $totalRetailAmount) * 100, 2) . '%' 
                            : '0.00%' 
                        ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (empty($transactions)): ?>
        <div class="alert alert-info">
            <p>В выбранный период транзакций не найдено.</p>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Транзакции за период <?= Yii::$app->formatter->asDate($startDate) ?> - <?= Yii::$app->formatter->asDate($endDate) ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Карта</th>
                                <th>Локация</th>
                                <th>Продукт</th>
                                <th>Кол-во</th>
                                <th>Розничная сумма</th>
                                <th>Сумма клиента</th>
                                <th>Прибыль</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?= Yii::$app->formatter->asDatetime($transaction->transaction_date) ?></td>
                                <td><?= Html::a(
                                    substr($transaction->card->card_number, -4), 
                                    ['/card/view', 'id' => $transaction->card_id]
                                ) ?></td>
                                <td><?= $transaction->location_name ?>, <?= $transaction->location_state ?></td>
                                <td><?= $transaction->product_description ?: $transaction->product_code ?></td>
                                <td><?= Yii::$app->formatter->asDecimal($transaction->quantity, 2) ?></td>
                                <td><?= Yii::$app->formatter->asCurrency($transaction->retail_amount) ?></td>
                                <td><?= Yii::$app->formatter->asCurrency($transaction->client_amount) ?></td>
                                <td><?= Yii::$app->formatter->asCurrency($transaction->our_profit) ?></td>
                                <td>
                                    <?= Html::a('<i class="bi bi-eye"></i>', ['/transaction/view', 'id' => $transaction->id], [
                                        'class' => 'btn btn-sm btn-outline-primary',
                                        'title' => 'Просмотр',
                                    ]) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>