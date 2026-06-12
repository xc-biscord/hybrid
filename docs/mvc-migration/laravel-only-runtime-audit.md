# Laravel-Only Runtime Audit

**Date:** 2026-06-12  
**Scope:** Final bridge and migration audit for the Biscord `/api/*.php` surface and root `/invite.php` endpoint after final convergence.

## 1. Executive verdict

Laravel is now the only dynamic runtime for Biscord's historical API-compatible backend surface.

The old Biscord backend runtime has been removed from the active execution path:

- no `app/*` legacy domain tree is present in the current file inventory;
- no legacy `api/*.php` wrapper files are present in the current file inventory;
- `ApiKernel` and legacy `Autoload` are gone;
- `router.php` forwards every `/api/<name>.php` request into `laravel/public/index.php`;
- `router.php` also forwards root `/invite.php` into `laravel/public/index.php`;
- root `invite.php` is now a minimal Laravel front-controller facade for environments that execute the file directly;
- `laravel/routes/api.php` owns the legacy-compatible `/api/*.php` route table;
- `router.php` maps root `/invite.php` internally to the Laravel API route `/api/invite.php`;
- `LegacyBridgeController` has been removed, including the impossible fallback to deleted wrappers.

The public URLs remain compatible. The runtime behind them is Laravel.

## 2. Final invite.php behavior

The old procedural root endpoint had this observable contract:

| Aspect | Contract |
|---|---|
| Public URL | `/invite.php` |
| Method | Any method accepted; behavior reads query parameter `code` |
| Input | `?code=<invitation-code>` |
| Auth | Native PHP session must contain `$_SESSION['user_id']` |
| Missing session or code | HTTP `200`, JSON `{success:false,error:"Utilisateur non connecté ou lien invalide."}` |
| Unknown code | HTTP `200`, JSON `{success:false,error:"Lien invalide."}` |
| Success | HTTP `200`, JSON `{success:true,server_id,server_name}` |
| Side effects | None; it only resolves invite/server display data |
| Frontend consumer | `invitation.html`, before calling `/api/accept_invite.php` |

That behavior now lives in Laravel through `InvitationController::resolve()`, `InvitationService::resolveInvite()` and `InvitationRepository::findInviteServerSummaryByCode()`.

The root `invite.php` file no longer performs SQL or business logic. It loads the shared config/session bridge and hands execution to `laravel/public/index.php`.

## 3. What changed structurally

Biscord moved from a mixed runtime into a single Laravel-owned dynamic runtime.

| Runtime style | Old role | Final state |
|---|---|---|
| Procedural PHP endpoints | Direct `session_start()`, direct `$pdo`, inline payload handling and inline SQL | Removed from active backend behavior |
| Homegrown MVC runtime | `app/*`, `ApiKernel`, manual service wiring and wrapper dispatch | Removed |
| Laravel runtime | Controllers, services, repositories, routes, tests | Current source of truth |

The frontend can still call historical endpoints such as `/api/login.php`, `/api/get_servers.php`, `/api/send_message.php`, `/api/update_account.php`, `/api/ban_user.php` and `/invite.php`, but those URLs are now captured by Laravel routing or a Laravel front-controller facade.

## 4. What was done well

The migration preserved product behavior instead of forcing a REST rewrite during a runtime migration.

The strongest engineering choices were:

- **Contract-first migration.** HTTP statuses, payload shapes, session behavior and odd legacy invariants were preserved while execution moved into Laravel.
- **Endpoint-by-endpoint convergence.** Earlier phases isolated auth, profile, DM, moderation, admin and message behavior before wrapper retirement.
- **Runtime collapse without URL churn.** Historical URLs still work, which protects the existing frontend and any external links.
- **Centralized compatibility routing.** `laravel/routes/api.php` and `laravel/routes/web.php` now make the compatibility surface inspectable.
- **Domain code moved into Laravel classes.** Controllers, services and repositories under `laravel/app/*` are the application source of truth.
- **Testable behavior.** The Contract suite under `laravel/tests/Contract` remains the compatibility gate.
- **Dead fallback removal.** `LegacyBridgeController` and its `forwardToLegacy()` fallback were removed after confirming no active route uses them.

## 5. What gates this opens

With Laravel as the only dynamic runtime, Biscord can now move faster in areas that were risky while two runtimes existed.

### 5.1 Framework-native auth and authorization

The current routes intentionally preserve native PHP session behavior through `$_SESSION`. Now that routing is unified, the project can plan a controlled move toward Laravel guards, middleware, policies and gates without worrying about parallel legacy entrypoints.

Practical gates opened:

- replace route-local auth helpers with Laravel middleware;
- move P1/P2/P3 checks into policies and gates;
- add centralized authorization logging;
- standardize unauthorized and forbidden handling after contracts are intentionally revised.

### 5.2 Cleaner route and controller design

`laravel/routes/api.php` still carries compatibility glue that mirrors old wrapper behavior. That was the right bridge. It can now be reduced deliberately.

Practical gates opened:

- move closure glue into controller methods or invokable controllers;
- replace repeated JSON parsing and method checks with reusable request classes where contracts allow it;
- introduce versioned modern endpoints while keeping legacy-compatible aliases;
- separate compatibility routes from future first-class API routes.

### 5.3 Database modernization

Because the old runtime is gone, DB modernization can now happen inside one application boundary.

Practical gates opened:

- finish PDO to Query Builder migration inside `laravel/app/Repositories`;
- introduce transactions around destructive flows such as ban/delete operations;
- add database-level consistency checks and migration baselines;
- progressively adopt Eloquent only where it simplifies domain code.

### 5.4 Operational clarity

Deployment and debugging become simpler because dynamic backend traffic enters Laravel.

Practical gates opened:

- one logging strategy;
- one error handling strategy;
- one dependency container;
- one test boot path;
- one place to attach observability and rate limiting.

## 6. Remaining work

No legacy PHP runtime remains active for the covered dynamic backend surface.

Remaining work is future platform cleanup, not migration completion:

| Item | Status |
|---|---|
| Root `/invite.php` procedural SQL | Completed: moved to Laravel behavior, file is now a facade |
| `LegacyBridgeController` fallback | Completed: deleted |
| Stale README / route mapping docs | Completed in final convergence pass |
| Route closure compatibility glue | Optional future cleanup; not required for runtime convergence |
| Native `$_SESSION` auth compatibility | Intentional contract preservation |
| PDO inside Laravel repositories | Internal implementation detail; not a separate legacy runtime |

## 7. Final assessment

The bridge has done its job. Biscord's historical `/api/*.php` surface and root `/invite.php` lookup now run through Laravel, the old Biscord PHP runtime has been removed from the active path, and the codebase has crossed the important architectural line: one dynamic backend runtime owns the product behavior.

The remaining work is no longer migration survival work. It is optional cleanup and future platform work. Laravel can now be used as the foundation for auth, policies, cleaner routes, safer database operations, observability and a modern API without fighting a parallel legacy runtime.
