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
        Schema::create('cxp_payments', function (Blueprint $table) {
            $table->id();
            $table->string('nit', 20)->nullable();
            $table->string('branch', 20)->nullable();
            $table->string('lapse', 6)->nullable();
            $table->date('report_date')->nullable()->index();
            $table->date('check_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->integer('consecutive')->nullable();
            $table->string('document', 20)->nullable();
            $table->string('supplier_name', 255)->nullable();
            $table->decimal('value_cxp', 20, 2)->nullable();
            $table->decimal('others_payments', 20, 2)->nullable();
            $table->string('observations', 255)->nullable();
            $table->string('payment_method', 20)->nullable(); // Corregido el espacio
            $table->integer('check_number')->nullable();
            $table->enum('payment_type', ['PagosFacturas', 'OtrosPagos'])->nullable()->default('PagosFacturas');
            $table->enum('state', ['Activo', 'Eliminado', 'Pendiente'])->nullable()->default('Activo');
            $table->string('state01', 20)->nullable();
            $table->string('state02', 20)->nullable();
            $table->string('state03', 20)->nullable();

            $table->string('proyect', 20)->nullable();
            $table->string('sproyect', 20)->nullable();
            $table->string('center', 20)->nullable();
            $table->string('activity', 20)->nullable();

            $table->index(['companies_id', 'report_date', 'consecutive', 'document'], 'idx_companies_report');
            $table->index(['companies_id', 'report_date', 'nit'], 'idx_companies_nit');
            $table->index(['companies_id', 'proyect', 'report_date', 'nit'], 'idx_companies_proyect_proyect_report');

            $table->unsignedBigInteger('suppliers_id')->index()->nullable();
            $table->foreign('suppliers_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->unsignedBigInteger('companies_id')->index()->nullable();
            $table->foreign('companies_id')->references('id')->on('companies')->onDelete('set null');

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
        Schema::dropIfExists('cxp_payments');
    }
};
