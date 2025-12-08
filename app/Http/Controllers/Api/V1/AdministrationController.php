<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAdministrationRequest;
use App\Http\Requests\Api\V1\UpdateAdministrationRequest;
use App\Http\Resources\Api\V1\AdministrationResource;
use App\Models\Administration;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdministrationController extends Controller
{
    /**
     * Liste des administrations
     */
    public function index(Request $request)
    {
        $query = Administration::query();

        // Filtres
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('type_administration')) {
            $query->where('type_administration', $request->type_administration);
        }

        if ($request->has('pays')) {
            $query->where('pays', $request->pays);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('numero_accreditation', 'like', "%{$search}%");
            });
        }

        $administrations = $query->orderBy('date_inscription', 'desc')
                                ->paginate($request->get('per_page', 15));

        return AdministrationResource::collection($administrations);
    }

    /**
     * Afficher une administration
     */
    public function show($id)
    {
        $administration = Administration::with(['documents'])->findOrFail($id);
        return new AdministrationResource($administration);
    }

    /**
     * Créer une administration
     */
    public function store(StoreAdministrationRequest $request)
    {
        $validated = $request->validated();

        $administration = Administration::create([
            'nom' => $validated['nom'],
            'type_administration' => $validated['type_administration'],
            'pays' => $validated['pays'],
            'ville' => $validated['ville'] ?? null,
            'adresse' => $validated['adresse'] ?? null,
            'numero_accreditation' => $validated['numero_accreditation'] ?? null,
            'email_contact' => $validated['email_contact'],
            'telephone_contact' => $validated['telephone_contact'] ?? null,
            'cle_publique_ed25519' => $validated['cle_publique_ed25519'],
            'logo_url' => $validated['logo_url'] ?? null,
            'site_web' => $validated['site_web'] ?? null,
            'statut' => 'en_attente',
            'date_inscription' => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Administration créée avec succès',
            'administration' => new AdministrationResource($administration),
        ], 201);
    }

    /**
     * Mettre à jour une administration
     */
    public function update(UpdateAdministrationRequest $request, $id)
    {
        $administration = Administration::findOrFail($id);
        $validated = $request->validated();

        $administration->update($validated);

        return response()->json([
            'message' => 'Administration mise à jour avec succès',
            'administration' => new AdministrationResource($administration->fresh()),
        ]);
    }

    /**
     * Supprimer une administration
     */
    public function destroy($id)
    {
        $administration = Administration::findOrFail($id);
        $administration->delete();

        return response()->json([
            'message' => 'Administration supprimée avec succès',
        ]);
    }

    /**
     * Vérifier une administration
     */
    public function verify($id)
    {
        $administration = Administration::findOrFail($id);
        $administration->update(['statut' => 'verifie']);

        return response()->json([
            'message' => 'Administration vérifiée avec succès',
            'administration' => new AdministrationResource($administration),
        ]);
    }

    /**
     * Suspendre une administration
     */
    public function suspend($id)
    {
        $administration = Administration::findOrFail($id);
        $administration->update(['statut' => 'suspendu']);

        return response()->json([
            'message' => 'Administration suspendue avec succès',
            'administration' => new AdministrationResource($administration),
        ]);
    }
}

