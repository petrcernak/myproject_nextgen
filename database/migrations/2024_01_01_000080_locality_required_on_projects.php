<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill: assign a locality to every project that lacks one
        $groupIds = DB::table('projects')
            ->whereNull('locality_id')
            ->distinct()
            ->pluck('id_group');

        foreach ($groupIds as $groupId) {
            $localityId = DB::table('localities')
                ->where('id_group', $groupId)
                ->orderBy('name')
                ->value('id');

            if (!$localityId) {
                $localityId = DB::table('localities')->insertGetId([
                    'id_group'   => $groupId,
                    'name'       => 'General',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('projects')
                ->where('id_group', $groupId)
                ->whereNull('locality_id')
                ->update(['locality_id' => $localityId]);
        }

        // Make locality_id NOT NULL
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedBigInteger('locality_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedBigInteger('locality_id')->nullable()->change();
        });
    }
};
