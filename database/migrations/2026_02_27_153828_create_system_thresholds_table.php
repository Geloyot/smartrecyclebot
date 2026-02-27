<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_thresholds', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->timestamps();
        });

        // Insert default values
        DB::table('system_thresholds')->insert([
            [
                'key' => 'full_bin_threshold',
                'value' => '75',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'classification_accuracy_threshold',
                'value' => '80',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_thresholds');
    }
};
