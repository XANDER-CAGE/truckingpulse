<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap5\ActiveForm */
/* @var $model app\models\LoginForm */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Вход в систему';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-login">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h1 class="card-title fs-4 text-center"><?= Html::encode($this->title) ?></h1>
                </div>
                <div class="card-body">
                    <p class="text-center">Пожалуйста, заполните следующие поля для входа:</p>

                    <?php $form = ActiveForm::begin([
                        'id' => 'login-form',
                        'layout' => 'horizontal',
                        'fieldConfig' => [
                            'template' => "{label}\n{input}\n{error}",
                            'labelOptions' => ['class' => 'col-form-label'],
                            'inputOptions' => ['class' => 'form-control'],
                            'errorOptions' => ['class' => 'invalid-feedback'],
                        ],
                    ]); ?>

                    <?= $form->field($model, 'username')->textInput(['autofocus' => true]) ?>

                    <?= $form->field($model, 'password')->passwordInput() ?>

                    <?= $form->field($model, 'rememberMe')->checkbox([
                        'template' => "<div class=\"form-check\">{input} {label}</div>\n{error}",
                        'labelOptions' => ['class' => 'form-check-label'],
                        'inputOptions' => ['class' => 'form-check-input'],
                    ]) ?>

                    <div class="form-group">
                        <div class="d-grid gap-2">
                            <?= Html::submitButton('Войти', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
                        </div>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
            
            <div class="alert alert-info mt-4">
                <p class="mb-0"><strong>Примечание:</strong> Для доступа к системе обратитесь к администратору для получения учетных данных.</p>
            </div>
        </div>
    </div>
</div>