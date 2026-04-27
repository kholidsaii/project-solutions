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
        Schema::table('work_categories', function (Blueprint $table) {
            // Tambahkan kolom image_path (nullable agar data lama tidak error)
            $table->string('image_path')->nullable()->after('name');
            
            // Jika kamu juga butuh kolom icon (buat FontAwesome)
            if (!Schema::hasColumn('work_categories', 'icon')) {
                $table->string('icon')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('work_categories', function (Blueprint $table) {
            $table->dropColumn(['image_path', 'icon']);
        });
    }
};
