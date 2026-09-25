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
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('watch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('path');
            $table->char('visitor_hash', 64);
            $table->string('source', 40);
            $table->string('referrer_host')->nullable();
            $table->string('device', 10);
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['watch_id', 'created_at']);
            $table->index(['visitor_hash', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
