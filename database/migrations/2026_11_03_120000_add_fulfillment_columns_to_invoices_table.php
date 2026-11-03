<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('fulfilled_at')->nullable()->after('status');
            $table->text('fulfillment_error')->nullable()->after('fulfilled_at');
        });

        // What was paid before this was tracked is taken as delivered, or every
        // old order would show up as waiting for one.
        DB::table('invoices')->where('status', 'paid')->update(['fulfilled_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['fulfilled_at', 'fulfillment_error']);
        });
    }
};
