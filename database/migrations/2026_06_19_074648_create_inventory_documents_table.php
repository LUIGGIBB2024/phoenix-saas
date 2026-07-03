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
        Schema::create('inventory_documents', function (Blueprint $table) {
            $table->id();
            $table->string('nit', 20)->nullable();
            $table->string('branch', 20)->nullable();
            $table->string('name', 255)->nullable();
            $table->integer('number')->nullable();
            $table->string('concept_inv', 20)->nullable();
            $table->string('concept_class', 20)->nullable()->default('001');
            $table->date('report_date')->nullable();
            $table->integer('purchase_invoice')->nullable()->default(0);
            $table->string('prefix', 20)->nullable();
            $table->string('documento_purchase', 20)->nullable();
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->decimal('subtotal', 20, 2)->nullable();
            $table->decimal('vatvalue', 20, 2)->nullable();
            $table->decimal('reteiva', 20, 2)->nullable();
            $table->decimal('reteica', 20, 2)->nullable();
            $table->decimal('products_discount', 20, 2)->nullable();
            $table->decimal('additional_discounts', 20, 2)->nullable();
            $table->decimal('additional_value', 20, 2)->nullable();
            $table->decimal('freight', 20, 2)->nullable();  // Flete
            $table->decimal('total_purchases', 20, 2)->nullable();

            $table->string('plate', 20)->nullable();

            $table->enum('type', ['Compras', 'Otras Entradas', 'Otras Salidas', 'Traslados', 'Devolución', 'Otras'])->default('Otras');
            $table->enum('type_of_purchase', ['Compras', 'Otras Entradas', 'Otras Salidas', 'Traslados', 'Devolución', 'Otras'])->default('Otras');
            $table->enum('state', ['Activo', 'Eliminado', 'Pendiente'])->nullable()->default('Activo');
            $table->string('state01', 20)->nullable();
            $table->string('state02', 20)->nullable();
            $table->string('state03', 20)->nullable();

            $table->unsignedBigInteger('companies_id')->index()->nullable();
            $table->foreign('companies_id')->references('id')->on('companies')->onDelete('set null');

            $table->string('proyect', 20)->nullable();
            $table->string('sproyect', 20)->nullable();
            $table->string('center', 20)->nullable();
            $table->string('activity', 20)->nullable();

            $table->index(['companies_id', 'report_date', 'number', 'prefix'], 'idx_companies_report_prefix');
            $table->index(['companies_id', 'report_date', 'nit'], 'idx_companies_report_nit');
            $table->index(['companies_id', 'proyect', 'report_date', 'nit'], 'idx_companies_proyect_report');

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
        Schema::dropIfExists('inventory_documents');
    }
};
