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
        // database/migrations/xxxx_xx_xx_create_bins_table.php
        Schema::create('bins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->boolean('notified_full')->default(false);
            $table->float('last_fill_level', 5)->nullable();
            $table->float('last_full_fill_level', 5)->nullable();
            $table->timestamp('last_full_at')->nullable();
            $table->timestamp('last_emptied_at')->nullable();
            $table->timestamp('last_emptied_full_at')->nullable(); // the FULL reading before emptying
            $table->timestamps();
        });

        DB::table('bins')->insert([
            [
                'id' => 1,
                'name' => 'Biodegradable',
                'type' => 'bio',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 2,
                'name' => 'Non-Biodegradable',
                'type' => 'non-bio',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bins');
    }
};
