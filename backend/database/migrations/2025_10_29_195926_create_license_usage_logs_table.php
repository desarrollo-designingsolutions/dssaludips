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
        Schema::create('license_usage_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_license_id')->constrained("company_licenses"); // A qué licencia pertenece
            $table->string('module_name'); // ej: "products"
            $table->string('action');
            $table->uuidMorphs('record');  // ← ¡POLIMÓRFICO!
            $table->foreignUuid('user_id')->constrained("users");            // Quién lo hizo
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_usage_logs');
    }
};
