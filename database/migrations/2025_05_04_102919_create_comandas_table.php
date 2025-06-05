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
        Schema::create('comandas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // mesero o cajero
            $table->timestamp('fecha')->useCurrent();
            $table->enum('estado', ['abierta', 'cerrada'])->default('abierta');
            $table->decimal('iva', 4, 2)->nullable()->after('estado'); // Porcentaje de IVA (ej: 0.21)
            $table->decimal('total_con_iva', 10, 2)->nullable()->after('iva');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comandas');
    }
};
