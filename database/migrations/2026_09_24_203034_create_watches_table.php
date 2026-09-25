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
        Schema::create('watches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('reference')->nullable();
            $table->string('summary', 300)->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('status')->default('available')->index();
            $table->string('condition')->default('new');
            $table->string('movement')->nullable();
            $table->string('gender')->default('unisex');
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('case_material')->nullable();
            $table->decimal('case_diameter', 4, 1)->nullable();
            $table->unsignedSmallInteger('water_resistance')->nullable();
            $table->string('dial_color')->nullable();
            $table->string('strap_material')->nullable();
            $table->boolean('has_box')->default(false);
            $table->boolean('has_papers')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'is_featured']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('watches');
    }
};
