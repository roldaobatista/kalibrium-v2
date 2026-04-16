<?php

/**
 * Payloads de SQL injection compartilhados entre TenantIsolationHttpTest e TenantIsolationSecurityTest.
 *
 * @ac AC-016
 */
return [
    'OR 1=1 clássico' => ['1 OR 1=1'],
    'UNION SELECT' => ['1 UNION SELECT id,name FROM tenants--'],
    'UNION SELECT multi-coluna' => ['1 UNION SELECT id,name,email,tenant_id FROM users--'],
    'Aspas simples' => ["1' OR '1'='1"],
    'Ponto e vírgula DROP TABLE users' => ['1; DROP TABLE users; --'],
    'DROP TABLE tenants' => ['1; DROP TABLE tenants; --'],
    'Subquery tenant_id' => ['0 OR (SELECT tenant_id FROM users LIMIT 1) IS NOT NULL'],
    'OR negação de tenant' => ['1 OR tenant_id != 999'],
    'Slug LIKE wildcard' => ["' OR slug LIKE '%"],
    'Comentário SQL inline' => ['1/* comment */OR/* */1=1'],
    'Hex encoding' => ['1 OR 0x31=0x31'],
    'Double dash comment' => ["1'--"],
];
