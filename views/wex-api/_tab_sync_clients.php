<?php
use yii\bootstrap5\Html;
?>

<div class="tab-sync-clients p-3">
    <p>Этот инструмент синхронизирует список дочерних клиентов (child carriers) из WEX API с вашей локальной базой данных.</p>
    
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-title mb-0">Синхронизация клиентов</h5>
        </div>
        <div class="card-body">
            <p>При синхронизации:</p>
            <ul>
                <li>Будут добавлены новые клиенты, которые есть в WEX, но отсутствуют в вашей базе данных</li>
                <li>Будут обновлены данные существующих клиентов</li>
                <li>Клиенты из вашей базы, которых нет в WEX, останутся без изменений</li>
            </ul>
            <div class="row">
                <div class="col-md-12">
                    <?= Html::button('Синхронизировать клиентов', [
                        'class' => 'btn btn-primary',
                        'id' => 'sync-clients-btn'
                    ]) ?>
                </div>
            </div>
        </div>
    </div>
    
    <div id="sync-status"></div>

    <div class="alert alert-info mt-3">
        <h5>Примечание</h5>
        <p>Синхронизация клиентов не удаляет существующих клиентов и не изменяет установленные вами скидки и другие настройки. Обновляются только основные данные клиента, такие как название, контактная информация и адрес.</p>
    </div>
</div>