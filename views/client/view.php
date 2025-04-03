<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;
use yii\data\ArrayDataProvider;

/* @var $this yii\web\View */
/* @var $model app\models\Client */
/* @var $cards app\models\Card[] */
/* @var $transactions app\models\Transaction[] */
/* @var $totalAmount float */
/* @var $totalProfit float */

$this->title = $model->company_name;
$this->params['breadcrumbs'][] = ['label' => 'Клиенты', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="client-view">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <div>
            <?= Html::a('<i class="bi bi-pencil"></i> Редактировать', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('<i class="bi bi-percent"></i> Установить скидку', ['set-rebate', 'id' => $model->id], ['class' => 'btn btn-success']) ?>
            <?= Html::a('<i class="bi bi-trash"></i> Удалить', ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => 'Вы уверены, что хотите удалить этого клиента?',
                    'method' => 'post',
                ],
            ]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Основная информация</h5>
                </div>
                <div class="card-body">
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            'carrier_id',
                            'company_name',
                            'contact_name',
                            'email:email',
                            'phone',
                            'fullAddress',
                            [
                                'attribute' => 'status',
                                'value' => $model->getStatusText(),
                                'format' => 'raw',
                                'contentOptions' => function ($model) {
                                    $class = '';
                                    if ($model->status === \app\models\Client::STATUS_ACTIVE) {
                                        $class = 'text-success';
                                    } elseif ($model->status === \app\models\Client::STATUS_INACTIVE) {
                                        $class = 'text-muted';
                                    } elseif ($model->status === \app\models\Client::STATUS_BLOCKED) {
                                        $class = 'text-danger';
                                    }
                                    return ['class' => $class];
                                },
                            ],
                            [
                                'attribute' => 'created_at',
                                'value' => Yii::$app->formatter->asDatetime($model->created_at),
                            ],
                            [
                                'attribute' => 'updated_at',
                                'value' => Yii::$app->formatter->asDatetime($model->updated_at),
                            ],
                        ],
                    ]) ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Скидка и финансы</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6>Текущая скидка</h6>
                        <div class="d-flex align-items-center">
                            <div class="display-6"><?= $model->getFormattedRebate() ?></div>
                            <?= Html::a('<i class="bi bi-pencil"></i>', ['set-rebate', 'id' => $model->id], [
                                'class' => 'btn btn-sm btn-outline-primary ms-3',
                                'title' => 'Изменить скидку',
                            ]) ?>
                        </div>
                        <small class="text-muted">Тип скидки: <?= $model->getRebateTypeText() ?></small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Общий объем за 30 дней</h6>
                                    <div class="display-6"><?= Yii::$app->formatter->asCurrency($totalAmount) ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Прибыль за 30 дней</h6>
                                    <div class="display-6"><?= Yii::$app->formatter->asCurrency($totalProfit) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Карты клиента</h5>
                    <?= Html::a('<i class="bi bi-plus"></i> Заказать карты', ['/card/order', 'client_id' => $model->id], ['class' => 'btn btn-sm btn-primary']) ?>
                </div>
                <div class="card-body">
                    <?php if (empty($cards)): ?>
                        <div class="alert alert-info">
                            У клиента еще нет карт. <?= Html::a('Заказать карты', ['/card/order', 'client_id' => $model->id]) ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Номер карты</th>
                                        <th>Статус</th>
                                        <th>Водитель</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cards as $card): ?>
                                    <tr>
                                        <td><?= Html::a($card->getMaskedCardNumber(), ['/card/view', 'id' => $card->id]) ?></td>
                                        <td>
                                            <?php
                                            $statusClass = '';
                                            switch ($card->status) {
                                                case \app\models\Card::STATUS_ACTIVE:
                                                    $statusClass = 'badge bg-success';
                                                    break;
                                                case \app\models\Card::STATUS_INACTIVE:
                                                    $statusClass = 'badge bg-secondary';
                                                    break;
                                                case \app\models\Card::STATUS_HOLD:
                                                    $statusClass = 'badge bg-warning';
                                                    break;
                                                case \app\models\Card::STATUS_DELETED:
                                                    $statusClass = 'badge bg-danger';
                                                    break;
                                            }
                                            ?>
                                            <span class="<?= $statusClass ?>"><?= $card->getStatusText() ?></span>
                                        </td>
                                        <td><?= $card->driver_name ?: $card->driver_id ?: '—' ?></td>
                                        <td>
                                            <?= Html::a('<i class="bi bi-eye"></i>', ['/card/view', 'id' => $card->id], ['class' => 'btn btn-sm btn-outline-primary']) ?>
                                            <?= Html::a('<i class="bi bi-pencil"></i>', ['/card/update', 'id' => $card->id], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?= Html::a('Показать все карты', ['cards', 'id' => $model->id], ['class' => 'btn btn-outline-primary mt-2']) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Последние транзакции</h5>
                    <?= Html::a('Показать все', ['transactions', 'id' => $model->id], ['class' => 'btn btn-sm btn-primary']) ?>
                </div>
                <div class="card-body">
                    <?php if (empty($transactions)): ?>
                        <div class="alert alert-info">
                            У клиента еще нет транзакций.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Дата</th>
                                        <th>Сумма</th>
                                        <th>Локация</th>
                                        <th>Продукт</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td><?= Yii::$app->formatter->asDatetime($transaction->transaction_date) ?></td>
                                        <td><?= Yii::$app->formatter->asCurrency($transaction->client_amount) ?></td>
                                        <td><?= $transaction->location_name ?>, <?= $transaction->location_state ?></td>
                                        <td><?= $transaction->product_description ?: $transaction->product_code ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?= Html::a('Показать все транзакции', ['transactions', 'id' => $model->id], ['class' => 'btn btn-outline-primary mt-2']) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>