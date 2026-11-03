<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->rename('billing.purchase_alerts.target', 'billing.purchase_alerts.targets');
    }

    public function down(): void
    {
        $this->rename('billing.purchase_alerts.targets', 'billing.purchase_alerts.target');
    }

    private function rename(string $from, string $to): void
    {
        if (Schema::hasTable('bot_settings')) {
            DB::table('bot_settings')->where('key', $from)->update(['key' => $to]);
        }
    }
};
