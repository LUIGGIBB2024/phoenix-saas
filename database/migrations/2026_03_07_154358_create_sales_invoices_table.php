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
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->date('date_issue')->nullable()->index();
            $table->date('expiration_date')->nullable();
            $table->dateTime('entry_date')->nullable();
            $table->dateTime('departure_date')->nullable();
            $table->decimal('number', 20, 0)->nullable();
            $table->string('prefix', 20)->nullable();
            $table->string('document_name', 30)->nullable();
            $table->string('customer', 20)->nullable()->index();
            $table->string('branch', 20)->nullable();
            $table->string('patient_id', 20)->nullable();
            $table->string('client_name', 255)->nullable();
            $table->decimal('subtotal', 20, 2)->nullable();
            $table->decimal('vatvalue', 20, 2)->nullable();
            $table->decimal('reteiva', 20, 2)->nullable();
            $table->decimal('reteica', 20, 2)->nullable();
            $table->decimal('impoconsumo', 20, 2)->nullable();
            $table->decimal('products_discount', 20, 2)->nullable();
            $table->decimal('additional_discounts', 20, 2)->nullable();
            $table->decimal('exempt_sales', 20, 2)->nullable();
            $table->decimal('taxed_sales', 20, 2)->nullable();
            $table->decimal('additional_value', 20, 2)->nullable();
            $table->decimal('cost_of_sale', 20, 2)->nullable();
            $table->decimal('payment_value', 20, 2)->nullable();
            $table->decimal('health_copays', 20, 2)->nullable();
            $table->decimal('health_advances', 20, 2)->nullable();
            $table->decimal('health_moderator_fee', 20, 2)->nullable();
            $table->decimal('tip', 20, 2)->nullable();      // Propina
            $table->integer('hours')->nullable()->default(0);
            $table->integer('minutes')->nullable()->default(0);
            $table->integer('total_items')->nullable()->default(0);
            $table->decimal('total_sale', 20, 2)->nullable();

            $table->string('cufe', 255)->nullable();
            $table->string('observations', 255)->nullable();
            $table->string('plate', 20)->nullable();
            $table->string('room', 20)->nullable();
            $table->string('purchase_orders', 20)->nullable();
            $table->string('document_number', 20)->nullable();
            $table->string('property', 20)->nullable();
            $table->string('authorization', 20)->nullable();
            $table->string('type_operation', 20)->nullable();
            $table->string('scenery', 20)->nullable();
            $table->string('conveyor', 20)->nullable();
            $table->string('table', 20)->nullable();
            $table->integer('order')->nullable()->default(0);

            $table->string('seller', 20)->nullable();
            $table->string('route', 20)->nullable();
            $table->string('zone', 20)->nullable();
            $table->string('typecustomer', 20)->nullable();
            $table->string('box', 20)->nullable();
            $table->string('atm', 20)->nullable();
            $table->string('list', 20)->nullable();

            $table->string('proyect', 20)->nullable();
            $table->string('sproyect', 20)->nullable();
            $table->string('center', 20)->nullable();
            $table->string('activity', 20)->nullable();

            $table->enum('state', ['Activo', 'Eliminado', 'Pendiente'])->nullable()->default('Activo');
            $table->enum('type', ['Contado', 'Crédito'])->nullable()->default('Contado');

            $table->index(['number', 'prefix', 'customer', 'companies_id'], 'idx_number_prefix_customer');
            $table->index(['companies_id', 'proyect', 'date_issue', 'customer'], 'idx_companies_proyect_date_issue');

            $table->unsignedBigInteger('companies_id')->index()->nullable();
            $table->foreign('companies_id')->references('id')->on('companies')->onDelete('set null');

            $table->unsignedBigInteger('payment_methods_id')->index()->nullable();
            $table->foreign('payment_methods_id')->references('id')->on('payment_methods')->onDelete('set null');

            $table->unsignedBigInteger('payment_forms_id')->index()->nullable();
            $table->foreign('payment_forms_id')->references('id')->on('payment_forms')->onDelete('set null');

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
        Schema::dropIfExists('sales_invoices');
    }
};
