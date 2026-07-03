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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('nit', 20)->nullable();
            $table->string('branch', 20)->nullable();
            $table->string('dv', 1)->nullable();
            $table->string('patient_id', 20)->nullable();
            $table->string('code', 20)->nullable();
            $table->string('name', 255)->nullable();
            $table->string('firstname', 255)->nullable();
            $table->string('lastname', 255)->nullable();
            $table->string('comercial_name', 255)->nullable();

            $table->string('address', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 255)->nullable();

            $table->string('nit_representative', 20)->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('name_contact', 255)->nullable();
            $table->string('email_contact', 255)->nullable();

            $table->string('health_contract_number', 20)->nullable();
            $table->string('health_policy_number', 20)->nullable();

            $table->decimal('credit_quota', 20, 8)->nullable();
            $table->integer('deadline_days')->nullable();
            $table->integer('point')->nullable();
            $table->integer('accumulated_points')->nullable();
            // $table->point('location')->nullable();
            $table->date('birthday')->nullable();
            $table->date('last_purchase_date')->nullable();
            $table->date('creation_date')->nullable();
            $table->string('provider_code', 20)->nullable();
            // $table->string('route', 20)->nullable();
            // $table->string('zone', 20)->nullable();
            $table->string('latitude', 20)->nullable();
            $table->string('longitude', 20)->nullable();

            $table->string('economic_activity', 20)->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->string('business_registration', 20)->nullable();

            $table->string('sales_account', 20)->nullable();
            $table->string('center', 10)->nullable();
            $table->string('scenter', 10)->nullable();

            $table->unsignedBigInteger('health_service_coverage_id')->index()->nullable();
            $table->foreign('health_service_coverage_id')->references('id')->on('miscellaneous_items')->onDelete('set null');

            $table->unsignedBigInteger('health_payment_method_id')->index()->nullable();
            $table->foreign('health_payment_method_id')->references('id')->on('miscellaneous_items')->onDelete('set null');

            $table->unsignedBigInteger('branch_id')->index()->nullable();
            $table->foreign('branch_id')->references('id')->on('miscellaneous_items')->onDelete('set null');

            $table->unsignedBigInteger('route_id')->index()->nullable();
            $table->foreign('route_id')->references('id')->on('miscellaneous_items')->onDelete('set null');

            $table->unsignedBigInteger('zone_id')->index()->nullable();
            $table->foreign('zone_id')->references('id')->on('miscellaneous_items')->onDelete('set null');

            $table->unsignedBigInteger('type_id')->index()->nullable();
            $table->foreign('type_id')->references('id')->on('miscellaneous_items')->onDelete('set null');

            $table->unsignedBigInteger('neighborhood_id')->index()->nullable();
            $table->foreign('neighborhood_id')->references('id')->on('miscellaneous_items')->onDelete('set null');

            $table->unsignedBigInteger('price_list_id')->index()->nullable();
            $table->foreign('price_list_id')->references('id')->on('price_lists')->onDelete('set null');

            $table->unsignedBigInteger('municipalities_id')->index()->nullable();
            $table->foreign('municipalities_id')->references('id')->on('municipalities')->onDelete('set null');

            $table->unsignedBigInteger('sellers_id')->index()->nullable();
            $table->foreign('sellers_id')->references('id')->on('sellers')->onDelete('set null');

            $table->unsignedBigInteger('type_document_identification_id')->index()->nullable();
            $table->foreign('type_document_identification_id')->references('id')->on('type_document_identifications')->onDelete('set null');

            $table->unsignedBigInteger('companies_id')->index()->nullable();
            $table->foreign('companies_id')->references('id')->on('companies')->onDelete('set null');

            $table->unsignedBigInteger('type_regime_id')->index()->nullable();
            $table->foreign('type_regime_id')->references('id')->on('type_regimes')->onDelete('set null');

            $table->unsignedBigInteger('type_liability_id')->index()->nullable();
            $table->foreign('type_liability_id')->references('id')->on('type_liabilities')->onDelete('set null');

            $table->index(['companies_id',  'nit', 'branch'], 'idx_companiesid_nit_branch');

            $table->enum('sex', ['Masculino', 'Femenino', 'Otro'])->nullable()->default('Masculino');
            $table->enum('state', ['Activo', 'Inactivo'])->nullable()->default('Activo');
            $table->enum('typeofcurrency', ['Pesos', 'Dólares'])->nullable()->default('Pesos');
            $table->enum('retesource', ['Si', 'No'])->nullable()->default('No');
            $table->enum('reteiva', ['Si', 'No'])->nullable()->default('No');
            $table->enum('reteica', ['Si', 'No'])->nullable()->default('No');
            $table->enum('declare_income', ['Si', 'No'])->nullable()->default('No');
            $table->enum('control_points', ['Si', 'No'])->nullable()->default('No');
            $table->enum('capture_signature', ['Si', 'No'])->nullable()->default('No');

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
        Schema::dropIfExists('customers');
    }
};
