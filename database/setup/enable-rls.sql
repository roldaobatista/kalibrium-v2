-- Enable Row-Level Security (RLS) configuration for the current session.
--
-- This script sets the session-level GUC 'rls.enabled' to 'true', which can be
-- checked later by application code via current_setting('rls.enabled', true).
--
-- RLS policies on individual tables will be created in E02 (Multi-tenancy),
-- where each tenant's data is isolated via policies that reference the current
-- tenant ID set in the session. This script only prepares the configuration
-- parameter — it does NOT create any table-level policies.
--
-- Decision rationale: see ADR-0001 (docs/adr/0001-stack-choice.md)
-- PostgreSQL 18 supports RLS natively. Enabling it early ensures the
-- infrastructure is ready when tenant isolation stories begin.

-- Register custom GUC parameter for RLS tracking
SELECT set_config('rls.enabled', 'true', false);
