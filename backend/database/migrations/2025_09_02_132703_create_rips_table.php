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
            $table->foreignUuid('process_batch_id')->constrained("process_batches")->comment('id del proceso de importación');
            $table->string('path_zip')->nullable()->comment('ruta del archivo zip');
            $table->bigInteger('numInvoices')->comment('cantidad de facturas');
            $table->integer('successfulInvoices')->default(0)->comment('cantidad de facturas completas');
            $table->integer('failedInvoices')->default(0)->comment('cantidad de facturas incompletas');
            $table->string('type')->comment('Tipo y método de subida (manual, zip, etc)');
            $table->decimal('sumVr', 15, 2)->comment('Sumatoria de todos los vrservicio de las facturas');
            $table->string('path_json')->nullable()->comment('ruta del archivo json Global');
            $table->string('path_excel')->nullable()->comment('ruta del archivo excel Global');
            $table->string('status');
            $table->string('nit')->nullable()->comment('Nit de la factura');

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
