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
        Schema::create('control_consecutives', function (Blueprint $table) {
            $table->id();
            $table->string('lapso', 6)->nullable();
            $table->integer('consecutive')->nullable()->default(0);

            $table->unsignedBigInteger('companies_id')->index()->nullable();
            $table->foreign('companies_id')->references('id')->on('companies')->onDelete('set null');

            // $table->unsignedBigInteger('general_documents_id')->index()->nullable();
            // $table->foreign('general_documents_id')->references('id')->on('general_documents')->onDelete('set null');

            $table->index(['companies_id', 'lapso'], 'idx_companiesid_generaldocuments');

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
        Schema::dropIfExists('control_consecutives');
    }
};
