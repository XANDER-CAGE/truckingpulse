<?php

/* @var $this \yii\web\View */
/* @var $content string */

use app\assets\AppAsset;
use app\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?> - <?= Html::encode(Yii::$app->name) ?></title>
    <?php $this->head() ?>
</head>
<body class="d-flex flex-column h-100">
<?php $this->beginBody() ?>

<header>
    <?php
    NavBar::begin([
        'brandLabel' => Yii::$app->name,
        'brandUrl' => Yii::$app->homeUrl,
        'options' => [
            'class' => 'navbar navbar-expand-md navbar-dark bg-dark fixed-top',
        ],
    ]);
    echo Nav::widget([
        'options' => ['class' => 'navbar-nav ms-auto'],
        'items' => [
            ['label' => 'Главная', 'url' => ['/site/index']],
            ['label' => 'Клиенты', 'url' => ['/client/index']],
            ['label' => 'Карты', 'url' => ['/card/index']],
            ['label' => 'Транзакции', 'url' => ['/transaction/index']],
            ['label' => 'Счета', 'url' => ['/invoice/index']],
            [
                'label' => 'WEX API',
                'items' => [
                    ['label' => 'Инструменты API', 'url' => ['/wex-api/index']],
                    ['label' => 'Заказ карт', 'url' => ['/card/order']],
                    ['label' => 'Журнал API', 'url' => ['/api-log/index']],
                ],
            ],
            [
                'label' => 'Отчеты',
                'items' => [
                    ['label' => 'Прибыль по клиентам', 'url' => ['/report/profit-by-client']],
                    ['label' => 'Использование карт', 'url' => ['/report/card-usage']],
                    ['label' => 'Транзакции по локациям', 'url' => ['/report/transactions-by-location']],
                ],
            ],
            Yii::$app->user->isGuest ? (
                ['label' => 'Вход', 'url' => ['/site/login']]
            ) : (
                '<li>'
                . Html::beginForm(['/site/logout'], 'post', ['class' => 'form-inline'])
                . Html::submitButton(
                    'Выход (' . Yii::$app->user->identity->username . ')',
                    ['class' => 'btn btn-link logout nav-link']
                )
                . Html::endForm()
                . '</li>'
            )
        ],
    ]);
    NavBar::end();
    ?>
</header>

<main role="main" class="flex-shrink-0">
    <div class="container">
        <?= Breadcrumbs::widget([
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
        ]) ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
</main>

<footer class="footer mt-auto py-3 text-muted">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <p>&copy; <?= Yii::$app->params['system']['companyName'] ?> <?= date('Y') ?></p>
            </div>
            <div class="col-md-6 text-end">
                <p>WEX Fuel System</p>
            </div>
        </div>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>