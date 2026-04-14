<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'id'], 'companies_tenant_id_unique');
        });

        Schema::table('branches', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'id'], 'branches_tenant_id_unique');
            $table->unique(['tenant_id', 'company_id', 'id'], 'branches_tenant_company_id_unique');
        });

        Schema::table('tenant_users', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->after('user_id')->constrained('companies')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('company_id')->constrained('branches')->nullOnDelete();
            $table->timestamp('invited_at')->nullable()->after('requires_2fa');
            $table->timestamp('accepted_at')->nullable()->after('invited_at');
            $table->string('invitation_token_hash', 64)->nullable()->after('accepted_at')->unique();
            $table->timestamp('invitation_expires_at')->nullable()->after('invitation_token_hash');

            $table->foreign(['tenant_id', 'company_id'], 'tenant_users_company_tenant_fk')
                ->references(['tenant_id', 'id'])
                ->on('companies');
            $table->foreign(['tenant_id', 'company_id', 'branch_id'], 'tenant_users_branch_company_tenant_fk')
                ->references(['tenant_id', 'company_id', 'id'])
                ->on('branches');
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('tenant_users', function (Blueprint $table): void {
            $table->dropForeign('tenant_users_branch_company_tenant_fk');
            $table->dropForeign('tenant_users_company_tenant_fk');
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropUnique(['invitation_token_hash']);
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn([
                'invited_at',
                'accepted_at',
                'invitation_token_hash',
                'invitation_expires_at',
            ]);
        });

        Schema::table('branches', function (Blueprint $table): void {
            $table->dropUnique('branches_tenant_company_id_unique');
            $table->dropUnique('branches_tenant_id_unique');
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->dropUnique('companies_tenant_id_unique');
        });
    }
};
