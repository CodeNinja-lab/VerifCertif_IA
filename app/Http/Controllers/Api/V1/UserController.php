<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreUserRequest;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserController extends Controller
{
    /**
     * Liste des utilisateurs
     */
    public function index(Request $request)
    {
        $query = User::with(['profilEtudiant']);

        // Filtres
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active === 'true');
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('date_creation', 'desc')
                      ->paginate($request->get('per_page', 15));

        return UserResource::collection($users);
    }

    /**
     * Afficher un utilisateur
     */
    public function show($id)
    {
        $user = User::with(['profilEtudiant', 'documents', 'offres'])->findOrFail($id);
        return new UserResource($user);
    }

    /**
     * Créer un utilisateur
     */
    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'prenom' => $validated['prenom'],
            'nom' => $validated['nom'],
            'email' => $validated['email'],
            'mot_de_passe_hash' => Hash::make($validated['password']),
            'role' => $validated['role'] ?? 'etudiant',
            'telephone' => $validated['telephone'] ?? null,
            'langue' => $validated['langue'] ?? 'fr',
            'date_creation' => Carbon::now(),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Utilisateur créé avec succès',
            'user' => new UserResource($user),
        ], 201);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(UpdateUserRequest $request, $id)
    {
        $user = User::findOrFail($id);
        $validated = $request->validated();

        if (isset($validated['password'])) {
            $validated['mot_de_passe_hash'] = Hash::make($validated['password']);
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Utilisateur mis à jour avec succès',
            'user' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'message' => 'Utilisateur supprimé avec succès',
        ]);
    }

    /**
     * Activer un utilisateur
     */
    public function activate($id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => true]);

        return response()->json([
            'message' => 'Utilisateur activé avec succès',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Désactiver un utilisateur
     */
    public function deactivate($id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => false]);

        return response()->json([
            'message' => 'Utilisateur désactivé avec succès',
            'user' => new UserResource($user),
        ]);
    }
}

