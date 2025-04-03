<?php

use yii\db\Migration;

class m250403_081936_init extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Таблица пользователей системы
        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string(255)->notNull()->unique(),
            'email' => $this->string(255)->notNull()->unique(),
            'password_hash' => $this->string(255)->notNull(),
            'auth_key' => $this->string(32)->notNull(),
            'status' => $this->smallInteger()->notNull()->defaultValue(10),
            'role' => $this->string(20)->notNull()->defaultValue('manager'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // Создаем первого пользователя-администратора
        $this->insert('{{%user}}', [
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password_hash' => Yii::$app->security->generatePasswordHash('admin'),
            'auth_key' => Yii::$app->security->generateRandomString(),
            'status' => 10,
            'role' => 'admin',
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        
        // Таблица клиентов (child carriers)
        $this->createTable('{{%client}}', [
            'id' => $this->primaryKey(),
            'carrier_id' => $this->integer()->notNull()->comment('WEX child carrier ID'),
            'company_name' => $this->string(255)->notNull(),
            'contact_name' => $this->string(255)->notNull(),
            'email' => $this->string(255)->notNull(),
            'phone' => $this->string(20)->notNull(),
            'address' => $this->string(255),
            'city' => $this->string(100),
            'state' => $this->string(2),
            'zip' => $this->string(10),
            'country' => $this->string(3)->defaultValue('USA'),
            'status' => $this->string(20)->notNull()->defaultValue('active'),
            'rebate_type' => $this->string(20)->comment('fixed or percentage'),
            'rebate_value' => $this->decimal(10, 4)->comment('Rebate amount or percentage'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        
        // Добавим индекс для быстрого поиска по carrier_id
        $this->createIndex('idx-client-carrier_id', '{{%client}}', 'carrier_id', true);
        
        // Таблица политик для карт
        $this->createTable('{{%policy}}', [
            'id' => $this->primaryKey(),
            'policy_number' => $this->integer()->notNull()->comment('WEX policy number'),
            'description' => $this->string(255)->notNull(),
            'contract_id' => $this->integer(),
            'hand_enter' => $this->boolean()->defaultValue(false),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        
        // Добавим индекс для быстрого поиска по номеру политики
        $this->createIndex('idx-policy-policy_number', '{{%policy}}', 'policy_number', true);
        
        // Таблица ограничений для политик
        $this->createTable('{{%policy_limit}}', [
            'id' => $this->primaryKey(),
            'policy_id' => $this->integer()->notNull(),
            'limit_id' => $this->string(10)->notNull()->comment('WEX limit ID like ULSD, GAS, etc'),
            'limit_value' => $this->integer()->notNull(),
            'hours' => $this->integer()->defaultValue(0),
            'min_hours' => $this->integer()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        
        // Индекс для ограничений политики
        $this->createIndex('idx-policy_limit-policy_id', '{{%policy_limit}}', 'policy_id');
        
        // Внешний ключ на таблицу политик
        $this->addForeignKey(
            'fk-policy_limit-policy_id', 
            '{{%policy_limit}}', 
            'policy_id', 
            '{{%policy}}', 
            'id', 
            'CASCADE', 
            'CASCADE'
        );
        
        // Таблица топливных карт
        $this->createTable('{{%card}}', [
            'id' => $this->primaryKey(),
            'card_number' => $this->string(25)->notNull()->unique(),
            'client_id' => $this->integer(),
            'policy_id' => $this->integer(),
            'status' => $this->string(20)->notNull()->defaultValue('active'),
            'company_xref' => $this->string(15),
            'driver_id' => $this->string(24),
            'driver_name' => $this->string(50),
            'unit_number' => $this->string(24),
            'hand_enter' => $this->string(10)->defaultValue('POLICY'),
            'pin' => $this->string(255),
            'issue_date' => $this->integer(),
            'last_used_date' => $this->integer(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        
        // Индексы для карт
        $this->createIndex('idx-card-client_id', '{{%card}}', 'client_id');
        $this->createIndex('idx-card-policy_id', '{{%card}}', 'policy_id');
        $this->createIndex('idx-card-driver_id', '{{%card}}', 'driver_id');
        $this->createIndex('idx-card-status', '{{%card}}', 'status');
        
        // Внешние ключи для карт
        $this->addForeignKey(
            'fk-card-client_id', 
            '{{%card}}', 
            'client_id', 
            '{{%client}}', 
            'id', 
            'SET NULL', 
            'CASCADE'
        );
        
        $this->addForeignKey(
            'fk-card-policy_id', 
            '{{%card}}', 
            'policy_id', 
            '{{%policy}}', 
            'id', 
            'SET NULL', 
            'CASCADE'
        );
        
        // Таблица заказов карт
        $this->createTable('{{%card_order}}', [
            'id' => $this->primaryKey(),
            'order_id' => $this->bigInteger()->notNull()->comment('WEX order ID'),
            'client_id' => $this->integer()->notNull(),
            'policy_id' => $this->integer()->notNull(),
            'card_style' => $this->integer()->notNull(),
            'quantity' => $this->integer()->notNull(),
            'embossed_name' => $this->string(25),
            'shipping_method' => $this->integer(),
            'rush_processing' => $this->boolean()->defaultValue(false),
            'status' => $this->string(20)->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        
        // Индексы для заказов карт
        $this->createIndex('idx-card_order-client_id', '{{%card_order}}', 'client_id');
        $this->createIndex('idx-card_order-policy_id', '{{%card_order}}', 'policy_id');
        
        // Внешние ключи для заказов карт
        $this->addForeignKey(
            'fk-card_order-client_id', 
            '{{%card_order}}', 
            'client_id', 
            '{{%client}}', 
            'id', 
            'CASCADE', 
            'CASCADE'
        );
        
        $this->addForeignKey(
            'fk-card_order-policy_id', 
            '{{%card_order}}', 
            'policy_id', 
            '{{%policy}}', 
            'id', 
            'CASCADE', 
            'CASCADE'
        );
        
        // Таблица транзакций
        $this->createTable('{{%transaction}}', [
            'id' => $this->primaryKey(),
            'transaction_id' => $this->bigInteger()->notNull()->unique()->comment('WEX transaction ID'),
            'card_id' => $this->integer()->notNull(),
            'client_id' => $this->integer()->notNull(),
            'transaction_date' => $this->integer()->notNull(),
            'post_date' => $this->integer(),
            'location_id' => $this->integer(),
            'location_name' => $this->string(255),
            'location_city' => $this->string(100),
            'location_state' => $this->string(2),
            'location_country' => $this->string(3),
            'product_code' => $this->string(10),
            'product_description' => $this->string(100),
            'quantity' => $this->decimal(10, 3),
            'retail_price' => $this->decimal(10, 3)->comment('Price per unit before discount'),
            'discounted_price' => $this->decimal(10, 3)->comment('Price per unit after WEX discount'),
            'client_price' => $this->decimal(10, 3)->comment('Price per unit for client after our markup'),
            'retail_amount' => $this->decimal(10, 2)->comment('Total amount before discount'),
            'funded_amount' => $this->decimal(10, 2)->comment('Total amount after WEX discount'),
            'client_amount' => $this->decimal(10, 2)->comment('Total amount for client after our markup'),
            'our_profit' => $this->decimal(10, 2)->comment('Our profit from this transaction'),
            'driver_id' => $this->string(24),
            'odometer' => $this->integer(),
            'invoice_number' => $this->string(20),
            'auth_code' => $this->string(20),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        
        // Индексы для транзакций
        $this->createIndex('idx-transaction-card_id', '{{%transaction}}', 'card_id');
        $this->createIndex('idx-transaction-client_id', '{{%transaction}}', 'client_id');
        $this->createIndex('idx-transaction-transaction_date', '{{%transaction}}', 'transaction_date');
        $this->createIndex('idx-transaction-location_id', '{{%transaction}}', 'location_id');
        $this->createIndex('idx-transaction-product_code', '{{%transaction}}', 'product_code');
        
        // Внешние ключи для транзакций
        $this->addForeignKey(
            'fk-transaction-card_id', 
            '{{%transaction}}', 
            'card_id', 
            '{{%card}}', 
            'id', 
            'CASCADE', 
            'CASCADE'
        );
        
        $this->addForeignKey(
            'fk-transaction-client_id', 
            '{{%transaction}}', 
            'client_id', 
            '{{%client}}', 
            'id', 
            'CASCADE', 
            'CASCADE'
        );
        
        // Таблица счетов
        $this->createTable('{{%invoice}}', [
            'id' => $this->primaryKey(),
            'invoice_number' => $this->string(20)->notNull()->unique(),
            'client_id' => $this->integer()->notNull(),
            'start_date' => $this->integer()->notNull(),
            'end_date' => $this->integer()->notNull(),
            'subtotal' => $this->decimal(10, 2)->notNull(),
            'tax' => $this->decimal(10, 2)->defaultValue(0),
            'total' => $this->decimal(10, 2)->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue('unpaid'),
            'due_date' => $this->integer()->notNull(),
            'paid_date' => $this->integer(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        
        // Индексы для счетов
        $this->createIndex('idx-invoice-client_id', '{{%invoice}}', 'client_id');
        $this->createIndex('idx-invoice-status', '{{%invoice}}', 'status');
        
        // Внешний ключ на клиента
        $this->addForeignKey(
            'fk-invoice-client_id', 
            '{{%invoice}}', 
            'client_id', 
            '{{%client}}', 
            'id', 
            'CASCADE', 
            'CASCADE'
        );
        
        // Детали счетов
        $this->createTable('{{%invoice_item}}', [
            'id' => $this->primaryKey(),
            'invoice_id' => $this->integer()->notNull(),
            'transaction_id' => $this->integer(),
            'description' => $this->string(255)->notNull(),
            'quantity' => $this->decimal(10, 3),
            'price' => $this->decimal(10, 2),
            'amount' => $this->decimal(10, 2)->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        
        // Индексы для деталей счетов
        $this->createIndex('idx-invoice_item-invoice_id', '{{%invoice_item}}', 'invoice_id');
        $this->createIndex('idx-invoice_item-transaction_id', '{{%invoice_item}}', 'transaction_id');
        
        // Внешние ключи для деталей счетов
        $this->addForeignKey(
            'fk-invoice_item-invoice_id', 
            '{{%invoice_item}}', 
            'invoice_id', 
            '{{%invoice}}', 
            'id', 
            'CASCADE', 
            'CASCADE'
        );
        
        $this->addForeignKey(
            'fk-invoice_item-transaction_id', 
            '{{%invoice_item}}', 
            'transaction_id', 
            '{{%transaction}}', 
            'id', 
            'SET NULL', 
            'CASCADE'
        );
        
        // Таблица платежей
        $this->createTable('{{%payment}}', [
            'id' => $this->primaryKey(),
            'invoice_id' => $this->integer()->notNull(),
            'client_id' => $this->integer()->notNull(),
            'amount' => $this->decimal(10, 2)->notNull(),
            'payment_method' => $this->string(50)->notNull(),
            'reference_number' => $this->string(50),
            'notes' => $this->text(),
            'payment_date' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        
        // Индексы для платежей
        $this->createIndex('idx-payment-invoice_id', '{{%payment}}', 'invoice_id');
        $this->createIndex('idx-payment-client_id', '{{%payment}}', 'client_id');
        
        // Внешние ключи для платежей
        $this->addForeignKey(
            'fk-payment-invoice_id', 
            '{{%payment}}', 
            'invoice_id', 
            '{{%invoice}}', 
            'id', 
            'CASCADE', 
            'CASCADE'
        );
        
        $this->addForeignKey(
            'fk-payment-client_id', 
            '{{%payment}}', 
            'client_id', 
            '{{%client}}', 
            'id', 
            'CASCADE', 
            'CASCADE'
        );
        
        // Таблица API-логов
        $this->createTable('{{%api_log}}', [
            'id' => $this->primaryKey(),
            'method' => $this->string(50)->notNull(),
            'request' => $this->text(),
            'response' => $this->text(),
            'status' => $this->string(20)->notNull(),
            'error' => $this->text(),
            'duration' => $this->integer(),
            'created_at' => $this->integer()->notNull(),
        ]);
        
        // Индексы для API-логов
        $this->createIndex('idx-api_log-method', '{{%api_log}}', 'method');
        $this->createIndex('idx-api_log-status', '{{%api_log}}', 'status');
        $this->createIndex('idx-api_log-created_at', '{{%api_log}}', 'created_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Удаляем внешние ключи для payment
        $this->dropForeignKey('fk-payment-client_id', '{{%payment}}');
        $this->dropForeignKey('fk-payment-invoice_id', '{{%payment}}');
        
        // Удаляем внешние ключи для invoice_item
        $this->dropForeignKey('fk-invoice_item-transaction_id', '{{%invoice_item}}');
        $this->dropForeignKey('fk-invoice_item-invoice_id', '{{%invoice_item}}');
        
        // Удаляем внешний ключ для invoice
        $this->dropForeignKey('fk-invoice-client_id', '{{%invoice}}');
        
        // Удаляем внешние ключи для transaction
        $this->dropForeignKey('fk-transaction-client_id', '{{%transaction}}');
        $this->dropForeignKey('fk-transaction-card_id', '{{%transaction}}');
        
        // Удаляем внешние ключи для card_order
        $this->dropForeignKey('fk-card_order-policy_id', '{{%card_order}}');
        $this->dropForeignKey('fk-card_order-client_id', '{{%card_order}}');
        
        // Удаляем внешние ключи для card
        $this->dropForeignKey('fk-card-policy_id', '{{%card}}');
        $this->dropForeignKey('fk-card-client_id', '{{%card}}');
        
        // Удаляем внешний ключ для policy_limit
        $this->dropForeignKey('fk-policy_limit-policy_id', '{{%policy_limit}}');
        
        // Удаляем таблицы
        $this->dropTable('{{%api_log}}');
        $this->dropTable('{{%payment}}');
        $this->dropTable('{{%invoice_item}}');
        $this->dropTable('{{%invoice}}');
        $this->dropTable('{{%transaction}}');
        $this->dropTable('{{%card_order}}');
        $this->dropTable('{{%card}}');
        $this->dropTable('{{%policy_limit}}');
        $this->dropTable('{{%policy}}');
        $this->dropTable('{{%client}}');
        $this->dropTable('{{%user}}');
    }
}