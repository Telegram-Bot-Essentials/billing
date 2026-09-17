<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Billing\Models\Offer;
use TelegramBotEssentials\Essence\Models\BotUser;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Offer::class)->constrained();
            $table->foreignIdFor(Invoice::class)->constrained()->unique();
            $table->foreignIdFor(BotUser::class)->constrained();
            $table->decimal('amount_applied', 65, 30);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_redemptions');
    }
};
