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
        Schema::create('search_queries', function (Blueprint $table) {
            $table->id();
            $table->string('term', 80);
            $table->unsignedInteger('results_count');
            $table->char('visitor_hash', 64);
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['term', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_queries');
    }
};
