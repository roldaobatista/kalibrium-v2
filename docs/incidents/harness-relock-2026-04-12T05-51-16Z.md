# Incidente — relock do harness

**Data UTC:** 2026-04-12T05:51:17Z
**Operador (git):** roldaobatista <roldaobatista@users.noreply.github.com>
**Host:** Roldao-Solution
**Origem:** `scripts/relock-harness.sh` invocado manualmente.

## Por que existe este registro

Cada relock do harness é uma operação privilegiada (recria os selos que
protegem `.claude/settings.json` e `scripts/hooks/*` contra auto-modificação).
Toda execução cria um incidente para auditoria — exigido pelo PM em
2026-04-10 como parte do item 1.2 (adição) do meta-audit action plan.

## Selos antes → depois

| Arquivo | Hash anterior | Hash novo |
|---|---|---|
| `.claude/settings.json` | `62e24a20920c181b…` | `b483b1a58ce14de1…` |
| `scripts/hooks/MANIFEST.sha256` (sha do próprio manifesto) | `3f0285089357880f…` | `3f0285089357880f…` |

## Hooks atualmente catalogados (17)

```
666ac6e787d14cad2d3ea9f8c4b0627d9c2ee1acbd2a06f972894177f0d57041  block-project-init.sh
ccb65334d6d050fbcfb46d849595a7d3b93909424bb05f7e67cf8634bdcce3f5  collect-telemetry.sh
bb7dcfb44cf734c3a447932e4832014f690dd805966e7efbfa4c6c9535738f36  edit-scope-check.sh
e37b7465f070225f9e6cd1c978dd19ddd38e68ddabfe2a034a77c487d4a91598  forbidden-files-scan.sh
214696393dcc6366ce7adcf9f983f4b3b85af863298500103e5c39df38c82c05  hooks-lock.sh
abdee81f9d1dd88698393ee0514a8996e90204e9ecc6e637e3178e1d38af2f51  post-edit-gate.sh
334e91dc2acc45113b86cc5bd84d8482997c41ae00c30647ae5f0772fb435c7f  pre-commit-gate.sh
dcaa8779d5666866cb430c18c580e25f0e509829a75fff03778011187662c9eb  pre-push-gate.sh
25a4dca517e19317073481370e80b7ec878729a3ec51b57d46f009996ca72730  read-secrets-block.sh
3d5514dbe81fa1fbd7dc920bdd002a2ad8f72fc0a6f00ee0b096727e0764ba14  record-subagent-usage.sh
c045e54dcf8748b31350838ec132300e2185590520cf05b379d1f181e4c1926b  sealed-files-bash-lock.sh
8a34198d95f7e01a568d56d68b1615f386ab697018e6c22710ac0f5b5d3c7528  session-start.sh
32ab3a2e99db8c587e44344d31a8e05633bc2293d3e02b24f54170dd6dc1038f  settings-lock.sh
859211f7485350e94dfee14ab4de5e3df25f96eaa6d86329c51774bc5aee643e  stop-gate.sh
a1bf1a8deec6a088a888d2c7d91ece0617ceb7c14a48f5f4c0ce512367fc3cbb  telemetry-lock.sh
4169233825b1c89c731575c2faa7eb1f265ae34ae3f729ae2cb6a96699f0ebdb  user-prompt-submit.sh
1b65084d6331f1f5ad732fe5e44c0a130715cae69f0ea25a225975d0876f5ae2  verifier-sandbox.sh
```

## Ação requerida do PM

- [ ] Validar que o relock é esperado (ex: você acabou de editar um hook intencionalmente)
- [ ] Se NÃO esperado: investigar como ocorreu (possível bypass dos hooks de segurança)
- [ ] Commitar este incidente junto com as mudanças do harness para rastreabilidade

