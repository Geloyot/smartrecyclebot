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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('status', 20)->default('Inactive');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_status_updated')->nullable();
            $table->foreignId('role_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        DB::table('users')->insert([
            [
                'name' => 'John Angelo Ayson',
                'email' => 'johnangeloayson@gmail.com',
                'password' => password_hash('abcdefghi', PASSWORD_DEFAULT),
                'role_id' => 2,
                'status' => 'Active',
                'created_at' => now(),
                'updated_at' => now(),
                'last_status_updated' => now()
            ],
            [
                'name' => 'Nhoj Olegna Nosya',
                'email' => 'a_ayson@yahoo.com',
                'password' => password_hash('12345678', PASSWORD_DEFAULT),
                'role_id' => 1,
                'status' => 'Inactive',
                'created_at' => now(),
                'updated_at' => now(),
                'last_status_updated' => now()
            ],
            [
                'name' => 'Administrator User',
                'email' => 'admin_recyclebot@gmail.com',
                'password' => password_hash('abcdefghi', PASSWORD_DEFAULT),
                'role_id' => 2,
                'status' => 'Active',
                'created_at' => now(),
                'updated_at' => now(),
                'last_status_updated' => now()
            ],
            [
                'name' => 'Maintenance User',
                'email' => 'maintenance@yahoo.com',
                'password' => password_hash('12345678', PASSWORD_DEFAULT),
                'role_id' => 1,
                'status' => 'Inactive',
                'created_at' => now(),
                'updated_at' => now(),
                'last_status_updated' => now()
            ],
            [
                'name' => 'Administrator User 2',
                'email' => 'admin@gmail.com',
                'password' => password_hash('abcdefghi', PASSWORD_DEFAULT),
                'role_id' => 2,
                'status' => 'Active',
                'created_at' => now(),
                'updated_at' => now(),
                'last_status_updated' => now()
            ],
            [
                'name' => 'Maintenance User 2',
                'email' => 'maintenance2@yahoo.com',
                'password' => password_hash('12345678', PASSWORD_DEFAULT),
                'role_id' => 1,
                'status' => 'Inactive',
                'created_at' => now(),
                'updated_at' => now(),
                'last_status_updated' => now()
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
