<?php

use app\models\Client;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel app\models\search\ClientSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Клиенты';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="client-index">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <div>
            <?= Html::a('<i class="bi bi-plus"></i> Добавить клиента', ['create'], ['class' => 'btn btn-success']) ?>
            <?= Html::a('<i class="bi bi-arrow-repeat"></i> Синхронизировать с WEX', ['/wex-api/sync-child-carriers'], [
                'class' => 'btn btn-primary',
                'id' => 'sync-clients-btn',
                'data' => [
                    'method' => 'post',
                    'confirm' => 'Вы уверены, что хотите синхронизировать клиентов с WEX API?',
                ],
            ]) ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <?php Pjax::begin(); ?>
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'tableOptions' => ['class' => 'table table-striped table-bordered'],
                'columns' => [
                    [
                        'attribute' => 'carrier_id',
                        'headerOptions' => ['style' => 'width: 100px;'],
                    ],
                    [
                        'attribute' => 'company_name',
                        'format' => 'raw',
                        'value' => function ($model) {
                            return Html::a(Html::encode($model->company_name), ['view', 'id' => $model->id]);
                        },
                    ],
                    'contact_name',
                    'email:email',
                    'phone',
                    [
                        'attribute' => 'status',
                        'filter' => Client::getStatusList(),
                        'value' => function ($model) {
                            return $model->getStatusText();
                        },
                        'contentOptions' => function ($model) {
                            $class = '';
                            if ($model->status === Client::STATUS_ACTIVE) {
                                $class = 'text-success';
                            } elseif ($model->status === Client::STATUS_INACTIVE) {
                                $class = 'text-muted';
                            } elseif ($model->status === Client::STATUS_BLOCKED) {
                                $class = 'text-danger';
                            }
                            return ['class' => $class];
                        },
                    ],
                    [
                        'attribute' => 'rebate_value',
                        'label' => 'Скидка',
                        'value' => function ($model) {
                            return $model->getFormattedRebate();
                        },
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{view} {update} {set-rebate} {delete}',
                        'buttons' => [
                            'set-rebate' => function ($url, $model) {
                                return Html::a('<i class="bi bi-percent"></i>', $url, [
                                    'title' => 'Установить скидку',
                                    'data-toggle' => 'tooltip',
                                ]);
                            },
                        ],
                        'visibleButtons' => [
                            'delete' => function ($model) {
                                // Разрешаем удаление только если у клиента нет транзакций
                                return !\app\models\Transaction::find()->where(['client_id' => $model->id])->exists();
                            },
                        ],
                    ],
                ],
            ]); ?>
            <?php Pjax::end(); ?>
        </div>
    </div>
    
    <div class="alert alert-info mt-4">
        <h5>Информация</h5>
        <p>Этот раздел позволяет управлять клиентами (дочерними перевозчиками в системе WEX). Вы можете:</p>
        <ul>
            <li>Добавлять новых клиентов вручную</li>
            <li>Синхронизировать клиентов с WEX API</li>
            <li>Устанавливать индивидуальные скидки для клиентов</li>
            <li>Просматривать детали клиентов, их карты и транзакции</li>
        </ul>
    </div>
</div>

<?php
$js = <<<JS
    // Обработка клика на кнопку синхронизации
    $('#sync-clients-btn').on('click', function(e) {
        e.preventDefault();
        
        if (confirm($(this).data('confirm'))) {
            var btn = $(this);
            btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Синхронизация...').prop('disabled', true);
            
            $.ajax({
                url: btn.attr('href'),
                type: 'POST',
                success: function(response) {
                    if (response.success) {
                        alert('Синхронизация успешно завершена: ' + response.message);
                        location.reload();
                    } else {
                        alert('Ошибка: ' + response.message);
                        btn.html('<i class="bi bi-arrow-repeat"></i> Синхронизировать с WEX').prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Произошла ошибка при выполнении запроса');
                    btn.html('<i class="bi bi-arrow-repeat"></i> Синхронизировать с WEX').prop('disabled', false);
                }
            });
        }
    });
JS;

$this->registerJs($js);
?>'