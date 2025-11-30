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
        Schema::create('sildes', function (Blueprint $table) {
            $table->id();
            $table->String('tagline');
            $table->String('title');
            $table->String('subtitle');
            $table->String('link');
            $table->String('image');
            $table->String('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sildes');
    }
};
