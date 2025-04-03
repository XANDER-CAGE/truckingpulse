<?php
use yii\bootstrap5\Html;
?>

<div class="tab-connection-test p-3">
    <p>Этот инструмент проверяет соединение с WEX API, выполняя попытку входа с текущими учетными данными.</p>
    
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-title mb-0">Параметры подключения</h5>
        </div>
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-md-3 fw-bold">Имя пользователя:</div>
                <div class="col-md-9"><?= Yii::$app->params['wex']['username'] ?></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-3 fw-bold">Среда:</div>
                <div class="col-md-9"><?= Yii::$app->params['wex']['isProduction'] ? 'Продакшн' : 'Тестовая' ?></div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <?= Html::button('Проверить соединение', [
                        'class' => 'btn btn-primary',
                        'id' => 'test-connection-btn'
                    ]) ?>
                </div>
            </div>
        </div>
    </div>
    
    <div id="connection-status"></div>
</div>