<?php
use Illuminate\Database\Capsule\Manager as Capsule;

return new class {
    public function up() {
        if (!Capsule::schema()->hasTable('order')) {
            Capsule::schema()->create('order', function ($table) {
                $table->bigIncrements('id');
                $table->bigInteger('chat_id')->index()->comment('Telegram foydalanuvchi chat ID');
                $table->string('product_name', 255)->comment('Mahsulot nomi');
                $table->integer('quantity')->default(1)->comment('Buyurtma miqdori');
                $table->text('address')->comment('Yetkazib berish manzili');
                $table->string('status', 50)->default('pending')->comment('Buyurtma holati: pending, approved, shipped, done');
                $table->timestamps();
            });
            echo "✅ Jadval yaratildi: order\n";
        } else {
            echo "⚠️ Jadval allaqachon mavjud: order\n";
        }
    }

    public function down() {
        if (Capsule::schema()->hasTable('order')) {
            Capsule::schema()->drop('order');
            echo "🗑️ Jadval o‘chirildi: order\n";
        } else {
            echo "⚠️ Jadval mavjud emas: order\n";
        }
    }
};