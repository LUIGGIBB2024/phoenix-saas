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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->nullable()->index();
            $table->string('name', 255)->nullable();
            $table->string('unit_of_measure', 20)->nullable();
            $table->string('codereference', 20)->nullable();
            $table->string('presentation', 20)->nullable();
            $table->string('location', 20)->nullable();

            $table->enum('control_id', ['Inventario', 'Servicio'])->default('Inventario');
            $table->enum('typeofproduct', ['Normal', 'Peso', 'Restaurante', 'Insumos', 'Compuesto', 'Combustibe', 'Gas'])->default('Normal');
            $table->enum('require_scale', ['Si', 'No'])->default('No');
            $table->enum('billable', ['Si', 'No'])->default('i');

            $table->string('group', 20)->nullable();
            $table->string('subgroup', 20)->nullable();
            $table->string('division', 20)->nullable();
            $table->string('category', 20)->nullable();
            $table->string('family', 20)->nullable();
            $table->string('namephoto', 50)->nullable();
            $table->string('routephoto', 255)->nullable();
            $table->string('observations', 255)->nullable();

            $table->string('cups', 20)->nullable();
            $table->string('alternate_code', 20)->nullable();
            $table->string('cie10_code', 20)->nullable();
            $table->string('invima_register', 20)->nullable();

            $table->decimal('percent', 20, 2)->nullable();
            $table->decimal('units_per_packaging', 20, 2)->nullable();
            $table->decimal('weight_volume', 20, 2)->nullable();
            $table->decimal('conversion_factor', 20, 2)->nullable();
            $table->decimal('sale_value', 20, 8)->nullable();

            $table->date('date_last_purchase')->nullable();
            $table->decimal('minimum_stock', 20, 2)->nullable();
            $table->decimal('maximum_stock', 20, 2)->nullable();
            $table->decimal('profitability', 20, 2)->nullable();
            $table->decimal('required_scale', 20, 2)->nullable();
            $table->decimal('consumption_tax', 20, 2)->nullable();
            $table->decimal('listvalue1', 20, 2)->nullable();
            $table->decimal('listvalue2', 20, 2)->nullable();
            $table->decimal('listvalue3', 20, 2)->nullable();
            $table->decimal('cost', 20, 2)->nullable();
            $table->enum('state', ['Activo', 'Eliminado', 'Pendiente'])->nullable()->default('Activo');

            $table->index(['companies_id', 'code'], 'idx_companies_code');

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
        Schema::dropIfExists('products');
    }
};
