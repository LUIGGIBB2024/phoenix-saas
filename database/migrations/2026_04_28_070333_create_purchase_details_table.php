<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_details', function (Blueprint $table) {
            $table->id();
            $table->decimal('number', 20)->nullable();
            $table->string('prefix', 20)->nullable();
            $table->string('document_name', 20)->nullable();
            $table->string('supplier', 20)->nullable();
            $table->dateTime('date_issue')->nullable()->index();
            $table->string('product', 20)->nullable();
            $table->string('name', 255)->nullable();
            $table->string('store', 20)->nullable();
            $table->decimal('quantity', 20, 2)->nullable();
            $table->decimal('basequantity', 20, 8)->nullable();
            $table->decimal('unit_value', 20, 8)->nullable();
            $table->decimal('cost_value', 20, 8)->nullable();
            $table->decimal('discount1', 6, 2)->nullable();
            $table->decimal('discount2', 6, 2)->nullable();
            $table->decimal('valuediscount1', 20, 2)->nullable();
            $table->decimal('valuediscount2', 20, 2)->nullable();
            $table->string('proyect', 20)->nullable();
            $table->string('sproyect', 20)->nullable();
            $table->string('center', 20)->nullable();
            $table->string('activity', 20)->nullable();

            $table->enum('state', ['Activo', 'Eliminado', 'Pendiente'])->nullable()->default('Activo');

            $table->index(['number', 'prefix', 'product', 'store', 'supplier', 'companies_id'], 'idx_number_prefix_supplier');
            $table->index(['companies_id', 'date_issue', 'supplier'], 'idx_companies_date_issue_supplier');
            $table->index(['companies_id', 'proyect', 'date_issue', 'supplier'], 'idx_companies_proyect_date_issue');

            $table->unsignedBigInteger('products_id')->index()->nullable();
            $table->foreign('products_id')->references('id')->on('products')->onDelete('set null');

            $table->unsignedBigInteger('companies_id')->index()->nullable();
            $table->foreign('companies_id')->references('id')->on('companies')->onDelete('set null');

            $table->unsignedBigInteger('purchases_invoices_id')->index()->nullable();
            $table->foreign('purchases_invoices_id')->references('id')->on('purchases_invoices')->onDelete('set null');

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
        Schema::dropIfExists('purchase_details');
    }
};
