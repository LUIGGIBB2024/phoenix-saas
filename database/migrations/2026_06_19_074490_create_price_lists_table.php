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
        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->nullable();
            $table->string('name', 255)->nullable();

            // Opción moderna y limpia:
            //$table->foreignId('prices_details_id')->nullable()->constrained('prices_details')->onDelete('set null');

            // O la forma tradicional paso a paso:
            // En price_lists migration
            // $table->unsignedBigInteger('price_details_id')->nullable();  // ✅ sin "s" extra
            // $table->foreign('price_details_id')->references('id')->on('price_details')->onDelete('set null');

            $table->unsignedBigInteger('companies_id')->index()->nullable();
            $table->foreign('companies_id')->references('id')->on('companies')->onDelete('set null');

            $table->index(['companies_id',  'code'], 'idx_companiesid_code');

            $table->enum('state', ['Activo', 'Inactivo'])->nullable()->default('Activo');
            $table->enum('typeofcurrency', ['Pesos', 'Dólares'])->nullable()->default('Pesos');
            $table->decimal('sale_value', 20, 8)->nullable();
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
        Schema::dropIfExists('price_lists');
    }
};
