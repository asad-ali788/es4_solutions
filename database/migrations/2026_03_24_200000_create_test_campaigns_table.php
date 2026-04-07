<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_campaigns', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('campaign_id')->nullable();
            $table->bigInteger('ad_group_id')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->decimal('sales1d', 10, 2)->nullable();
            $table->decimal('sales7d', 10, 2)->nullable();
            $table->integer('purchases1d')->nullable();
            $table->integer('purchases7d')->nullable();
            $table->integer('clicks')->nullable();
            $table->decimal('costPerClick', 10, 2)->nullable();
            $table->decimal('c_budget', 10, 2)->nullable();
            $table->boolean('budget_gap')->default(0);
            $table->string('c_currency')->nullable();
            $table->string('c_status')->nullable();
            $table->dateTime('c_date')->nullable();
            $table->string('country')->nullable();
            $table->dateTime('added')->nullable();
            $table->string('campaign_type')->default('SP');
            $table->string('report_type')->nullable();
            $table->string('report_status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_campaigns');
    }
};
