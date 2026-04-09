<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('arm_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('waste_object_id')->nullable()->constrained('waste_objects')->nullOnDelete();
            $table->text('description');
            $table->enum('status', ['SUCCESS', 'WARNING']);
            $table->timestamp('performed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arm_actions');
    }
};
