<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('id');
            $table->string('size_key')->nullable()->after('parent_id');

            $table->foreign('parent_id')->references('id')->on('attachments')->cascadeOnDelete();

            $table->index('parent_id');
            $table->index('size_key');
            $table->unique(['parent_id', 'size_key']);
        });
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropUnique(['parent_id', 'size_key']);
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['size_key']);
            $table->dropColumn(['parent_id', 'size_key']);
        });
    }
};
