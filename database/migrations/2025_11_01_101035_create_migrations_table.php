<?php
use Illuminate\Database\Capsule\Manager as Capsule;

return new class {
    public function up() {
        if (!Capsule::schema()->hasTable('migrations')) {
            Capsule::schema()->create('migrations', function ($table) {
                $table->id();
                $table->string('migration', 255)->unique();
                $table->integer('batch')->default(1);
                $table->timestamp('created_at')->default(Capsule::raw('CURRENT_TIMESTAMP'));
            });
            echo "✅ Jadval yaratildi: migrations\n";
        } else {
            echo "⚠️ Jadval allaqachon mavjud: migrations\n";
        }
    }

    public function down() {
        if (Capsule::schema()->hasTable('migrations')) {
            Capsule::schema()->drop('migrations');
            echo "🗑️ Jadval o‘chirildi: migrations\n";
        } else {
            echo "⚠️ Jadval mavjud emas: migrations\n";
        }
    }
};