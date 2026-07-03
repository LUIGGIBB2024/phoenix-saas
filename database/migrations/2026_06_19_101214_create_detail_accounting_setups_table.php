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
        Schema::create('detail_accounting_setups', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->nullable();

            // 1. Relación con la tabla principal (Foreign Key)
            // Usamos unsignedBigInteger para que coincida perfectamente con el id() de accounting_setups
            $table->unsignedBigInteger('accounting_setup_id')->index();
            $table->foreign('accounting_setup_id')
                ->references('id')
                ->on('accounting_setups')
                ->onDelete('cascade'); // Si se elimina la configuración principal, se eliminan sus detalles

            // 2. Campos de Negocio / Configuración Contable
            //$table->string('concept', 150)->comment('Ej: Ingresos, IVA, Retención, Cartera');
            $table->string('accounting_account', 20)->comment('El código de la cuenta contable (PUC)');
            $table->enum('type', ['DEBITO', 'CREDITO'])->comment('Naturaleza del movimiento en el asiento');

            // Opcional: Si manejas dinámicamente porcentajes (ej: % de retención o IVA)
            $table->decimal('percentage', 5, 2)->default(0.00)->comment('Porcentaje aplicable si aplica');

            // 3. Campos de Auditoría (Manteniendo tu estándar)
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->string('usercreate', 20)->nullable()->default('System');
            $table->string('userupdate', 20)->nullable()->default('System');

            // 4. Índice compuesto para optimizar búsquedas por configuración
            $table->index(['accounting_setup_id', 'id'], 'idx_accounting_setup_detail');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_accounting_setups');
    }
};
