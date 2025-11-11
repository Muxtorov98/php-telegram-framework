<?php
use Illuminate\Database\Capsule\Manager as Capsule;

return new class {
    public function up() {
        if (!Capsule::schema()->hasTable('user')) {
            Capsule::schema()->create('user', function ($table) {
                $table->id();
                $table->string('username')->unique();
                $table->string('password');
                $table->bigInteger('chat_id')->unique();
                $table->enum('role', ['admin', 'manager', 'user'])->default('user');
                $table->timestamps();
            });
            echo "✅ Jadval yaratildi: user\n";
        } else {
            echo "⚠️ Jadval allaqachon mavjud: user\n";
        }
    }

    public function down() {
        if (Capsule::schema()->hasTable('user')) {
            Capsule::schema()->drop('user');
            echo "🗑️ Jadval o‘chirildi: user\n";
        } else {
            echo "⚠️ Jadval mavjud emas: user\n";
        }
    }
};