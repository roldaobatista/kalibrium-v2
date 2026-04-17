# Slice 016 — E15-S02: Scaffold React + TypeScript + Ionic + Capacitor + Vite

Slice **016** — pronto para aceitação do PM.

## Gates obrigatórios aprovados

- Verifier (mecânico): **approved** → `specs/016/verification.json`
- Reviewer (estrutural): **approved** → `specs/016/review.json`
- Security-reviewer (segurança/LGPD): **approved** → `specs/016/security-review.json`
- Test-auditor (cobertura/qualidade dos testes): **approved** → `specs/016/test-audit.json`
- Functional-reviewer (produto/UX/ACs): **approved** → `specs/016/functional-review.json`

Os gates obrigatórios foram concluídos com verdict approved e sem findings bloqueantes.

## Acceptance Criteria verificados

14 AC(s) no spec — todos passaram no verifier. Detalhes mecânicos em `specs/016/verification.json`.

## Para o PM (linguagem de produto, R12)

Este PR entrega o comportamento descrito em `specs/016/spec.md`. Antes de aceitar o merge:

1. Ler `specs/016/spec.md` (contexto + ACs em português).
2. Se houver UI: testar visualmente no ambiente de staging.
3. Aceitar (merge) ou comentar ajustes — o agente aplica na próxima iteração.

## Arquivos alterados

- .gitignore
- .prettierignore
- .prettierrc.json
- android/.gitignore
- android/app/.gitignore
- android/app/build.gradle
- android/app/capacitor.build.gradle
- android/app/proguard-rules.pro
- android/app/src/androidTest/java/com/getcapacitor/myapp/ExampleInstrumentedTest.java
- android/app/src/main/AndroidManifest.xml
- android/app/src/main/java/app/kalibrium/client/MainActivity.java
- android/app/src/main/res/drawable-land-hdpi/splash.png
- android/app/src/main/res/drawable-land-mdpi/splash.png
- android/app/src/main/res/drawable-land-xhdpi/splash.png
- android/app/src/main/res/drawable-land-xxhdpi/splash.png
- android/app/src/main/res/drawable-land-xxxhdpi/splash.png
- android/app/src/main/res/drawable-port-hdpi/splash.png
- android/app/src/main/res/drawable-port-mdpi/splash.png
- android/app/src/main/res/drawable-port-xhdpi/splash.png
- android/app/src/main/res/drawable-port-xxhdpi/splash.png
- android/app/src/main/res/drawable-port-xxxhdpi/splash.png
- android/app/src/main/res/drawable-v24/ic_launcher_foreground.xml
- android/app/src/main/res/drawable/ic_launcher_background.xml
- android/app/src/main/res/drawable/splash.png
- android/app/src/main/res/layout/activity_main.xml
- android/app/src/main/res/mipmap-anydpi-v26/ic_launcher.xml
- android/app/src/main/res/mipmap-anydpi-v26/ic_launcher_round.xml
- android/app/src/main/res/mipmap-hdpi/ic_launcher.png
- android/app/src/main/res/mipmap-hdpi/ic_launcher_foreground.png
- android/app/src/main/res/mipmap-hdpi/ic_launcher_round.png
- android/app/src/main/res/mipmap-mdpi/ic_launcher.png
- android/app/src/main/res/mipmap-mdpi/ic_launcher_foreground.png
- android/app/src/main/res/mipmap-mdpi/ic_launcher_round.png
- android/app/src/main/res/mipmap-xhdpi/ic_launcher.png
- android/app/src/main/res/mipmap-xhdpi/ic_launcher_foreground.png
- android/app/src/main/res/mipmap-xhdpi/ic_launcher_round.png
- android/app/src/main/res/mipmap-xxhdpi/ic_launcher.png
- android/app/src/main/res/mipmap-xxhdpi/ic_launcher_foreground.png
- android/app/src/main/res/mipmap-xxhdpi/ic_launcher_round.png
- android/app/src/main/res/mipmap-xxxhdpi/ic_launcher.png
- android/app/src/main/res/mipmap-xxxhdpi/ic_launcher_foreground.png
- android/app/src/main/res/mipmap-xxxhdpi/ic_launcher_round.png
- android/app/src/main/res/values/ic_launcher_background.xml
- android/app/src/main/res/values/strings.xml
- android/app/src/main/res/values/styles.xml
- android/app/src/main/res/xml/file_paths.xml
- android/app/src/test/java/com/getcapacitor/myapp/ExampleUnitTest.java
- android/build.gradle
- android/capacitor.settings.gradle
- android/gradle.properties
- android/gradle/wrapper/gradle-wrapper.jar
- android/gradle/wrapper/gradle-wrapper.properties
- android/gradlew
- android/gradlew.bat
- android/settings.gradle
- android/variables.gradle
- app/Livewire/Concerns/ResolvesTenantAndActor.php
- app/Livewire/Pages/App/HomePage.php
- app/Livewire/Pages/Auth/AcceptInvitationPage.php
- app/Livewire/Pages/Auth/ForgotPasswordPage.php
- app/Livewire/Pages/Auth/LoginPage.php
- app/Livewire/Pages/Auth/ResetPasswordPage.php
- app/Livewire/Pages/Auth/TwoFactorChallengePage.php
- app/Livewire/Pages/Privacy/RevokeConsentPage.php
- app/Livewire/Pages/Settings/Concerns/ResolvesTenantSettingsContext.php
- app/Livewire/Pages/Settings/PlansPage.php
- app/Livewire/Pages/Settings/TenantPage.php
- app/Livewire/Pages/Settings/UsersPage.php
- app/Livewire/Ping.php
- app/Livewire/Settings/ConsentSubjectsPage.php
- app/Livewire/Settings/LgpdCategoriesPage.php
- capacitor.config.ts
- composer.json
- composer.lock
- docs/frontend/README.md
- docs/handoffs/handoff-2026-04-17-slice-016-planejamento-fechado.md
- docs/handoffs/latest.md
- docs/product/PRD.md
- docs/schedule/harness-pending-removals.md
- docs/slice-registry.md
- epics/E02/stories/E02-S09.md
- epics/E02/stories/E02-S10.md
- epics/E02/stories/INDEX.md
- epics/E16/epic.md
- epics/E16/stories/INDEX.md
- epics/E17/epic.md
- epics/E17/stories/INDEX.md
- epics/E18/epic.md
- epics/E18/stories/INDEX.md
- epics/E19/epic.md
- epics/E19/stories/INDEX.md
- epics/E20/epic.md
- epics/E20/stories/INDEX.md
- epics/E21/epic.md
- epics/E21/stories/INDEX.md
- epics/E22/epic.md
- epics/E22/stories/INDEX.md
- epics/E23/epic.md
- epics/E23/stories/INDEX.md
- epics/E24/epic.md
- epics/E24/stories/INDEX.md
- epics/E25/epic.md
- epics/E25/stories/INDEX.md
- eslint.config.js
- index.html
- package-lock.json
- package.json
- playwright.config.ts
- project-state.json
- resources/js/app.js
- resources/js/bootstrap.js
- resources/views/layouts/app.blade.php
- resources/views/layouts/guest.blade.php
- resources/views/livewire/pages/app/home-page.blade.php
- resources/views/livewire/pages/auth/accept-invitation-page.blade.php
- resources/views/livewire/pages/auth/forgot-password-page.blade.php
- resources/views/livewire/pages/auth/login-page.blade.php
- resources/views/livewire/pages/auth/partials/feedback.blade.php
- resources/views/livewire/pages/auth/reset-password-page.blade.php
- resources/views/livewire/pages/auth/two-factor-challenge-page.blade.php
- resources/views/livewire/pages/privacy/revoke-consent-page.blade.php
- resources/views/livewire/pages/settings/plans-page.blade.php
- resources/views/livewire/pages/settings/tenant-page.blade.php
- resources/views/livewire/pages/settings/users-page.blade.php
- resources/views/livewire/ping.blade.php
- resources/views/livewire/settings/consent-subjects-page.blade.php
- resources/views/livewire/settings/lgpd-categories-page.blade.php
- resources/views/welcome.blade.php
- routes/web.php
- scripts/draft-spec.sh
- scripts/security-scan.sh
- scripts/sequencing-check.sh
- scripts/test-scope.php
- specs/.current
- specs/016/functional-review.json
- specs/016/master-audit.json
- specs/016/plan-review.json
- specs/016/plan.md
- specs/016/review.json
- specs/016/security-review.json
- specs/016/spec-audit.json
- specs/016/spec.md
- specs/016/tasks.md
- specs/016/test-audit.json
- specs/016/test-run-output.txt
- specs/016/tests-draft-audit.json
- specs/016/verification.json
- src/App.tsx
- src/auth/.gitkeep
- src/components/.gitkeep
- src/db/.gitkeep
- src/hooks/.gitkeep
- src/main.tsx
- src/observability/.gitkeep
- src/pages/.gitkeep
- src/pages/AdminDevicesPage.tsx
- src/pages/HomePage.tsx
- src/pages/LoginPage.tsx
- src/theme-variables.css
- src/vite-env.d.ts
- src/wipe/.gitkeep
- tests/Unit/README.md
- tests/e2e/ac-001-dev-server.spec.ts
- tests/e2e/ac-006-layout-adaptive.spec.ts
- tests/scaffold/README.md
- tests/scaffold/ac-002-build-web.test.cjs
- tests/scaffold/ac-003-cap-ios.test.cjs
- tests/scaffold/ac-004-cap-android.test.cjs
- tests/scaffold/ac-005-structure.test.cjs
- tests/scaffold/ac-007-lint.test.cjs
- tests/scaffold/ac-008-legacy-removed.test.cjs
- tests/scaffold/ac-014-capacitor-security.test.cjs
- tests/slice-007/AuthAuditTest.php
- tests/slice-007/AuthLoginTest.php
- tests/slice-007/AuthPasswordResetTest.php
- tests/slice-007/AuthTwoFactorTest.php
- tests/slice-008/TenantSettingsAuditTest.php
- tests/slice-008/TenantSettingsIsolationTest.php
- tests/slice-008/TenantSettingsPageTest.php
- tests/slice-008/TenantSettingsValidationTest.php
- tests/slice-009/PlansPageTest.php
- tests/slice-009/UsersInviteAcceptanceTest.php
- tests/slice-009/UsersInviteTest.php
- tests/slice-009/UsersPageTest.php
- tests/slice-009/UsersPlansSecurityTest.php
- tests/slice-010/ConsentBlockingTest.php
- tests/slice-010/ConsentSubjectsPageTest.php
- tests/slice-010/LgpdCategoriesPageTest.php
- tests/slice-010/RevocationTokenTest.php
- tests/slice-011/TenantIsolationExportTest.php
- tests/slice-011/TenantIsolationHttpTest.php
- tests/slice-011/TenantIsolationSecurityTest.php
- tests/slice-016/ac-tests.sh
- tsconfig.json
- tsconfig.node.json
- vite.config.js
- vite.config.ts

---
Gerado por `/merge-slice 016`.
