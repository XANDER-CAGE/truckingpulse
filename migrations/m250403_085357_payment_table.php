<?php

use yii\db\Migration;

class m250403_085357_payment_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%payment}}', [
            'id' => $this->primaryKey(),
            'invoice_id' => $this->integer()->notNull(),
            'client_id' => $this->integer()->notNull(),
            'amount' => $this->decimal(10, 2)->notNull(),
            'payment_method' => $this->string(20)->notNull(),
            'reference_number' => $this->string(50),
            'status' => $this->string(20)->notNull()->defaultValue('pending'),
            'notes' => $this->text(),
            'payment_date' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // Индексы
        $this->createIndex(
            'idx-payment-invoice_id',
            '{{%payment}}',
            'invoice_id'
        );

        $this->createIndex(
            'idx-payment-client_id',
            '{{%payment}}',
            'client_id'
        );

        $this->createIndex(
            'idx-payment-status',
            '{{%payment}}',
            'status'
        );

        $this->createIndex(
            'idx-payment-payment_date',
            '{{%payment}}',
            'payment_date'
        );

        // Внешний ключ на счет
        $this->addForeignKey(
            'fk-payment-invoice_id',
            '{{%payment}}',
            'invoice_id',
            '{{%invoice}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Внешний ключ на клиента
        $this->addForeignKey(
            'fk-payment-client_id',
            '{{%payment}}',
            'client_id',
            '{{%client}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Удаляем внешние ключи
        $this->dropForeignKey('fk-payment-client_id', '{{%payment}}');
        $this->dropForeignKey('fk-payment-invoice_id', '{{%payment}}');

        // Удаляем индексы
        $this->dropIndex('idx-payment-invoice_id', '{{%payment}}');
        $this->dropIndex('idx-payment-client_id', '{{%payment}}');
        $this->dropIndex('idx-payment-status', '{{%payment}}');
        $this->dropIndex('idx-payment-payment_date', '{{%payment}}');

        // Удаляем таблицу
        $this->dropTable('{{%payment}}');
    }
}