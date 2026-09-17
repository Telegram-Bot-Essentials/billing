<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TelegramBotEssentials\Billing\Models\Offer;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignIdFor(Offer::class)->nullable()->after('payable_id')->constrained()->nullOnDelete();
            $table->decimal('original_price', 65, 30)->nullable()->after('price');
        });

        DB::table('invoices')->whereNull('original_price')->update(['original_price' => DB::raw('price')]);
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(Offer::class);
            $table->dropColumn('original_price');
        });
    }
};
