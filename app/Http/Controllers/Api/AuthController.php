<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AuthController extends Controller
{
    /**
     * Connexion utilisateur avec Sanctum
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Tentative de connexion
            if (!Auth::attempt($request->only('email', 'password'), $request->remember)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email ou mot de passe incorrect'
                ], 401);
            }

            $user = Auth::user();
            
            // Vérification du statut actif
            if (!$user->is_active) {
                Auth::logout();
                return response()->json([
                    'success' => false,
                    'message' => 'Votre compte est désactivé. Veuillez contacter l\'administrateur.',
                    'code' => 'account_disabled'
                ], 403);
            }

            DB::beginTransaction();


            Auth::login($user, $request->remember);

            // Supprimer les anciens tokens (optionnel)
            // $user->tokens()->delete();

            // Créer un nouveau token Sanctum
            $token = $user->createToken('auth_token', ['*'])->plainTextToken;

            // Mettre à jour la dernière connexion
            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'company' => $user->company,
                    'avatar' => $user->avatar,
                    'phone' => $user->phone,
                    'position' => $user->position,
                    'is_active' => $user->is_active,
                    'initials' => $user->initials,
                    'role' => $user->getRoleNames()->first(),
                    'permissions' => $user->getAllPermissions()->pluck('name')
                ],
                'redirect' => '/dashboard'
            ]);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Login error: ' . $e->getMessage(), [
                'email' => $request->email,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la connexion. Veuillez réessayer.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Inscription utilisateur
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255|min:2',
                'last_name' => 'required|string|max:255|min:2',
                'email' => 'required|string|email|max:255|unique:users',
                'company' => 'nullable|string|max:255',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $user = User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'company' => $request->company,
                'password' => Hash::make($request->password),
                'is_active' => true, // Par défaut, le compte est actif
            ]);

            if (!$user) {
                throw new \Exception("Impossible de créer l'utilisateur");
            }

            // Assigner le rôle par défaut 'user'
            $user->assignRole('user');
            
            // Événement d'inscription
            event(new Registered($user));
            
            // Créer un token pour l'API
            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie',
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'company' => $user->company,
                    'is_active' => $user->is_active,
                    'initials' => $user->initials,
                    'role' => $user->getRoleNames()->first(),
                ],
                'redirect' => '/dashboard'
            ], 201);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Registration error: ' . $e->getMessage(), [
                'email' => $request->email,
                'trace' => $e->getTraceAsString()
            ]);

            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet email est déjà utilisé.',
                    'errors' => ['email' => ['Cet email est déjà utilisé.']]
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'inscription.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Déconnexion - Révoquer le token
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            
            if ($user) {
                // Révoquer le token actuel
                $user->currentAccessToken()->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie'
            ]);

        } catch (Throwable $e) {
            Log::error('Logout error: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion'
            ], 500);
        }
    }

    /**
     * Obtenir l'utilisateur connecté
     */
    public function user(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'company' => $user->company,
                    'avatar' => $user->avatar,
                    'phone' => $user->phone,
                    'position' => $user->position,
                    'bio' => $user->bio,
                    'is_active' => $user->is_active,
                    'initials' => $user->initials,
                    'last_login_at' => $user->last_login_at,
                    'created_at' => $user->created_at,
                ],
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name')
            ]);

        } catch (Throwable $e) {
            Log::error('Get user error: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des informations'
            ], 500);
        }
    }

    /**
     * Rafraîchir le token
     */
    public function refreshToken(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            DB::beginTransaction();

            // Supprimer l'ancien token
            $user->currentAccessToken()->delete();
            
            // Créer un nouveau token
            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'success' => true,
                'token' => $token,
                'token_type' => 'Bearer',
                'message' => 'Token rafraîchi avec succès'
            ]);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Token refresh error: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rafraîchissement du token'
            ], 500);
        }
    }

    /**
     * Désactiver un compte utilisateur (Admin uniquement)
     */
    public function disableAccount(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Vérifier que l'utilisateur connecté est admin
            if (!$request->user()->hasRole('super_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas les droits pour désactiver un compte'
                ], 403);
            }

            DB::beginTransaction();

            $user->is_active = false;
            $user->save();
            
            // Révoquer tous les tokens de l'utilisateur désactivé
            $user->tokens()->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Compte désactivé avec succès',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'is_active' => $user->is_active
                ]
            ]);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Disable account error: ' . $e->getMessage(), [
                'user_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la désactivation du compte'
            ], 500);
        }
    }

    /**
     * Activer un compte utilisateur (Admin uniquement)
     */
    public function enableAccount(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            
            if (!$request->user()->hasRole('super_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas les droits pour activer un compte'
                ], 403);
            }

            DB::beginTransaction();

            $user->is_active = true;
            $user->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Compte activé avec succès',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'is_active' => $user->is_active
                ]
            ]);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Enable account error: ' . $e->getMessage(), [
                'user_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'activation du compte'
            ], 500);
        }
    }
}