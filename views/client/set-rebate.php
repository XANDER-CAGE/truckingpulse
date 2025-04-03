<?php

use app\models\Client;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Client */

$this->title = 'Установка скидки: ' . $model->company_name;
$this->params['breadcrumbs'][] = ['label' => 'Клиенты', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->company_name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Установка скидки';
?>
<div class="client-set-rebate">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Установка скидки</h5>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin(); ?>

                    <?= $form->field($model, 'rebate_type')->dropDownList(Client::getRebateTypeList(), [
                        'prompt' => 'Выберите тип скидки',
                        'id' => 'rebate-type',
                    ]) ?>

                    <?= $form->field($model, 'rebate_value', [
                        'options' => ['class' => 'form-group'],
                        'template' => '{label}<div class="input-group">{input}<div class="input-group-text" id="rebate-addon">$</div></div>{hint}{error}',
                    ])->textInput([
                        'id' => 'rebate-value',
                        'aria-describedby' => 'rebate-addon',
                    ]) ?>

                    <div class="form-group">
                        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
                        <?= Html::a('Отмена', ['view', 'id' => $model->id], ['class' => 'btn btn-secondary']) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Информация о скидках</h5>
                </div>
                <div class="card-body">
                    <p>Скидка для клиента может быть установлена одним из двух способов:</p>
                    
                    <h6>Фиксированная скидка ($)</h6>
                    <p>Фиксированная сумма скидки в долларах, которая будет вычтена из стоимости каждого галлона топлива.</p>
                    <div class="alert alert-info">
                        <strong>Пример:</strong> Если розничная цена галлона $3.50, и установлена фиксированная скидка $0.15, то клиент заплатит $3.35 за галлон.
                    </div>
                    
                    <h6>Процентная скидка (%)</h6>
                    <p>Процент от розничной стоимости, который будет вычтен из цены.</p>
                    <div class="alert alert-info">
                        <strong>Пример:</strong> Если розничная цена галлона $3.50, и установлена процентная скидка 5%, то клиент заплатит $3.33 за галлон ($3.50 - 5% = $3.50 - $0.175 = $3.325, округленно $3.33).
                    </div>
                    
                    <p><strong>Рекомендация:</strong> Для топливных карт обычно используется фиксированная скидка на галлон.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$js = <<<JS
    // Обновляем суффикс в зависимости от выбранного типа скидки
    $('#rebate-type').on('change', function() {
        if ($(this).val() === 'fixed') {
            $('#rebate-addon').text('$');
            $('#rebate-value').attr('placeholder', '0.15');
        } else if ($(this).val() === 'percentage') {
            $('#rebate-addon').text('%');
            $('#rebate-value').attr('placeholder', '5.00');
        } else {
            $('#rebate-addon').text('');
            $('#rebate-value').attr('placeholder', '');
        }
    });
    
    // Инициализация
    $('#rebate-type').trigger('change');
JS;

$this->registerJs($js);
?>