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
        Schema::table('incidents', function (Blueprint $table) {
            // Add reportedBy field if it doesn't exist
            if (!Schema::hasColumn('incidents', 'reportedBy')) {
                $table->string('reportedBy')->nullable()->after('photoKey');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            if (Schema::hasColumn('incidents', 'reportedBy')) {
                $table->dropColumn('reportedBy');
            }
        });
    }
};
