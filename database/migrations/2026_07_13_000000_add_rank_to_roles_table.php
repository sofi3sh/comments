<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedSmallInteger('rank')->default(1000)->after('guard_name');
        });

        $levels = [
            'Admin' => 10,
            'EditorMain' => 20,
            'Staff writer' => 30,
            'Blogger' => 40,
            'Company Representative' => 40,
            'Customer' => 50,
            'Blogger Candidate' => 50,
            'Company Representative Candidate' => 50,
        ];

        foreach ($levels as $roleName => $level) {
            DB::table('roles')
                ->where('guard_name', 'web')
                ->where('name', $roleName)
                ->update(['rank' => $level]);
        }
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('rank');
        });
    }
};
