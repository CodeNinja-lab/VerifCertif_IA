<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreOffreRequest;
use App\Http\Requests\Api\V1\UpdateOffreRequest;
use App\Http\Requests\Api\V1\SyncExternalOffersRequest;
use App\Http\Requests\Api\V1\AddCompetenceToOffreRequest;
use App\Http\Resources\Api\V1\OffreResource;
use App\Http\Resources\Api\V1\OffreCompetenceResource;
use App\Http\Resources\Api\V1\MatchingResource;
use App\Models\Offre;
use App\Models\OffreCompetence;
use App\Models\OffreView;
use App\Models\Competence;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OffreController extends Controller
{
    /**
     * Liste des offres du recruteur (authentifiée)
     */
    public function myOffres(Request $request)
    {
        $user = $request->user();
        
        if ($user->role !== 'recruteur' && $user->role !== 'admin') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }
        
        $query = Offre::with(['recruteur', 'offreCompetences.competence'])
                     ->withCount('candidatures');
        
        // Pour les recruteurs, filtrer uniquement leurs offres
        // Pour les admins, montrer toutes les offres
        if ($user->role === 'recruteur') {
            $query->where('recruteur_id', $user->id);
        }
        
        // Filtrer par statut si demandé
        if ($request->has('statut') && $request->statut !== 'all') {
            $query->where('statut', $request->statut);
        }
        
        // Autres filtres
        if ($request->has('type_contrat')) {
            $query->where('type_contrat', $request->type_contrat);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('entreprise', 'like', "%{$search}%");
            });
        }
        
        $offres = $query->orderByRaw('COALESCE(date_publication, created_at) DESC')
                       ->paginate($request->get('per_page', 15));
        
        return OffreResource::collection($offres);
    }

    /**
     * Liste des offres
     */
    public function index(Request $request)
    {
        $query = Offre::with(['recruteur', 'offreCompetences.competence'])
                     ->withCount('candidatures');

        // Vérifier si c'est pour voir ses propres offres
        // Si my_offres est demandé, on DOIT être authentifié
        $user = $request->user();
        $isMyOffres = $request->has('my_offres');
        
        if ($isMyOffres) {
            // Si my_offres est demandé mais pas d'utilisateur authentifié, erreur
            if (!$user) {
                return response()->json(['message' => 'Authentification requise pour voir vos offres'], 401);
            }
            
            // Vérifier que c'est un recruteur ou admin
            if ($user->role !== 'recruteur' && $user->role !== 'admin') {
                return response()->json(['message' => 'Accès refusé'], 403);
            }
            
            // Filtrer strictement par recruteur_id pour les recruteurs
            if ($user->role === 'recruteur') {
                $query->where('recruteur_id', $user->id);
            }
            // Pour les admins, on pourrait voir toutes les offres, mais on filtre aussi par leur ID si my_offres
            // Pour l'instant, on laisse les admins voir toutes les offres même avec my_offres
            
            // Pour ses propres offres, on peut voir tous les statuts
            // Sauf si un filtre statut est spécifiquement demandé
            if ($request->has('statut') && $request->statut !== 'all') {
                $query->where('statut', $request->statut);
            }
            // Sinon, on affiche toutes ses offres (tous statuts)
        } else {
            // Pour les offres publiques, filtrer par statut
            if ($request->has('statut') && $request->statut !== 'all') {
                $query->where('statut', $request->statut);
            } else {
                // Par défaut, ne montrer que les offres publiées pour les visiteurs
                $query->where('statut', 'PUBLIEE');
            }
        }

        if ($request->has('type_contrat')) {
            $query->where('type_contrat', $request->type_contrat);
        }

        if ($request->has('teletravail')) {
            $query->where('teletravail', $request->teletravail);
        }

        if ($request->has('lieu')) {
            $query->where('lieu', 'like', "%{$request->lieu}%");
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('entreprise', 'like', "%{$search}%");
            });
        }

        // Trier par date de publication (desc) ou date de création si pas de publication
        $offres = $query->orderByRaw('COALESCE(date_publication, created_at) DESC')
                       ->paginate($request->get('per_page', 15));

        return OffreResource::collection($offres);
    }

    /**
     * Afficher une offre
     */
    public function show(Request $request, $id)
    {
        $offre = Offre::with(['recruteur', 'offreCompetences.competence'])->findOrFail($id);
        
        // Incrémenter le nombre de vues uniquement si c'est un étudiant authentifié
        // et qu'il n'a pas déjà vu cette offre
        $user = $request->user();
        
        if ($user && $user->role === 'etudiant') {
            // Vérifier si l'étudiant a déjà vu cette offre
            $existingView = OffreView::where('offre_id', $offre->id)
                ->where('user_id', $user->id)
                ->first();
            
            // Si pas de vue existante, créer une nouvelle vue et incrémenter le compteur
            if (!$existingView) {
                OffreView::create([
                    'offre_id' => $offre->id,
                    'user_id' => $user->id,
                    'viewed_at' => now(),
                ]);
                
                $offre->increment('nombre_vues');
            }
        }

        return new OffreResource($offre);
    }

    /**
     * Créer une offre
     */
    public function store(StoreOffreRequest $request)
    {
        $validated = $request->validated();

        $offre = Offre::create([
            'recruteur_id' => $request->user()->id,
            'titre' => $validated['titre'],
            'description' => $validated['description'],
            'missions_principales' => $validated['missions_principales'] ?? null,
            'profil_recherche' => $validated['profil_recherche'] ?? null,
            'nice_to_have' => $validated['nice_to_have'] ?? null,
            'avantages' => $validated['avantages'] ?? null,
            'processus_recrutement' => $validated['processus_recrutement'] ?? null,
            'entreprise' => $validated['entreprise'],
            'secteur_activite' => $validated['secteur_activite'] ?? null,
            'lieu' => $validated['lieu'],
            'type_contrat' => $validated['type_contrat'],
            'duree_contrat_mois' => $validated['duree_contrat_mois'] ?? null,
            'teletravail' => $validated['teletravail'] ?? null,
            'salaire_min' => $validated['salaire_min'] ?? null,
            'salaire_max' => $validated['salaire_max'] ?? null,
            'devise' => $validated['devise'] ?? 'XOF',
            'niveau_etudes_requis' => $validated['niveau_etudes_requis'] ?? null,
            'annees_experience_min' => $validated['annees_experience_min'] ?? null,
            'date_expiration' => $validated['date_expiration'] ?? null,
            'statut' => 'BROUILLON',
        ]);

        return response()->json([
            'message' => 'Offre créée avec succès',
            'offre' => new OffreResource($offre->load('recruteur')),
        ], 201);
    }

    /**
     * Mettre à jour une offre
     */
    public function update(UpdateOffreRequest $request, $id)
    {
        $offre = Offre::findOrFail($id);

        // Vérifier les permissions
        if ($offre->recruteur_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $validated = $request->validated();
        $offre->update($validated);

        return response()->json([
            'message' => 'Offre mise à jour avec succès',
            'offre' => new OffreResource($offre->fresh()->load('recruteur', 'offreCompetences.competence')),
        ]);
    }

    /**
     * Synchroniser les offres externes d'une source dédiée.
     */
    public function syncExternalOffers(SyncExternalOffersRequest $request)
    {
        $validated = $request->validated();
        $importerEmail = config('app_constants.expat_dakar_import.email', env('EXPAT_DAKAR_IMPORT_EMAIL', 'expat-dakar-import@vericertis.sn'));
        $importerUser = User::where('email', $importerEmail)->first();

        if (!$importerUser) {
            return response()->json([
                'message' => 'Le compte technique Expat Dakar Import est introuvable.',
            ], 500);
        }

        $authenticatedUser = $request->user();

        if (!$authenticatedUser || $authenticatedUser->id !== $importerUser->id) {
            return response()->json([
                'message' => 'Accès refusé: la synchronisation Expat Dakar utilise un seul compte technique dédié.',
            ], 403);
        }

        $sourceName = $validated['source_name'];
        $sourceAccount = $validated['source_account'];
        $now = Carbon::now();
        $incomingIds = collect($validated['offers'])->pluck('external_id')->all();

        $result = DB::transaction(function () use ($validated, $importerUser, $sourceName, $sourceAccount, $now, $incomingIds) {
            $created = 0;
            $updated = 0;
            $skipped = 0;

            foreach ($validated['offers'] as $payload) {
                $criteria = [
                    'recruteur_id' => $importerUser->id,
                    'source_name' => $sourceName,
                    'source_account' => $sourceAccount,
                    'source_external_id' => $payload['external_id'],
                ];

                $offre = Offre::firstOrNew($criteria);
                $wasNew = !$offre->exists;
                $before = $wasNew ? null : Arr::only($offre->getRawOriginal(), [
                    'titre',
                    'description',
                    'entreprise',
                    'lieu',
                    'type_contrat',
                    'teletravail',
                    'salaire_min',
                    'salaire_max',
                    'devise',
                    'date_expiration',
                    'statut',
                    'source_url',
                ]);

                $attributes = [
                    'titre' => $payload['title'],
                    'description' => $payload['description'],
                    'entreprise' => $payload['company'],
                    'lieu' => $payload['location'] ?: 'Dakar, Sénégal',
                    'type_contrat' => $payload['contract_type'] ?: 'CDI',
                    'teletravail' => $payload['remote_type'] ?? null,
                    'salaire_min' => $payload['salary_min'] ?? null,
                    'salaire_max' => $payload['salary_max'] ?? null,
                    'devise' => $payload['currency'] ?? 'XOF',
                    'date_expiration' => $payload['expires_at'] ?? null,
                    'statut' => 'PUBLIEE',
                    'source_url' => $payload['source_url'] ?? ($payload['metadata']['source_url'] ?? null),
                    'source_imported_at' => $offre->source_imported_at ?? $now,
                    'source_last_seen_at' => $now,
                ];

                $offre->fill($attributes);
                $desiredComparable = Arr::only($attributes, [
                    'titre',
                    'description',
                    'entreprise',
                    'lieu',
                    'type_contrat',
                    'teletravail',
                    'salaire_min',
                    'salaire_max',
                    'devise',
                    'date_expiration',
                    'statut',
                    'source_url',
                ]);

                $offre->save();

                if ($wasNew) {
                    $created++;
                    continue;
                }

                if ($before === $desiredComparable) {
                    $skipped++;
                } else {
                    $updated++;
                }
            }

            $expired = 0;
            if (($validated['mark_missing_as_expired'] ?? true) === true) {
                $expired = Offre::where('recruteur_id', $importerUser->id)
                    ->where('source_name', $sourceName)
                    ->where('source_account', $sourceAccount)
                    ->whereNotIn('source_external_id', $incomingIds)
                    ->update([
                        'statut' => 'EXPIREE',
                        'source_last_seen_at' => $now,
                    ]);
            }

            return [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'expired' => $expired,
            ];
        });

        return response()->json([
            'message' => 'Synchronisation externe terminée',
            'source_name' => $sourceName,
            'source_account' => $sourceAccount,
            'recruteur_id' => $importerUser->id,
            'received' => count($validated['offers']),
            'created' => $result['created'],
            'updated' => $result['updated'],
            'skipped' => $result['skipped'],
            'expired' => $result['expired'],
        ]);
    }

    /**
     * Supprimer une offre
     */
    public function destroy(Request $request, $id)
    {
        $offre = Offre::findOrFail($id);

        // Vérifier les permissions
        if ($offre->recruteur_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $offre->delete();

        return response()->json([
            'message' => 'Offre supprimée avec succès',
        ]);
    }

    /**
     * Publier une offre
     */
    public function publish(Request $request, $id)
    {
        $offre = Offre::findOrFail($id);

        // Vérifier les permissions
        if ($offre->recruteur_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $offre->update([
            'statut' => 'PUBLIEE',
            'date_publication' => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Offre publiée avec succès',
            'offre' => new OffreResource($offre->fresh()),
        ]);
    }

    /**
     * Archiver une offre
     */
    public function archive(Request $request, $id)
    {
        $offre = Offre::findOrFail($id);

        // Vérifier les permissions
        if ($offre->recruteur_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $offre->update(['statut' => 'ARCHIVEE']);

        return response()->json([
            'message' => 'Offre archivée avec succès',
            'offre' => new OffreResource($offre->fresh()),
        ]);
    }

    /**
     * Obtenir les compétences d'une offre
     */
    public function getCompetences($id)
    {
        $offre = Offre::findOrFail($id);
        $competences = OffreCompetence::with('competence')
                                     ->where('offre_id', $offre->id)
                                     ->get();

        return OffreCompetenceResource::collection($competences);
    }

    /**
     * Ajouter une compétence à une offre
     */
    public function addCompetence(AddCompetenceToOffreRequest $request, $id)
    {
        $offre = Offre::findOrFail($id);

        // Vérifier les permissions
        if ($offre->recruteur_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $validated = $request->validated();

        // Vérifier si la compétence existe déjà
        $existing = OffreCompetence::where('offre_id', $offre->id)
                                   ->where('competence_id', $validated['competence_id'])
                                   ->first();

        if ($existing) {
            return response()->json(['message' => 'Cette compétence existe déjà dans l\'offre'], 422);
        }

        $offreCompetence = OffreCompetence::create([
            'offre_id' => $offre->id,
            'competence_id' => $validated['competence_id'],
            'niveau_requis' => $validated['niveau_requis'] ?? null,
            'importance' => $validated['importance'] ?? 'souhaitee',
            'poids' => $validated['poids'] ?? 5,
        ]);

        return response()->json([
            'message' => 'Compétence ajoutée à l\'offre avec succès',
            'competence' => new OffreCompetenceResource($offreCompetence->load('competence')),
        ], 201);
    }

    /**
     * Mettre à jour une compétence d'une offre
     */
    public function updateCompetence(Request $request, $id, $competenceId)
    {
        $offre = Offre::findOrFail($id);

        // Vérifier les permissions
        if ($offre->recruteur_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $offreCompetence = OffreCompetence::where('offre_id', $offre->id)
                                         ->where('competence_id', $competenceId)
                                         ->firstOrFail();

        $validated = $request->validate([
            'niveau_requis' => 'sometimes|in:debutant,intermediaire,avance,expert',
            'importance' => 'sometimes|in:indispensable,importante,souhaitee,bonus',
            'poids' => 'sometimes|integer|min:1|max:10',
        ]);

        $offreCompetence->update($validated);

        return response()->json([
            'message' => 'Compétence mise à jour avec succès',
            'competence' => new OffreCompetenceResource($offreCompetence->fresh()->load('competence')),
        ]);
    }

    /**
     * Supprimer une compétence d'une offre
     */
    public function removeCompetence(Request $request, $id, $competenceId)
    {
        $offre = Offre::findOrFail($id);

        // Vérifier les permissions
        if ($offre->recruteur_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $offreCompetence = OffreCompetence::where('offre_id', $offre->id)
                                         ->where('competence_id', $competenceId)
                                         ->firstOrFail();

        $offreCompetence->delete();

        return response()->json([
            'message' => 'Compétence supprimée de l\'offre avec succès',
        ]);
    }

    /**
     * Obtenir les matchings d'une offre (pour recruteurs)
     */
    public function getMatchings(Request $request, $id)
    {
        $offre = Offre::findOrFail($id);

        // Vérifier les permissions
        if ($offre->recruteur_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $matchings = $offre->matchings()
                          ->with(['etudiant.profilEtudiant', 'offre'])
                          ->orderBy('score_global', 'desc')
                          ->paginate($request->get('per_page', 15));

        return MatchingResource::collection($matchings);
    }
}

