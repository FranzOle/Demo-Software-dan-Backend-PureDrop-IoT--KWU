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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name', 100);
            $table->decimal('liter', 5, 2);
            $table->integer('price');
            $table->string('order_id', 100)->unique();
            $table->enum('payment_status', ['pending', 'success', 'failed'])->default('pending');
            $table->string('payment_type', 50)->nullable();
            $table->timestamp('transaction_time')->nullable()->comment('Waktu transaksi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
