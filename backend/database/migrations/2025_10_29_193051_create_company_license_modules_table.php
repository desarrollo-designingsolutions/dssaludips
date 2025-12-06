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
        Schema::create('company_license_modules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_license_id')->constrained("company_licenses"); // Relación con company_licenses
            $table->string('module_name'); // ej: "products", "users"
            $table->integer('max_records'); // límite personalizado
            $table->decimal('package_price', 10, 2); // precio negociado
            $table->integer('current_count')->default(0); // uso real
            $table->timestamp('last_reset_at')->nullable(); // cuándo se reinició
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_license_modules');
    }
};
