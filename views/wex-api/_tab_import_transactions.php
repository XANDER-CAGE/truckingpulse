<?php
use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;
?>

<div class="tab-import-transactions p-3">
    <p>Этот инструмент позволяет импортировать транзакции из WEX API за указанный период времени.</p>
    
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-title mb-0">Параметры импорта</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="minutes" class="form-label">Период (минуты)</label>
                </div>
                <div class="col-md-9">
                    <select id="minutes" class="form-select">
                        <option value="15">Последние 15 минут</option>
                        <option value="30">Последние 30 минут</option>
                        <option value="60" selected>Последний час</option>
                        <option value="180">Последние 3 часа</option>
                        <option value="360">Последние 6 часов</option>
                        <option value="720">Последние 12 часов</option>
                        <option value="1440">Последние 24 часа</option>
                    </select>
                    <div class="form-text">Рекомендуется импортировать данные каждые 15-60 минут для оптимальной производительности</div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <?= Html::button('Импортировать транзакции', [
                        'class' => 'btn btn-primary',
                        'id' => 'import-transactions-btn'
                    ]) ?>
                </div>
            </div>
        </div>
    </div>
    
    <div id="import-status"></div>
    
    <div class="alert alert-warning mt-3">
        <h5>Важно!</h5>
        <p>Импорт транзакций за большой период времени может занять значительное время. Для регулярного импорта рекомендуется настроить автоматическое задание в cron.</p>
        <p>Пример команды cron для импорта каждые 15 минут:</p>
        <pre>*/15 * * * * php /var/www/html/yii import/transactions</pre>
    </div>
</div>