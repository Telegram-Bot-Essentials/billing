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
        Schema::create('to_zirgozar_attempts', function (Blueprint $table) {
            $table->id();
            $table->integer('payment_code');
            $table->string('payment_token');
            $table->string('payer_mobile')->nullable();
            $table->string('payer_card')->nullable();
            $table->decimal('amount', 65, 30);
            $table->decimal('received_amount',65, 30)->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('to_zirgozar_attempts');
    }
};
