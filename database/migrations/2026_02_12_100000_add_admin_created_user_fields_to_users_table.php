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
        Schema::table('users', function (Blueprint $table) {
            $table->string('surname')->nullable()->after('name');
            $table->string('facebook_url', 500)->nullable()->after('avatar');
            $table->string('linkedin_url', 500)->nullable()->after('facebook_url');
            $table->string('twitter_url', 500)->nullable()->after('linkedin_url');
            $table->boolean('personal_data_processed')->default(false)->after('twitter_url');
            $table->boolean('site_rules_accepted')->default(false)->after('personal_data_processed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'surname',
                'facebook_url',
                'linkedin_url',
                'twitter_url',
                'personal_data_processed',
                'site_rules_accepted',
            ]);
        });
    }
};