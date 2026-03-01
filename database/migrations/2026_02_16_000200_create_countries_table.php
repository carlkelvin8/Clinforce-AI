<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('countries')) {
            Schema::create('countries', function (Blueprint $table) {
                $table->id();
                $table->string('country_code', 2)->unique();
                $table->string('country_name');
                $table->string('currency_code', 3);
                $table->string('currency_symbol', 8)->nullable();
                $table->unsignedTinyInteger('currency_decimals')->default(2);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
