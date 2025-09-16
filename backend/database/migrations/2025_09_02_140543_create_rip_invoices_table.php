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
        Schema::create('rip_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('company_id')->constrained();
            $table->foreignUuid('rip_id')->constrained();
            $table->string('invoice_number')->comment('Columna del txt (numFactura), archivo AF');
            $table->string('status')->comment("Estado de la factura");
            $table->string('status_xml')->nullable()->comment("Estado del XML de la factura");
            $table->string('users_count')->default(0)->comment('cantidad de usaurios en la factura');
            $table->string('path_json')->nullable()->comment('ruta del archivo json');
            $table->string('path_xml')->nullable()->comment('ruta del archivo xml');
            $table->string('path_excel')->nullable()->comment('ruta del archivo excel');
            $table->decimal('sumVr', 15, 2)->nullable()->comment('Sumatoria de todos los vrservicio de las facturas');
            $table->string('count_users')->nullable()->comment('Cantidad de usuarios en la factura');
            $table->string('note_type')->nullable()->comment('tipo de nota en la factura');
            $table->string('note_number')->nullable()->comment('numero de nota en la factura');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rip_invoices');
    }
};
