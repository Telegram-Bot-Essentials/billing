<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TelegramBotEssentials\Essence\Models\Bot;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Bot::class)->constrained();
            $table->string('code');
            $table->enum('type', ['percentage', 'fixed'])->nullable();
            $table->decimal('amount', 65, 30)->nullable();
            $table->decimal('max_discount', 65, 30)->nullable();
            $table->decimal('min_price', 65, 30)->nullable();
            $table->decimal('max_price', 65, 30)->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_limit_per_user')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Not a unique constraint: a soft-deleted row would keep the DB
            // slot occupied forever, blocking reuse of its code even though
            // the app-level uniqueness check (which excludes trashed rows)
            // would allow it. Uniqueness is enforced at the application
            // level instead; this index just keeps lookups fast.
            $table->index(['bot_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
