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
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('legal_name')->nullable()->after('name');
            $table->string('document_number', 14)->nullable()->after('legal_name');
            $table->string('trade_name')->nullable()->after('document_number');
            $table->string('main_email')->nullable()->after('trade_name');
            $table->string('phone', 32)->nullable()->after('main_email');
            $table->string('operational_profile', 32)->nullable()->after('phone');
            $table->boolean('emits_metrological_certificate')->default(false)->after('operational_profile');

            $table->unique('document_number', 'tenants_document_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropUnique('tenants_document_number_unique');
            $table->dropColumn([
                'legal_name',
                'document_number',
                'trade_name',
                'main_email',
                'phone',
                'operational_profile',
                'emits_metrological_certificate',
            ]);
        });
    }
};
