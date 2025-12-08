<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AdminRegisterRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Requests\Api\V1\ChangePasswordRequest;
use App\Http\Requests\Api\V1\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\ResetPasswordRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\AdminAccessCode;
use App\Models\Administration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Enregistrement d'un nouvel utilisateur
     */
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();
        
        $userData = [
            'prenom' => $validated['prenom'],
            'nom' => $validated['nom'],
            'email' => $validated['email'],
            'mot_de_passe_hash' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'telephone' => $validated['telephone'],
            'langue' => $validated['langue'] ?? 'fr',
            'is_active' => true,
        ];

        // Ajouter le nom de l'entreprise si présent (pour les recruteurs)
        if (isset($validated['nom_entreprise'])) {
            $userData['nom_entreprise'] = $validated['nom_entreprise'];
        }

        $user = User::create($userData);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Inscription réussie',
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    /**
     * Enregistrement d'un nouvel administrateur (université) avec code d'accès.
     */
    public function registerAdmin(AdminRegisterRequest $request)
    {
        $validated = $request->validated();

        // Vérifier que le code n'a pas déjà été utilisé
        $accessCode = AdminAccessCode::where('code', $validated['code_acces'])
            ->whereNull('used_at')
            ->first();

        if (!$accessCode) {
            return response()->json([
                'message' => 'Ce code d\'accès a déjà été utilisé ou est invalide.',
            ], 422);
        }

        // Créer (ou récupérer) l'administration correspondante
        $administration = Administration::firstOrCreate(
            [
                'nom' => $validated['nom_universite'],
                'email_contact' => $validated['email'],
            ],
            [
                'type_administration' => 'universite',
                'pays' => 'Sénégal',
                'ville' => null,
                'adresse' => $validated['adresse_universite'],
                'statut' => 'verifie',
            ]
        );

        // Créer l'utilisateur avec rôle admin (université)
        $user = User::create([
            'prenom' => $validated['prenom'],
            'nom' => $validated['nom'],
            'email' => $validated['email'],
            'mot_de_passe_hash' => Hash::make($validated['password']),
            'role' => 'admin',
            'telephone' => $validated['telephone'],
            'langue' => 'fr',
            'is_active' => true,
        ]);

        // Marquer le code comme utilisé (usage unique)
        $accessCode->update([
            'used_at' => now(),
            'used_by_email' => $user->email,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Inscription administrateur réussie',
            'user' => new UserResource($user),
            'token' => $token,
            'administration_id' => $administration->id,
        ], 201);
    }

    /**
     * Connexion
     */
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        /**
         * Cas particulier : compte administrateur principal UCAD.
         * On garantit qu'il existe et qu'il utilise le mot de passe
         * fixe @dministrateur2025 pour éviter les divergences de seed.
         */
        if (
            $validated['email'] === 'admin@ucad.edu.sn'
            && $validated['password'] === '@dministrateur2025'
        ) {
            $user = User::updateOrCreate(
                ['email' => 'admin@ucad.edu.sn'],
                [
                    'prenom' => 'Admin',
                    'nom' => 'UCAD',
                    'mot_de_passe_hash' => Hash::make('@dministrateur2025'),
                    'role' => 'admin',
                    'telephone' => '+221000000000',
                    'langue' => 'fr',
                    'is_active' => true,
                ]
            );
        } else {
            $user = User::where('email', $validated['email'])->first();

            if (!$user || !Hash::check($validated['password'], $user->mot_de_passe_hash)) {
                throw ValidationException::withMessages([
                    'email' => ['Les identifiants fournis sont incorrects.'],
                ]);
            }
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Votre compte est désactivé',
            ], 403);
        }

        // Mise à jour de la dernière connexion
        $user->update(['derniere_connexion' => now()]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie',
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie',
        ]);
    }

    /**
     * Obtenir l'utilisateur connecté
     */
    public function me(Request $request)
    {
        $user = $request->user();
        
        // Charger les relations selon le rôle
        if ($user->role === 'etudiant') {
            $user->load('profilEtudiant');
        } elseif ($user->role === 'recruteur') {
            $user->load('offres');
        }

        return response()->json([
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Mettre à jour le profil
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->update($validated);

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'user' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Changer le mot de passe
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        if (!Hash::check($validated['current_password'], $user->mot_de_passe_hash)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        $user->update([
            'mot_de_passe_hash' => Hash::make($validated['new_password']),
        ]);

        // Révoquer tous les anciens tokens
        $user->tokens()->delete();

        // Créer un nouveau token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Mot de passe modifié avec succès',
            'token' => $token,
        ]);
    }

    /**
     * Demande de réinitialisation de mot de passe
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Lien de réinitialisation envoyé à votre adresse email',
            ]);
        }

        return response()->json([
            'message' => 'Impossible d\'envoyer le lien de réinitialisation',
        ], 400);
    }

    /**
     * Réinitialisation du mot de passe
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->mot_de_passe_hash = Hash::make($password);
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Mot de passe réinitialisé avec succès',
            ]);
        }

        return response()->json([
            'message' => 'Impossible de réinitialiser le mot de passe',
        ], 400);
    }
}

