# Tenant Isolation — Suite de Testes

Esta suite garante que nenhum model sensível retorna dados de outros tenants. Se você criar um novo model que armazena dados por tenant, adicione-o à lista `sensitive_models` no arquivo `tests/TenantIsolationTestCase.php` para que o isolamento seja validado automaticamente.

Para incluir um novo model sensível, edite o array `sensitive_models` no `TenantIsolationTestCase`:

```php
protected array $sensitive_models = [
    \App\Models\User::class,
    \App\Models\SeuNovoModel::class, // adicione aqui
];
```

Para rodar a suite localmente:

```bash
php artisan test --testsuite=tenant-isolation
```
