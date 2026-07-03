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
        Schema::create('general_documents', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->nullable()->index();
            $table->string('name', 255)->nullable();
            $table->integer('consecutive')->nullable()->default(0);

            $table->enum('type', ['Clientes', 'Proveedores', 'Inventarios'])->default('Clientes');
            $table->enum('typedocument1', ['Facturas', 'Nota Credito', 'Nota Debito', 'Recibos', 'No Aplica'])->default('No Aplica');
            $table->enum('typedocument2', ['Compras', 'Entradas', 'Salidas', 'Dev Proveedores', 'Dev Clientes', 'Requisiciones', 'Ventas'])->default('Ventas');
            $table->enum('typedocument3', ['Facturas', 'Egresos', 'Causaciones', 'Documento Soporte', 'Otros Movimientos', 'No Aplica'])->default('No Aplica');
            $table->enum('typedocument4', ['Factura de Contado', 'Factura Crédito', 'No Aplica'])->default('Factura de Contado');
            $table->enum('controlconsecutive', ['Único', 'Global', 'Lapso'])->default('Único');
            $table->enum('state', ['Activo', 'Eliminado', 'Pendiente'])->nullable()->default('Activo');

            $table->unsignedBigInteger('companies_id')->index()->nullable();
            $table->foreign('companies_id')->references('id')->on('companies')->onDelete('set null');

            $table->unsignedBigInteger('resolutions_id')->index()->nullable();
            $table->foreign('resolutions_id')->references('id')->on('resolutions')->onDelete('set null');

            $table->unsignedBigInteger('control_consecutives_id')->index()->nullable();
            $table->foreign('control_consecutives_id')->references('id')->on('control_consecutives')->onDelete('set null');

            $table->unsignedBigInteger('accounting_setups_id')->index()->nullable();
            $table->foreign('accounting_setups_id')->references('id')->on('accounting_setups')->onDelete('set null');

            $table->index(['companies_id', 'code', 'type'], 'idx_companies_code_type');

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
        Schema::dropIfExists('general_documents');
    }
};
