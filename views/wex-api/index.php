<?php
/* @var $this yii\web\View */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Button;
use yii\bootstrap5\Tabs;

$this->title = 'WEX API Инструменты';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="wex-api-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="alert alert-info">
        <p><strong>Информация:</strong> Эта страница предоставляет инструменты для работы с WEX API. Используйте эти функции для синхронизации данных, импорта транзакций и тестирования соединения.</p>
    </div>

    <?= Tabs::widget([
        'items' => [
            [
                'label' => 'Тестирование соединения',
                'content' => $this->render('_tab_connection_test'),
                'active' => true,
            ],
            [
                'label' => 'Импорт транзакций',
                'content' => $this->render('_tab_import_transactions'),
            ],
            [
                'label' => 'Синхронизация клиентов',
                'content' => $this->render('_tab_sync_clients'),
            ],
        ],
    ]); ?>

    <div class="mt-4">
        <h3>Журнал API</h3>
        <div id="api-log-container" class="bg-light p-3 mb-3" style="max-height: 300px; overflow-y: auto;">
            <pre id="api-log">Журнал API будет отображаться здесь...</pre>
        </div>
    </div>
</div>

<?php
$js = <<<JS
// Функция для тестирования соединения
function testConnection() {
    $('#test-connection-btn').prop('disabled', true);
    $('#connection-status').html('<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Загрузка...</span></div> Проверка соединения...');
    
    $.ajax({
        url: '/wex-api/test-connection',
        type: 'POST',
        success: function(response) {
            if (response.success) {
                $('#connection-status').html('<div class="alert alert-success">' + response.message + '</div>');
                logApiAction('Тест соединения', 'Успешно: ' + response.message);
            } else {
                $('#connection-status').html('<div class="alert alert-danger">' + response.message + '</div>');
                logApiAction('Тест соединения', 'Ошибка: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            $('#connection-status').html('<div class="alert alert-danger">Ошибка: ' + error + '</div>');
            logApiAction('Тест соединения', 'Ошибка запроса: ' + error);
        },
        complete: function() {
            $('#test-connection-btn').prop('disabled', false);
        }
    });
}

// Функция для импорта транзакций
function importTransactions() {
    var minutes = $('#minutes').val();
    
    $('#import-transactions-btn').prop('disabled', true);
    $('#import-status').html('<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Загрузка...</span></div> Импорт транзакций...');
    
    $.ajax({
        url: '/wex-api/import-transactions',
        type: 'POST',
        data: {minutes: minutes},
        success: function(response) {
            if (response.success) {
                $('#import-status').html('<div class="alert alert-success">' + response.message + '</div>');
                logApiAction('Импорт транзакций', 'Успешно: ' + response.message);
            } else {
                $('#import-status').html('<div class="alert alert-danger">' + response.message + '</div>');
                logApiAction('Импорт транзакций', 'Ошибка: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            $('#import-status').html('<div class="alert alert-danger">Ошибка: ' + error + '</div>');
            logApiAction('Импорт транзакций', 'Ошибка запроса: ' + error);
        },
        complete: function() {
            $('#import-transactions-btn').prop('disabled', false);
        }
    });
}

// Функция для синхронизации клиентов
function syncClients() {
    $('#sync-clients-btn').prop('disabled', true);
    $('#sync-status').html('<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Загрузка...</span></div> Синхронизация клиентов...');
    
    $.ajax({
        url: '/wex-api/sync-child-carriers',
        type: 'POST',
        success: function(response) {
            if (response.success) {
                $('#sync-status').html('<div class="alert alert-success">' + response.message + '</div>');
                logApiAction('Синхронизация клиентов', 'Успешно: ' + response.message);
            } else {
                $('#sync-status').html('<div class="alert alert-danger">' + response.message + '</div>');
                logApiAction('Синхронизация клиентов', 'Ошибка: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            $('#sync-status').html('<div class="alert alert-danger">Ошибка: ' + error + '</div>');
            logApiAction('Синхронизация клиентов', 'Ошибка запроса: ' + error);
        },
        complete: function() {
            $('#sync-clients-btn').prop('disabled', false);
        }
    });
}

// Функция для добавления записи в журнал
function logApiAction(action, message) {
    var now = new Date();
    var timestamp = now.toLocaleTimeString();
    var logEntry = '[' + timestamp + '] ' + action + ': ' + message;
    
    var logElement = $('#api-log');
    var currentLog = logElement.html();
    
    if (currentLog === 'Журнал API будет отображаться здесь...') {
        logElement.html(logEntry);
    } else {
        logElement.html(logEntry + '\\n' + currentLog);
    }
}

// Привязка событий к кнопкам
$(document).ready(function() {
    $('#test-connection-btn').click(testConnection);
    $('#import-transactions-btn').click(importTransactions);
    $('#sync-clients-btn').click(syncClients);
});
JS;

$this->registerJs($js);
?>