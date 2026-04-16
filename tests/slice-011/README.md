# Tenant Isolation — Suite de Testes

Esta suite garante que nenhum model sensível retorna dados de outros tenants. Se você criar um novo model que armazena dados por tenant, adicione-o à chave `sensitive_models` em `config/tenancy.php` para que o isolamento seja validado automaticamente.

Para incluir um novo model sensível, edite `config/tenancy.php`:

```php
'sensitive_models' => [
    \App\Models\TenantUser::class,
    \App\Models\ConsentSubject::class,
    \App\Models\ConsentRecord::class,
    \App\Models\LgpdCategory::class,
    \App\Models\SeuNovoModel::class, // adicione aqui
],
```

O dataset do teste AC-001 (`sensitive_models_query_methods`) lê esta config dinamicamente — novos models entram na cobertura automaticamente sem alterar nenhum arquivo de teste.

Para rodar a suite localmente:

```bash
php artisan test --testsuite=tenant-isolation
```
