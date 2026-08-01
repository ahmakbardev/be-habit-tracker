<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('job_title')->nullable()->after('bio');
            $table->string('company')->nullable()->after('job_title');
            $table->string('phone_mobile')->nullable()->after('company');
            $table->string('phone_work')->nullable()->after('phone_mobile');
            $table->text('mailing_address')->nullable()->after('phone_work');
            $table->string('timezone')->nullable()->after('mailing_address');
            $table->date('birthday')->nullable()->after('timezone');
            $table->json('tags')->nullable()->after('birthday');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'job_title',
                'company',
                'phone_mobile',
                'phone_work',
                'mailing_address',
                'timezone',
                'birthday',
                'tags',
            ]);
        });
    }
};
