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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->nullable()->index();
            $table->string('name', 255)->nullable();
            $table->string('store', 20)->nullable();
            $table->string('batch', 20)->nullable();
            $table->date('report_date')->nullable();
            $table->integer('number')->nullable();
            $table->string('prefix', 20)->nullable();
            $table->string('concept_inv', 20)->nullable();
            $table->string('concept_class', 20)->nullable()->default('001');
            $table->string('nit', 20)->nullable();
            $table->string('nit2', 20)->nullable();
            $table->string('branch', 20)->nullable();
            $table->string('health_batch', 20)->nullable();
            $table->string('plate', 20)->nullable();
            $table->string('serial', 20)->nullable();
            $table->decimal('amount', 20, 8)->nullable();
            $table->decimal('amount1', 20, 8)->nullable();
            $table->decimal('vat', 6, 2)->nullable();
            $table->decimal('discount1', 6, 2)->nullable();
            $table->decimal('discount2', 6, 2)->nullable();
            $table->decimal('discount3', 6, 2)->nullable();
            $table->decimal('unit_cost', 20, 8)->nullable();
            $table->decimal('sale_price', 20, 8)->nullable();
            $table->decimal('parcial_value', 20, 8)->nullable();
            $table->enum('type', ['FACT', 'DEVC', 'COMP', 'DEVP', 'OTRE', 'OTRS'])->default('FACT');
            $table->integer('idregister')->nullable()->default(1);
            $table->enum('state', ['Activo', 'Eliminado', 'Pendiente'])->nullable()->default('Activo');
            $table->string('state01', 20)->nullable();
            $table->string('state02', 20)->nullable();
            $table->string('state03', 20)->nullable();

            $table->unsignedBigInteger('companies_id')->index()->nullable();
            $table->foreign('companies_id')->references('id')->on('companies')->onDelete('set null');

            $table->unsignedBigInteger('inventory_documents_id')->index()->nullable();
            $table->foreign('inventory_documents_id')->references('id')->on('inventory_documents')->onDelete('set null');

            $table->string('proyect', 20)->nullable();
            $table->string('sproyect', 20)->nullable();
            $table->string('center', 20)->nullable();
            $table->string('activity', 20)->nullable();

            $table->index(['companies_id', 'report_date', 'number', 'code', 'store', 'idregister'], 'idx_companies_report_code');
            $table->index(['companies_id', 'report_date', 'nit'], 'idx_companies_report');
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
        Schema::dropIfExists('inventory_movements');
    }
};
