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
        Schema::create('license_modules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('license_id')->constrained("licenses"); // Relación con la plantilla
            $table->string('module_name'); // ej: "products", "users", "invoices"
            $table->integer('max_records'); // cuántos permite crear
            $table->decimal('package_price', 10, 2); // precio del paquete completo
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_modules');
    }
};
