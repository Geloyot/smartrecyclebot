<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bins', function (Blueprint $table) {
            $table->timestamp('last_full_at')->nullable()->after('last_full_fill_level');
            $table->timestamp('last_emptied_at')->nullable()->after('last_full_at');
            $table->timestamp('last_emptied_full_at')->nullable()->after('last_emptied_at');
        });
    }

    public function down(): void
    {
        Schema::table('bins', function (Blueprint $table) {
            $table->dropColumn(['last_full_at', 'last_emptied_at', 'last_emptied_full_at']);
        });
    }
};
