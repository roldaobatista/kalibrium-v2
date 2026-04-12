# E10 — GED: Gestão Eletrônica de Documentos

## Objetivo
Implementar o repositório centralizado de documentos do tenant — fonte única de verdade para todos os arquivos do sistema (certificados, procedimentos, XMLs fiscais, documentos de RH, evidências, contratos). Nenhum outro módulo armazena arquivos fora do GED (FR-GED-07).

## Valor entregue
Todo documento do laboratório está em um único lugar: procedimentos de calibração, certificados emitidos, XMLs de NFS-e, documentos de clientes e evidências de OS. Ninguém procura arquivo em pasta compartilhada ou e-mail. Alerta automático de vencimento de documentos evita surpresas em auditoria.

## Escopo

### Repositório centralizado (FR-GED-01, FR-GED-07)
- Upload de qualquer formato de arquivo (PDF, imagem, XML, planilha, documento)
- Vinculação contextual a entidades: OS, cliente, instrumento, certificado, procedimento, padrão, NFS-e
- Busca full-text nos metadados do documento (nome, descrição, tags)
- OCR básico em PDFs para indexação de conteúdo (FR-BI-05 reclassificado para GED)
- Organização por categorias configuráveis pelo tenant
- Entidades: Documento GED

### Controle de acesso (FR-GED-03)
- Controle de acesso por documento/categoria: quais papéis podem ler, editar, excluir
- Marcação de confidencialidade (público no tenant / restrito / confidencial)
- Log de acesso auditável: quem acessou/baixou qual documento, quando

### Limites de armazenamento por plano (FR-GED-06)
- Starter: 10 GB por tenant
- Alertas automáticos em 80% e 95% do limite
- Bloqueio suave ao atingir 100% (upload bloqueado, leitura livre)
- Contabilização de uso exibida no painel de administração do tenant

### Alertas de vencimento (FR-GED-04)
- Documentos com campo de validade (procedimentos, certificados de padrão, habilitações técnicas)
- Alertas em 90/60/30/15 dias antes do vencimento
- Status "vencido" visível em todos os módulos consumidores (padrão de referência, procedimento, habilitação técnica)

### Integração downstream
- Certificados de calibração armazenados automaticamente no GED após emissão (E06)
- XMLs de NFS-e armazenados automaticamente após autorização (E07)
- Documentos de procedimentos de calibração gerenciados pelo GED (E03)
- Evidências de OS vinculadas ao GED (E04)

## Fora de escopo
- Controle de versão documental completo com ciclo de vida (Rascunho → Análise Crítica → Publicado → Obsoleto) — FR-GED-02, classificado P1 (pós-MVP)
- Link temporário externo com token para compartilhamento com auditores — FR-GED-05, P1 (pós-MVP)
- Storage físico dos arquivos: usa Laravel Filesystem com driver configurável (MinIO self-hosted ou S3 `sa-east-1` — detalhes em ADR-0005)

## Critérios de entrada
- E03 completo (entidades a serem vinculadas a documentos existem)

## Critérios de saída
- Upload de PDF de procedimento vinculado à entidade "Procedimento de calibração"
- Busca por nome de documento retorna resultado correto
- Alerta de vencimento disparado para documento com validade em 30 dias
- Documento de papel "confidencial" não visível para papel "visualizador" (verificado por teste)
- Contador de uso de armazenamento correto após upload e deleção
- Certificado emitido em E06 armazenado automaticamente no GED (integração verificada por teste)

## Stories previstas
- E10-S01 — Entidade Documento GED + storage Laravel Filesystem (MinIO/S3)
- E10-S02 — Upload, download e vinculação contextual a entidades
- E10-S03 — Controle de acesso por documento e log de acesso auditável
- E10-S04 — Alertas de vencimento (90/60/30/15 dias)
- E10-S05 — Limites de armazenamento por plano + alertas de uso

## Dependências
- E03 (entidades core cadastradas — clientes, instrumentos, procedimentos)
- ADR-0005 (storage de documentos — MinIO vs S3) — se ADR não estiver fechado, usar MinIO local em staging

## Riscos
- OCR em PDFs pode ser lento para arquivos grandes — processamento assíncrono via fila, indexação em background
- Integração com storage externo (MinIO/S3) pode ter latência — pre-signed URLs para download direto do bucket

## Complexidade estimada
- Stories: 5
- Complexidade relativa: média
- Duração estimada: 1 semana
