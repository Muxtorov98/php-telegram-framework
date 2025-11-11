<?php
use Illuminate\Database\Capsule\Manager as Capsule;

return new class {
    public function up() {
        if (!Capsule::schema()->hasTable('products')) {
            Capsule::schema()->create('products', function ($table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2);
                $table->string('image')->nullable();
                $table->timestamps();
            });
            echo "✅ Jadval yaratildi: products\n";
        } else {
            echo "⚠️ Jadval allaqachon mavjud: products\n";
        }
    }

    public function down() {
        if (Capsule::schema()->hasTable('products')) {
            Capsule::schema()->drop('products');
            echo "🗑️ Jadval o‘chirildi: products\n";
        } else {
            echo "⚠️ Jadval mavjud emas: products\n";
        }
    }
};