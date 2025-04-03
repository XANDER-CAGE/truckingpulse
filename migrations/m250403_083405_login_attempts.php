<?php

use yii\db\Migration;

class m250403_083405_login_attempts extends Migration
{
    public function up()
    {
        $this->createTable('{{%login_attempts}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string(255)->notNull(),
            'ip' => $this->string(45)->notNull(),
            'status' => $this->string(20)->notNull(),
            'created_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            'idx-login_attempts-username',
            '{{%login_attempts}}',
            'username'
        );

        $this->createIndex(
            'idx-login_attempts-created_at',
            '{{%login_attempts}}',
            'created_at'
        );
    }

    public function down()
    {
        $this->dropTable('{{%login_attempts}}');
    }
}