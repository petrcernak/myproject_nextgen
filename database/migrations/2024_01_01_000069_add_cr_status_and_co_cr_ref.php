<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->string('status', 20)->default('open')->after('date');
        });

        Schema::table('change_orders', function (Blueprint $table) {
            $table->foreignId('change_request_id')->nullable()->after('amendment_id')
                ->constrained('change_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('change_orders', function (Blueprint $table) {
            $table->dropForeign(['change_request_id']);
            $table->dropColumn('change_request_id');
        });
        Schema::table('change_requests', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
