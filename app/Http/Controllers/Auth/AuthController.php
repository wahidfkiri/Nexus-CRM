<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\OnboardingController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResendVerificationRequest;
use App\Models\TenantUserMembership;
use App\Models\User;
use App\Notifications\WelcomeAccountNotification;
use Google\Client as GoogleClient;
use Google\Service\Oauth2 as GoogleOauth2;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use Vendor\CrmCore\Models\Tenant;
use Vendor\Rbac\Services\TenantRoleService;
use Vendor\User\Repositories\UserRepository;
use Vendor\User\Services\UserService;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function showForgotPasswordForm()
    {
        return view('auth.passwords.email');
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (!Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Identifiants invalides.',
                    'errors' => [
                        'email' => ['Identifiants invalides.'],
                    ],
                ], 401);
            }
            return back()->withErrors([
                'email' => 'Identifiants invalides.',
            ])->withInput();
        }

        /** @var User $user */
        $user = Auth::user();

        if (!$user->is_active || in_array((string) $user->status, ['inactive', 'suspended'], true)) {
            Auth::logout();
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre compte est désactivé.',
                    'errors' => [
                        'email' => ['Votre compte est désactivé.'],
                    ],
                ], 403);
            }
            return back()->withErrors([
                'email' => 'Votre compte est desactive.',
            ]);
        }

        if (!$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            Auth::logout();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Compte non activé. Un email d’activation vient d’être renvoyé.',
                    'errors' => [
                        'email' => ['Compte non activé. Un email d’activation vient d’être renvoyé.'],
                    ],
                ], 403);
            }

            return back()->withErrors([
                'email' => 'Compte non active. Un email d activation vient d etre renvoye.',
            ])->withInput();
        }

        try {
            $this->handlePendingInvitationAfterAuthentication($request, $user);
        } catch (Throwable $e) {
            Auth::logout();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => [
                        'email' => [$e->getMessage()],
                    ],
                ], 403);
            }

            return back()->withErrors([
                'email' => $e->getMessage(),
            ])->withInput();
        }

        $request->session()->regenerate();
        $activeTenantId = $this->resolveActiveTenantIdForUser($user, (int) $request->session()->get('current_tenant_id', 0));

        if ($activeTenantId <= 0) {
            Auth::logout();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre compte est désactivé par l’administrateur.',
                    'errors' => [
                        'email' => ['Votre compte est désactivé par l’administrateur.'],
                    ],
                ], 403);
            }

            return back()->withErrors([
                'email' => 'Votre compte est désactivé par l’administrateur.',
            ])->withInput();
        }

        $request->session()->put('current_tenant_id', $activeTenantId);

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        if (!$user->tenant_id) {
            $this->createTenantForUser($user, $user->company);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie.',
                'redirect' => $this->afterAuthRedirect($user),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
        }

        return redirect()->intended($this->afterAuthRedirect($user));
    }

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $pendingInvitationToken = (string) $request->session()->get('pending_invitation_token', '');
        $pendingInvitation = $pendingInvitationToken !== ''
            ? app(UserRepository::class)->findInvitationByToken($pendingInvitationToken)
            : null;

        if ($pendingInvitation && mb_strtolower((string) $pendingInvitation->email) !== mb_strtolower((string) $data['email'])) {
            return back()->withErrors([
                'email' => 'Cette invitation est liée à une autre adresse email.',
            ])->withInput();
        }

        try {
            DB::transaction(function () use ($data, $pendingInvitation): void {
                $company = trim((string) ($data['company'] ?? ''));
                $user = User::create([
                    'name' => trim($data['first_name'] . ' ' . $data['last_name']),
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => mb_strtolower((string) $data['email']),
                    'company' => $company !== '' ? $company : null,
                    'password' => Hash::make($data['password']),
                    'is_active' => true,
                    'status' => 'active',
                    'tenant_id' => null,
                    'role_in_tenant' => $pendingInvitation?->role_in_tenant ?? 'owner',
                    'is_tenant_owner' => false,
                    'auth_provider' => 'manual',
                ]);

                if ($pendingInvitation && $pendingInvitation->isUsable()) {
                    app(UserService::class)->acceptInvitation($pendingInvitation, ['user' => $user]);
                } else {
                    $companyName = $company !== '' ? $company : ('Entreprise de ' . $data['first_name']);

                    $tenant = Tenant::create([
                        'name' => $companyName,
                        'slug' => Tenant::generateSlug($companyName),
                        'email' => $data['email'],
                        'timezone' => 'Europe/Paris',
                        'locale' => 'fr',
                        'currency' => 'EUR',
                        'status' => 'active',
                    ]);

                    $user->forceFill([
                        'tenant_id' => $tenant->id,
                        'role_in_tenant' => 'owner',
                        'is_tenant_owner' => true,
                    ])->save();

                    TenantUserMembership::query()->updateOrCreate(
                        [
                            'user_id' => (int) $user->id,
                            'tenant_id' => (int) $tenant->id,
                        ],
                        [
                            'role_in_tenant' => 'owner',
                            'is_tenant_owner' => true,
                            'status' => 'active',
                            'joined_at' => now(),
                        ]
                    );

                    app(TenantRoleService::class)->syncUserRole($user, (int) $tenant->id, 'owner', [
                        'is_tenant_owner' => true,
                        'status' => 'active',
                        'joined_at' => now(),
                    ]);
                }

                $user->sendEmailVerificationNotification();
            });
        } catch (Throwable $e) {
            Log::error('Register error: ' . $e->getMessage());
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inscription impossible pour le moment.',
                ], 500);
            }
            return back()->withErrors([
                'email' => 'Inscription impossible pour le moment.',
            ])->withInput();
        }

        $request->session()->forget(['pending_invitation_token', 'pending_invitation_email']);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Compte créé. Vérifiez votre email pour activer votre accès.',
                'redirect' => route('login'),
            ], 201);
        }

        return redirect()->route('login')->with('success', 'Compte cree. Verifiez votre email pour activer votre acces.');
    }

    public function redirectToGoogle(Request $request)
    {
        try {
            $client = $this->buildGoogleClient();
            $state = Str::random(40);
            $request->session()->put('google_oauth_state', $state);
            $client->setState($state);

            return redirect()->away($client->createAuthUrl());
        } catch (Throwable $e) {
            return redirect()->route('login')->withErrors([
                'email' => $this->resolveGoogleAuthErrorMessage($e),
            ]);
        }
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            if ($request->filled('error')) {
                throw new RuntimeException((string) $request->string('error'));
            }

            $state = (string) $request->string('state');
            $expected = (string) $request->session()->pull('google_oauth_state', '');
            if (!$expected || !hash_equals($expected, $state)) {
                throw new RuntimeException('Etat OAuth invalide.');
            }

            $client = $this->buildGoogleClient();
            $token = $client->fetchAccessTokenWithAuthCode((string) $request->string('code'));
            if (!empty($token['error'])) {
                throw new RuntimeException((string) ($token['error_description'] ?? $token['error']));
            }

            $client->setAccessToken($token);
            $googleUser = (new GoogleOauth2($client))->userinfo->get();

            $email = (string) $googleUser->getEmail();
            if (empty($email)) {
                throw new RuntimeException('Email Google introuvable.');
            }

            $sendWelcome = false;

            /** @var User $user */
            $user = DB::transaction(function () use ($googleUser, $email, &$sendWelcome) {
                $existing = User::query()->where('email', $email)->first();

                if (!$existing) {
                    $fullName = trim((string) $googleUser->getName());
                    [$first, $last] = $this->splitName($fullName);

                    $tenant = Tenant::create([
                        'name' => 'Entreprise de ' . ($first ?: 'Nouveau client'),
                        'slug' => Tenant::generateSlug('entreprise-' . Str::slug($fullName ?: $email)),
                        'email' => $email,
                        'timezone' => 'Europe/Paris',
                        'locale' => 'fr',
                        'currency' => 'EUR',
                        'status' => 'active',
                    ]);

                    $existing = User::create([
                        'name' => $fullName ?: $email,
                        'first_name' => $first,
                        'last_name' => $last,
                        'email' => $email,
                        'password' => Hash::make(Str::random(32)),
                        'is_active' => true,
                        'status' => 'active',
                        'tenant_id' => $tenant->id,
                        'role_in_tenant' => 'owner',
                        'is_tenant_owner' => true,
                        'email_verified_at' => now(),
                        'auth_provider' => 'google',
                        'auth_provider_id' => (string) $googleUser->getId(),
                    ]);

                    app(TenantRoleService::class)->syncUserRole($existing, (int) $tenant->id, 'owner', [
                        'is_tenant_owner' => true,
                        'status' => 'active',
                        'joined_at' => now(),
                    ]);
                    $sendWelcome = true;
                } else {
                    $wasVerified = $existing->hasVerifiedEmail();
                    $existing->forceFill([
                        'auth_provider' => 'google',
                        'auth_provider_id' => (string) $googleUser->getId(),
                    ])->save();

                    if (!$existing->tenant_id) {
                        $this->createTenantForUser($existing, $existing->company);
                    }

                    if (!$existing->hasVerifiedEmail()) {
                        $existing->markEmailAsVerified();
                    }

                    if (!$wasVerified && $existing->hasVerifiedEmail()) {
                        $sendWelcome = true;
                    }
                }

                return $existing;
            });

            if ($sendWelcome) {
                $this->sendWelcomeNotificationSafely($user, 'google_callback');
            }

            Auth::login($user, true);
            $this->handlePendingInvitationAfterAuthentication($request, $user);
            $request->session()->regenerate();
            $request->session()->put('current_tenant_id', $this->resolveActiveTenantIdForUser($user));

            $user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ])->save();

            return redirect($this->afterAuthRedirect($user))->with('success', 'Connexion Google reussie.');
        } catch (Throwable $e) {
            Log::warning('Google callback failed: ' . $e->getMessage());
            return redirect()->route('login')->withErrors([
                'email' => $this->resolveGoogleAuthErrorMessage($e),
            ]);
        }
    }

    public function verifyEmail(Request $request, int $id, string $hash)
    {
        /** @var User|null $user */
        $user = User::find($id);
        if (!$user) {
            abort(404);
        }

        if (!$request->hasValidSignature()) {
            abort(403, 'Lien de verification invalide ou expire.');
        }

        if (!hash_equals($hash, sha1($user->getEmailForVerification()))) {
            abort(403, 'Hash de verification invalide.');
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            $this->sendWelcomeNotificationSafely($user, 'email_verification');
        }

        if (!$user->tenant_id) {
            $this->createTenantForUser($user, $user->company);
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->put('current_tenant_id', (int) $user->tenant_id);

        return redirect($this->afterAuthRedirect($user))->with('success', 'Compte active avec succes.');
    }

    public function resendVerification(ResendVerificationRequest $request)
    {
        $data = $request->validated();

        /** @var User|null $user */
        $user = User::query()->where('email', (string) ($data['email'] ?? ''))->first();
        if (!$user) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun compte correspondant.',
                    'errors' => ['email' => ['Aucun compte correspondant.']],
                ], 404);
            }
            return back()->withErrors(['email' => 'Aucun compte correspondant.']);
        }

        if ($user->hasVerifiedEmail()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ce compte est déjà actif.',
                ]);
            }
            return back()->with('success', 'Ce compte est deja actif.');
        }

        $user->sendEmailVerificationNotification();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Email d’activation renvoyé.',
            ]);
        }

        return back()->with('success', 'Email d activation renvoye.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    private function buildGoogleClient(): GoogleClient
    {
        $clientId = (string) env('GOOGLE_CLIENT_ID');
        $clientSecret = (string) env('GOOGLE_CLIENT_SECRET');

        if (!$clientId || !$clientSecret) {
            throw new RuntimeException('Google OAuth non configure dans .env.');
        }

        $client = new GoogleClient();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $redirect = (string) env('GOOGLE_AUTH_REDIRECT_URI');
        if (!str_starts_with($redirect, 'http')) {
            $redirect = url($redirect ?: '/auth/google/callback');
        }
        $client->setRedirectUri($redirect);
        $client->setAccessType('offline');
        $client->setPrompt('select_account');
        $client->setIncludeGrantedScopes(true);
        $client->setScopes(['openid', 'email', 'profile']);

        return $client;
    }

    private function afterAuthRedirect(User $user): string
    {
        if ($user->tenant_id && OnboardingController::mustCompleteOnboarding($user)) {
            return route('onboarding.show');
        }

        return url('/dashboard');
    }

    private function createTenantForUser(User $user, ?string $company = null): void
    {
        $tenant = Tenant::create([
            'name' => $company ?: ('Entreprise de ' . ($user->first_name ?: $user->name ?: 'Client')),
            'slug' => Tenant::generateSlug($company ?: ('entreprise-' . Str::slug($user->name ?: $user->email))),
            'email' => $user->email,
            'timezone' => 'Europe/Paris',
            'locale' => 'fr',
            'currency' => 'EUR',
            'status' => 'active',
        ]);

        $user->forceFill([
            'tenant_id' => $tenant->id,
            'role_in_tenant' => 'owner',
            'is_tenant_owner' => true,
        ])->save();

        TenantUserMembership::query()->updateOrCreate(
            [
                'user_id' => (int) $user->id,
                'tenant_id' => (int) $tenant->id,
            ],
            [
                'role_in_tenant' => 'owner',
                'is_tenant_owner' => true,
                'status' => 'active',
                'joined_at' => now(),
            ]
        );

        app(TenantRoleService::class)->syncUserRole($user, (int) $tenant->id, 'owner', [
            'is_tenant_owner' => true,
            'status' => 'active',
            'joined_at' => now(),
        ]);
    }

    private function splitName(string $value): array
    {
        $parts = preg_split('/\s+/', trim($value)) ?: [];
        $first = $parts[0] ?? '';
        $last = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';

        return [$first, $last];
    }

    private function sendWelcomeNotificationSafely(User $user, string $context): void
    {
        try {
            $user->notify(new WelcomeAccountNotification());
        } catch (Throwable $e) {
            Log::warning('Welcome notification failed', [
                'context' => $context,
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolveGoogleAuthErrorMessage(Throwable $e): string
    {
        $message = mb_strtolower($e->getMessage());

        if (
            str_contains($message, 'google oauth non configure')
            || str_contains($message, 'invalid_client')
            || str_contains($message, 'client_secret')
            || str_contains($message, 'client_id')
            || str_contains($message, 'redirect_uri_mismatch')
        ) {
            return 'Connexion Google impossible. Vérifiez GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET et l’URL de callback.';
        }

        if (
            str_contains($message, 'smtp server')
            || str_contains($message, 'authentication rejected')
            || str_contains($message, 'mailer')
            || str_contains($message, 'mail')
        ) {
            return 'Connexion Google réussie, mais l’envoi d’email automatique a échoué. Vérifiez la configuration SMTP.';
        }

        if (str_contains($message, 'invalid_grant') || str_contains($message, 'etat oauth invalide')) {
            return 'Connexion Google expirée ou invalide. Relancez la connexion Google puis réessayez.';
        }

        return 'Connexion Google impossible pour le moment. Vérifiez les logs serveur pour le détail.';
    }

    private function handlePendingInvitationAfterAuthentication(Request $request, User $user): void
    {
        $token = (string) $request->session()->get('pending_invitation_token', '');
        if ($token === '') {
            return;
        }

        $invitation = app(UserRepository::class)->findInvitationByToken($token);
        if (!$invitation) {
            $request->session()->forget(['pending_invitation_token', 'pending_invitation_email']);
            return;
        }

        if (mb_strtolower((string) $user->email) !== mb_strtolower((string) $invitation->email)) {
            throw new RuntimeException('Cette invitation est liée à une autre adresse email.');
        }

        if ($invitation->isUsable() && !$user->hasTenantAccess((int) $invitation->tenant_id)) {
            app(UserService::class)->acceptInvitation($invitation, ['user' => $user]);
            $request->session()->put('current_tenant_id', (int) $invitation->tenant_id);
        }

        $request->session()->forget(['pending_invitation_token', 'pending_invitation_email']);
    }

    private function resolveActiveTenantIdForUser(User $user, int $requestedTenantId = 0): int
    {
        if ($requestedTenantId > 0 && $user->hasTenantAccess($requestedTenantId)) {
            $membership = $user->membershipForTenant($requestedTenantId);
            if (!$membership || (string) $membership->status === 'active') {
                return $requestedTenantId;
            }
        }

        $baseTenantId = (int) ($user->getOriginal('tenant_id') ?: $user->tenant_id ?: 0);
        if ($baseTenantId > 0 && $user->hasTenantAccess($baseTenantId)) {
            $membership = $user->membershipForTenant($baseTenantId);
            if (!$membership || (string) $membership->status === 'active') {
                return $baseTenantId;
            }
        }

        $membership = $user->tenantMemberships()
            ->where('status', 'active')
            ->orderByDesc('is_tenant_owner')
            ->orderBy('id')
            ->first();

        return $membership ? (int) $membership->tenant_id : 0;
    }
}
