<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comandas', function (Blueprint $table) {
            $table->foreignId('mesa_id')->nullable()->constrained('mesas')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('comandas', function (Blueprint $table) {
            $table->dropForeign(['mesa_id']);
            $table->dropColumn('mesa_id');
        });
    }
};