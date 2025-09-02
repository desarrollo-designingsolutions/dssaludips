<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rips', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('company_id')->constrained();
            $table->foreignUuid('user_id')->constrained()->comment('Usuario que subio el zip');
            $table->foreignUuid('batch_id')->constrained()->comment('id del proceso de importación');
            $table->string('path_zip')->nullable()->comment('ruta del archivo zip');
            $table->bigInteger('numInvoices')->comment('cantidad de facturas');
            $table->integer('successfulInvoices')->default(0)->comment('cantidad de facturas completas');
            $table->integer('failedInvoices')->default(0)->comment('cantidad de facturas incompletas');
            $table->string('status');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rips');
    }
};
