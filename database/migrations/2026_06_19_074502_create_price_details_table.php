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
        Schema::create('price_details', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->nullable();
            $table->string('product', 20)->nullable();

            $table->unsignedBigInteger('companies_id')->index()->nullable();
            $table->foreign('companies_id')->references('id')->on('companies')->onDelete('set null');

            $table->unsignedBigInteger('products_id')->index()->nullable();
            $table->foreign('products_id')->references('id')->on('products')->onDelete('set null');

            $table->unsignedBigInteger('price_lists_id')->index()->nullable();
            $table->foreign('price_lists_id')->references('id')->on('price_lists')->onDelete('set null');

            $table->index(['companies_id',  'code', 'product'], 'idx_companiesid_code_product');

            $table->decimal('vat', 6, 2)->nullable();
            $table->decimal('price', 20, 8)->nullable();
            $table->decimal('priceunit', 20, 8)->nullable();
            $table->decimal('beforevat', 20, 8)->nullable();

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
        Schema::dropIfExists('price_details');
    }
};
