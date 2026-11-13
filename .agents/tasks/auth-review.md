# Laravel Sanctum Authentication Implementation

Laravel Sanctum token-based authentication for SIPENTAR hydroponics management system. Implements login and logout endpoints with role-based user model (admin and karyawan). Password hashing via Laravel's native cast, token generation through Sanctum's `createToken`, and consistent JSON envelope via `ApiResponse` trait.

**Watch for:** Timing attack in login flow (confirmed), missing rate limiting on login endpoint (confirmed), no token expiration configured (confirmed), seeder uses explicit Hash::make instead of relying on model cast (likely inconsistency).

**Verdict**: NEEDS_CHANGES

## High-level view

Login flow checks username existence before password verification, creating a timing differential that leaks whether usernames exist. Rate limiting is absent from the login endpoint, allowing unrestricted brute-force attempts. Sanctum tokens have no expiration, meaning compromised tokens remain valid indefinitely unless explicitly revoked. The SuperAdminSeeder calls `Hash::make` directly despite the User model having a `password: 'hashed'` cast that auto-hashes passwords, introducing an inconsistency where the same password gets hashed twice. Test coverage validates happy paths and basic failure modes but does not verify token revocation behavior beyond a single round-trip.

<details>
<summary>Issues (4)</summary>

1. **Timing attack in login** — Login checks `User::where('username')->first()` before `Hash::check`, leaking username existence through response timing. Refactor to always perform Hash::check even when user is not found, using a dummy hash.
2. **Missing rate limiting** — Login endpoint has no throttle, allowing unlimited brute-force attempts. Apply `throttle:api` middleware or a custom login throttle.
3. **No token expiration** — Sanctum tokens never expire. Compromised tokens remain valid until explicitly revoked. Publish and configure `config/sanctum.php` with `expiration` value or implement manual expiration logic.
4. **Double hashing in seeder** — SuperAdminSeeder calls `Hash::make('admin123')` but the User model already has `password: 'hashed'` cast, hashing the password twice. Remove `Hash::make` from the seeder and pass plain text.

</details>

<details>
<summary>Details</summary>

## Timing attack in login flow

The login method retrieves the user record, then checks the password:

```php
$user = User::where('username', $request->username)->first();

if (! $user || ! Hash::check($request->password, $user->password)) {
    return $this->errorResponse('Username atau password salah.', 401);
}
```

When the username does not exist, the method returns immediately without calling `Hash::check`. When the username exists, it performs an expensive bcrypt hash comparison. This timing difference leaks whether a username is registered. An attacker can enumerate valid usernames by measuring response times.

The fix is to always call `Hash::check` even when the user does not exist:

```php
$user = User::where('username', $request->username)->first();
$password = $user?->password ?? '$2y$10$dummyhashtopreventtimingleak';

if (! $user || ! Hash::check($request->password, $password)) {
    return $this->errorResponse('Username atau password salah.', 401);
}
```

The dummy hash ensures consistent bcrypt execution regardless of whether the user exists.

## Missing rate limiting on authentication endpoint

The `/api/v1/auth/login` route has no rate limiting middleware. An attacker can submit unlimited login attempts, enabling brute-force attacks against weak passwords or username enumeration at scale.

Laravel provides `throttle:api` middleware out of the box (60 requests per minute by default). For login endpoints, a stricter throttle is warranted:

```php
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');
```

Then define the `login` throttle in `bootstrap/app.php` or a service provider:

```php
RateLimiter::for('login', fn () => Limit::perMinute(5));
```

This limits each IP to 5 login attempts per minute, blocking brute-force attacks while allowing legitimate users who mistype their password.

## No token expiration configured

Sanctum tokens are issued with no expiration. The `createToken` call in the login method does not set a `expiresAt` parameter, and the default Sanctum configuration does not enforce expiration. A token stolen via XSS, man-in-the-middle, or log exposure remains valid indefinitely.

Sanctum supports token expiration through the `sanctum.expiration` config value (in minutes) or per-token expiration via `createToken('name', ['*'], now()->addHours(24))`. Since the application context is a single hydroponics farm (not a public SaaS), long-lived tokens may be acceptable, but the absence of expiration should be a deliberate choice, not an oversight.

Options:
1. Publish `config/sanctum.php` and set `expiration` (e.g., 1440 minutes = 24 hours)
2. Add per-token expiration: `$user->createToken('auth_token', ['*'], now()->addHours(24))`
3. Document that tokens are long-lived by design and rely on explicit logout for revocation

Without at least option 3, the system has no expiration policy at all.

## Password double-hashing in SuperAdminSeeder

The `SuperAdminSeeder` calls `Hash::make('admin123')` before passing the password to the User model:

```php
User::updateOrCreate(
    ['username' => 'admin'],
    [
        'password' => Hash::make('admin123'),
        'role' => UserRole::ADMIN,
    ]
);
```

But the User model already declares `password: 'hashed'` in its `casts()` method, which automatically hashes any plain text password assigned to the `password` attribute. This means the seeder hashes the password once with `Hash::make`, then the model's cast hashes it again, resulting in a double-hashed password that cannot be verified.

The seeder should pass plain text and let the model cast handle hashing:

```php
'password' => 'admin123',
```

Alternatively, the seeder could bypass the cast by directly inserting the hashed value, but that's inconsistent with the model's design. The cleaner pattern is to use the cast consistently.

Interestingly, the tests use `Hash::make` in their `beforeEach` setup, which suffers the same issue but may pass if the test database is isolated. This inconsistency should be fixed across seeders, factories, and tests.

## Test coverage gaps

Not tested:
- **Multiple tokens per user**: Sanctum allows multiple concurrent tokens. The current implementation revokes only `currentAccessToken()`, but the test suite does not verify that other tokens remain valid after logout.
- **Token expiration**: No tests for expired token rejection (because expiration is not configured).
- **Rate limiting**: No tests for throttle behavior (because throttle is not applied).
- **Case sensitivity**: No test for username case sensitivity (e.g., does `Admin` match `admin`?). The database column is `username` with a unique constraint, so the system is likely case-sensitive by default, but this is not verified.

</details>

<details>
<summary>File map</summary>

**Core authentication flow:**
- `app/Http/Controllers/Api/V1/AuthController.php` — login and logout methods
- `app/Http/Requests/Api/V1/LoginRequest.php` — login validation (username required, password min:8)
- `app/Http/Resources/Api/V1/UserResource.php` — user response format (id, nama, username, role)
- `app/Traits/ApiResponse.php` — JSON envelope (success, message, data/errors)
- `routes/api.php` — `/api/v1/auth/login` (public) and `/api/v1/auth/logout` (protected by `auth:sanctum`)

**Models and enums:**
- `app/Models/User.php` — User model with HasApiTokens, password hashed cast, role enum cast
- `app/Enums/UserRole.php` — ADMIN and KARYAWAN enum cases

**Database:**
- `database/migrations/0001_01_01_000000_create_users_table.php` — users table (id, nama, username, password, role, timestamps)
- `database/seeders/SuperAdminSeeder.php` — creates super admin (username: admin, password: admin123)
- `database/seeders/DatabaseSeeder.php` — calls SuperAdminSeeder
- `database/factories/UserFactory.php` — user factory with `admin()` state

**Exception handling:**
- `app/Exceptions/Handler.php` — renders validation (422), authentication (401), and server (500) errors as JSON for API routes
- `bootstrap/app.php` — configures JSON rendering for `api/*` routes

**Tests:**
- `tests/Feature/Api/V1/Auth/LoginTest.php` — 7 tests covering valid login, invalid credentials, validation errors, admin login
- `tests/Feature/Api/V1/Auth/LogoutTest.php` — 4 tests covering authenticated logout, token revocation, unauthenticated access, invalid token

Full diff: `git diff main` (if reviewing a branch) or `git log --stat` for committed changes.

</details>
