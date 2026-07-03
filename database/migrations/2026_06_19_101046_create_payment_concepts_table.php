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
        Schema::create('payment_concepts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->nullable()->index();
            $table->string('name', 255)->nullable();
            $table->string('description', 255)->nullable();
            $table->string('account', 20)->nullable();
            $table->string('center', 20)->nullable();
            $table->string('scenter', 20)->nullable();

            $table->enum('type', ['Clientes', 'Proveedores',])->default('Clientes');
            $table->enum('typemovement', ['Débito', 'Crédito'])->default('Débito');
            $table->enum('typeofcalculation', ['Abonos', 'Descuentos', 'Retenciones', 'Ingreso x Intereses', 'Ingreso x Mayor Valor', 'Otros Ingresos', 'Ingreso x Pre´stamos'])->default('Abonos');
            $table->enum('aplicateaccount', ['Si', 'No'])->default('No');
            $table->enum('generatenote', ['Si', 'No'])->default('No');
            $table->enum('advances', ['Si', 'No'])->default('No');
            $table->enum('indicators', ['No Aplica', 'Descuentos a PropietariosNo', 'Otros'])->default('No Aplica');

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
        Schema::dropIfExists('payment_concepts');
    }
};
