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
        Schema::create('detail_cxp_others_payments', function (Blueprint $table) {
            $table->id();

            // NUEVA RELACIÓN: Apunta a la cabecera de pagos
            $table->unsignedBigInteger('cxp_payment_id')->index()->nullable();
            $table->foreign('cxp_payment_id')->references('id')->on('cxp_payments')->onDelete('cascade');

            $table->integer('consecutive')->nullable();
            $table->string('document', 20)->nullable();
            $table->string('nit', 20)->nullable();
            $table->string('branch', 20)->nullable();
            $table->date('report_date')->nullable()->index();
            $table->string('internaldoc', 20)->nullable();
            $table->string('concept', 20)->nullable();
            $table->string('accounting_code', 20)->nullable();
            $table->string('center', 10)->nullable();
            $table->string('scenter', 10)->nullable();
            $table->string('proyect', 20)->nullable();
            $table->string('sproyect', 20)->nullable();
            $table->string('activity', 20)->nullable();
            $table->integer('idregister')->nullable();
            $table->enum('calculate', ['Suma', 'Resta', 'No Aplica'])->nullable()->default('Suma');
            $table->enum('state', ['Activo', 'Eliminado', 'Pendiente'])->nullable()->default('Activo');
            $table->string('state01', 20)->nullable();
            $table->string('state02', 20)->nullable();
            $table->string('state03', 20)->nullable();

            $table->index(['companies_id', 'report_date', 'consecutive', 'document', 'concept', 'nit', 'idregister'], 'idx_companies_report_consecutive');
            $table->index(['companies_id', 'report_date', 'nit'], 'idx_companies_report_report_date');

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
        Schema::dropIfExists('detail_cxp_others_payments');
    }
};
