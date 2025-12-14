<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_status', function (Blueprint $table) {
             DB::statement("
            ALTER TABLE transactions 
            MODIFY payment_status 
            ENUM('pending','success','consumed','failed') 
            NOT NULL DEFAULT 'pending'
        ");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_status', function (Blueprint $table) {
            DB::statement("
            ALTER TABLE transactions 
            MODIFY payment_status 
            ENUM('pending','success','failed') 
            NOT NULL DEFAULT 'pending'
        ");
        });
    }
};
