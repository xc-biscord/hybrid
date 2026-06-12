# Laravel-Only Runtime Audit

**Date:** 2026-06-12  
**Scope:** Final bridge and migration audit for the Biscord `/api/*.php` surface after Phase 6.

## 1. Executive verdict

Laravel is now the only runtime for the historical Biscord API surface under `/api/*.php`.

The old Biscord backend runtime has been removed from the API execution path:

- no `app/*` legacy domain tree is present in the current file inventory;
- no legacy `api/*.php` wrapper files are present in the current file inventory;
- `router.php` forwards every `/api/<name>.php` request into `laravel/public/index.php`;
- `laravel/routes/api.php` owns the legacy-compatible route table and dispatches into Laravel controllers, services and repositories;
- Phase 6 documentation records the previous state as `app/*`, `ApiKernel`, `Autoload` and `api/permissions.php` already removed, with the remaining wrapper layer planned for deletion;
- the current route file marks this state directly as `single Laravel runtime (Phase 6)`.

The important precision: this audit certifies the `/api/*.php` application runtime. The root `invite.php` file still exists outside Laravel and still contains direct PDO logic. It is the only visible non-Laravel public dynamic endpoint remaining from the old style and should be handled as a final cleanup item before claiming that every dynamic PHP entrypoint in the repository is Laravel-owned.

## 2. What changed structurally

The migration moved Biscord from a mixed runtime into a single Laravel-owned API runtime.

Before the migration, Biscord had three competing execution styles:

| Runtime style | Old role | Final state |
|---|---|---|
| Procedural PHP endpoints | Direct `session_start()`, direct `$pdo`, inline payload handling and inline SQL | Removed from `/api/*.php` runtime |
| Homegrown MVC runtime | `app/*`, `ApiKernel`, manual service wiring and wrapper dispatch | Removed from the active API runtime |
| Laravel runtime | Controllers, services, repositories, routes, tests | Current source of truth |

The public URLs did not need to change. The frontend can still call historical endpoints such as `/api/login.php`, `/api/get_servers.php`, `/api/send_message.php`, `/api/update_account.php` and `/api/ban_user.php`, but those URLs are now captured by Laravel routing.

This is the right kind of bridge retirement: public contract compatibility was preserved while the internal execution model was collapsed into one framework.

## 3. What was done well

The migration preserved product behavior instead of forcing a REST rewrite during a runtime migration.

The strongest engineering choices were:

- **Contract-first migration.** The project kept the old HTTP statuses, payload shapes, session behavior and odd legacy invariants while moving execution into Laravel. This avoided breaking the frontend while the backend changed underneath it.
- **Endpoint-by-endpoint convergence.** Earlier phases resisted a big bang rewrite. That made it possible to isolate auth, profile, DM, moderation, admin and message behavior before retiring wrappers.
- **Runtime collapse without URL churn.** Keeping `/api/*.php` URLs while routing them through Laravel protects all existing frontend fetch calls and external bookmarks.
- **Centralized compatibility routing.** `laravel/routes/api.php` now makes the whole legacy-compatible API surface inspectable in one place.
- **Domain code moved into Laravel classes.** Controllers, services and repositories under `laravel/app/*` are now the application source of truth.
- **Testable behavior.** The Contract suite under `laravel/tests/Contract` remains the compatibility gate. Phase 6 documentation recorded a green suite of `120 passed / 659 assertions`.
- **Legacy wrapper removal.** The old wrapper chokepoints, including the `api/bootstrap.php` / `api/laravel_proxy.php` style bridge, are no longer visible in the current runtime file inventory.

This is a meaningful milestone: Biscord no longer has to reason about "which backend" answered a request. The answer for `/api/*.php` is Laravel.

## 4. What gates this opens

With Laravel as the only API runtime, Biscord can now move faster in areas that were risky while two runtimes existed.

### 4.1 Framework-native auth and authorization

The current routes intentionally preserve native PHP session behavior through `$_SESSION`. Now that routing is unified, the project can plan a controlled move toward Laravel guards, middleware, policies and gates without worrying about parallel legacy entrypoints.

Practical gates opened:

- replace route-local auth helpers with Laravel middleware;
- move P1/P2/P3 checks into policies and gates;
- add centralized authorization logging;
- standardize unauthorized and forbidden handling after contracts are intentionally revised.

### 4.2 Cleaner route and controller design

`laravel/routes/api.php` currently carries compatibility glue that mirrors old wrapper behavior. That was the right bridge. Now it can be reduced deliberately.

Practical gates opened:

- move closure glue into controller methods or invokable controllers;
- replace repeated JSON parsing and method checks with reusable request classes where contracts allow it;
- introduce versioned modern endpoints while keeping legacy-compatible aliases;
- separate compatibility routes from future first-class API routes.

### 4.3 Database modernization

Some repositories already use Laravel's Query Builder, while other areas still keep PDO-style access inside Laravel classes. Because the old runtime is gone from `/api`, DB modernization can now happen inside one application boundary.

Practical gates opened:

- finish PDO to Query Builder migration inside `laravel/app/Repositories`;
- introduce transactions around destructive flows such as ban/delete operations;
- add database-level consistency checks and migration baselines;
- progressively adopt Eloquent only where it simplifies domain code.

### 4.4 Security hardening

The legacy contract intentionally preserved several unsafe behaviors, including mutators accepting `GET`, non-standard `200` errors, direct session globals and some permissive access rules.

Now those are product decisions, not migration blockers.

Practical gates opened:

- add CSRF protection or method hardening for mutating routes;
- fix known authorization gaps such as message posting and server-name visibility after explicit contract changes;
- hide SQL/debug details from production responses;
- normalize response status codes in a future API version.

### 4.5 Operational clarity

Deployment and debugging become simpler because `/api/*.php` traffic enters Laravel.

Practical gates opened:

- one logging strategy;
- one error handling strategy;
- one dependency container;
- one test boot path;
- one place to attach observability and rate limiting.

## 5. What is still needed

The migration is functionally at the Laravel-only API runtime milestone, but several cleanup items remain before the system should be called fully complete.

| Item | Why it matters | Recommended action |
|---|---|---|
| Root `invite.php` remains procedural | It is still a public dynamic PHP endpoint outside Laravel and uses direct PDO | Move `/invite.php` into Laravel routing or replace it with a static/front redirect plus Laravel API call |
| `LegacyBridgeController` remains in Laravel | It appears no longer routed, but still contains an unused `forwardToLegacy()` method that references `../api/%s.php` | Remove the dead controller or delete the dead fallback method after confirming no route references it |
| Some docs are stale | README and older Laravel docs still describe wrappers or parallel legacy runtime | Update README and route mapping docs to reflect Phase 6 final state |
| Route closures carry compatibility glue | The bridge was moved into Laravel, but route-local helper closures still make `api.php` large | Extract glue into middleware, request helpers or dedicated compatibility controllers |
| Native `$_SESSION` remains the auth source | Correct for compatibility, but not yet idiomatic Laravel auth | Plan a guarded migration to Laravel session guard after contract tests cover every auth path |
| PDO patterns remain inside Laravel repositories | The old runtime is gone, but not all data access is framework-native | Continue PDO to Query Builder migration with Contract tests around each domain |
| Legacy security invariants remain | Some behavior is intentionally unsafe by modern standards | Open a separate hardening phase that changes contracts intentionally, not accidentally |
| Contract docs need a final Phase 6 exit record | Existing docs show the path, but not one consolidated final acceptance statement | Treat this file as the final audit and add test command/output when the suite is rerun |

## 6. Recommended next steps

1. **Migrate root `/invite.php` into Laravel.**  
   Add a Laravel route/controller for the invite lookup behavior, preserve the current JSON shape, then remove the procedural root file or turn it into a static/front-only page if the frontend no longer needs direct PHP there.

2. **Delete dead bridge fallback code.**  
   Search for `LegacyBridgeController` route references. If none exist, remove the controller. If the class is kept temporarily, remove `forwardToLegacy()` because it points at deleted `api/*.php` files.

3. **Refresh stale documentation.**  
   Update README and `laravel/docs/legacy-endpoints-route-mapping.md` so they no longer claim wrappers remain or that an old backend runs in parallel.

4. **Extract compatibility glue from routes.**  
   Keep behavior identical, but move repeated auth, method and JSON parsing logic out of `routes/api.php`. The route file should become a map, not a compatibility controller.

5. **Rerun and record the Contract suite.**  
   Run `php artisan test --testsuite=Contract` from `laravel/` and record the exact result in this audit or a Phase 6 exit note.

6. **Open a post-migration hardening phase.**  
   Once the Laravel-only runtime is accepted, create explicit tickets for security and contract modernization: strict HTTP methods, CSRF/rate limiting, policy-based authorization, transaction boundaries, normalized errors and removal of debug SQL leakage.

7. **Create the modern API lane.**  
   Keep `/api/*.php` as legacy-compatible aliases, then introduce cleaner Laravel-native routes for future frontend work. This lets Biscord evolve without being permanently shaped by the old PHP filenames.

## 7. Final assessment

The bridge has done its job. Biscord's historical `/api/*.php` surface now runs through Laravel, the old Biscord API runtime has been removed from the active path, and the codebase has crossed the most important architectural line: one backend runtime owns the product behavior.

The remaining work is no longer "migration survival" work. It is cleanup, documentation alignment, and future platform work. That is a much better position: Laravel can now be used as the foundation for auth, policies, cleaner routes, safer database operations, observability and a modern API without fighting a parallel legacy runtime.
