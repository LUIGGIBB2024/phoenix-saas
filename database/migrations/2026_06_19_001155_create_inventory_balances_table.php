<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->string('year', 4)->nullable()->index();
            $table->string('code', 20)->nullable()->index();
            $table->string('store', 10)->nullable();
            $table->string('batch', 10)->nullable();
            $table->decimal('quantity', 20, 8)->nullable();
            $table->decimal('quantity1', 20, 8)->nullable();
            $table->decimal('previous_balance', 20, 8)->nullable();
            $table->decimal('cost', 20, 8)->nullable();
            $table->decimal('lastcost', 20, 8)->nullable();
            $table->decimal('cost00', 20, 8)->nullable();
            $table->decimal('cost01', 20, 8)->nullable();
            $table->decimal('cost02', 20, 8)->nullable();
            $table->decimal('cost03', 20, 8)->nullable();
            $table->decimal('cost04', 20, 8)->nullable();
            $table->decimal('cost05', 20, 8)->nullable();
            $table->decimal('cost06', 20, 8)->nullable();
            $table->decimal('cost07', 20, 8)->nullable();
            $table->decimal('cost08', 20, 8)->nullable();
            $table->decimal('cost09', 20, 8)->nullable();
            $table->decimal('cost10', 20, 8)->nullable();
            $table->decimal('cost11', 20, 8)->nullable();
            $table->decimal('cost12', 20, 8)->nullable();

            $table->unsignedBigInteger('companies_id')->index()->nullable();
            $table->foreign('companies_id')->references('id')->on('companies')->onDelete('set null');

            $table->unsignedBigInteger('products_id')->index()->nullable();
            $table->foreign('products_id')->references('id')->on('products')->onDelete('set null');

            $table->index(['companies_id', 'year', 'code', 'store', 'batch'], 'idx_year_code_store_batch');

            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->string('usercreate', 20)->nullable()->default('System');
            $table->string('userupdate', 20)->nullable()->default('System');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_balances');
    }
};
