<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Matching;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    /**
     * Obtenir une conversation par ID
     */
    public function getConversation(Request $request, $id)
    {
        $user = $request->user();
        
        $conversation = Conversation::where('id', $id)
            ->where(function($query) use ($user) {
                $query->where('recruteur_id', $user->id)
                      ->orWhere('etudiant_id', $user->id);
            })
            ->with(['etudiant.profilEtudiant', 'offre', 'messages.sender'])
            ->firstOrFail();

        // Charger les messages
        $conversation->load(['messages' => function($query) {
            $query->orderBy('created_at', 'asc');
        }]);

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'candidate' => $conversation->etudiant->name,
                'candidate_id' => $conversation->etudiant_id,
                'position' => $conversation->etudiant->profilEtudiant->titre_profil ?? 'Candidat',
                'offre_id' => $conversation->offre_id,
                'offre_titre' => $conversation->offre->titre ?? null,
            ],
            'messages' => $conversation->messages->map(function($msg) use ($user) {
                return [
                    'id' => $msg->id,
                    'sender' => $msg->sender_id === $user->id ? 'me' : 'candidate',
                    'content' => $msg->content,
                    'time' => $msg->created_at->format('H:i'),
                    'date' => $msg->created_at->format('Y-m-d'),
                    'is_read' => $msg->is_read,
                ];
            }),
        ]);
    }

    /**
     * Liste des conversations pour le recruteur
     */
    public function conversations(Request $request)
    {
        $user = $request->user();
        
        if ($user->role !== 'recruteur' && $user->role !== 'admin') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $conversations = Conversation::where('recruteur_id', $user->id)
            ->with(['etudiant.profilEtudiant', 'offre'])
            ->with(['messages' => function($query) {
                $query->latest()->limit(1);
            }, 'messages.sender'])
            ->orderBy('last_message_at', 'desc')
            ->get()
            ->map(function($conv) {
                $lastMessage = $conv->messages->first();
                $profil = $conv->etudiant->profilEtudiant;
                
                return [
                    'id' => $conv->id,
                    'candidate' => $conv->etudiant->name,
                    'candidate_id' => $conv->etudiant_id,
                    'position' => $profil->titre_profil ?? 'Candidat',
                    'avatar' => null,
                    'lastMessage' => $lastMessage ? $lastMessage->content : 'Aucun message',
                    'time' => $lastMessage ? $lastMessage->created_at->diffForHumans() : '',
                    'unread' => $conv->recruteur_has_unread ? 1 : 0,
                    'online' => false, // Peut être implémenté plus tard
                    'starred' => false, // Peut être implémenté plus tard
                    'offre_id' => $conv->offre_id,
                    'offre_titre' => $conv->offre->titre ?? null,
                ];
            });

        return response()->json(['data' => $conversations]);
    }

    /**
     * Liste des conversations pour l'étudiant
     */
    public function studentConversations(Request $request)
    {
        $user = $request->user();

        $conversations = Conversation::where('etudiant_id', $user->id)
            ->with(['recruteur', 'offre'])
            ->with(['messages' => function($query) {
                $query->latest()->limit(1);
            }, 'messages.sender'])
            ->orderBy('last_message_at', 'desc')
            ->get()
            ->map(function($conv) use ($user) {
                $lastMessage = $conv->messages->first();
                $unreadCount = Message::where('conversation_id', $conv->id)
                    ->where('sender_id', '!=', $user->id)
                    ->where('is_read', false)
                    ->count();
                
                return [
                    'id' => $conv->id,
                    'company' => $conv->recruteur->nom_entreprise ?? $conv->recruteur->name,
                    'recruiter' => $conv->recruteur->name,
                    'recruiter_id' => $conv->recruteur_id,
                    'avatar' => $conv->recruteur->photo_url,
                    'lastMessage' => $lastMessage ? $lastMessage->content : 'Aucun message',
                    'time' => $lastMessage ? $lastMessage->created_at->diffForHumans() : '',
                    'unread' => $unreadCount,
                    'online' => false,
                    'offre_id' => $conv->offre_id,
                    'offre_titre' => $conv->offre->titre ?? null,
                ];
            });

        return response()->json(['success' => true, 'data' => $conversations]);
    }

    /**
     * Obtenir une conversation par ID (version étudiant)
     */
    public function getStudentConversation(Request $request, $id)
    {
        $user = $request->user();
        
        $conversation = Conversation::where('id', $id)
            ->where('etudiant_id', $user->id)
            ->with(['recruteur', 'offre', 'messages.sender'])
            ->firstOrFail();

        $conversation->load(['messages' => function($query) {
            $query->orderBy('created_at', 'asc');
        }]);

        // Marquer les messages comme lus
        Message::where('conversation_id', $id)
            ->where('sender_id', '!=', $user->id)
            ->update(['is_read' => true, 'read_at' => now()]);
        
        $conversation->update(['etudiant_has_unread' => false]);

        return response()->json([
            'success' => true,
            'conversation' => [
                'id' => $conversation->id,
                'company' => $conversation->recruteur->nom_entreprise ?? $conversation->recruteur->name,
                'recruiter' => $conversation->recruteur->name,
                'recruiter_id' => $conversation->recruteur_id,
                'offre_id' => $conversation->offre_id,
                'offre_titre' => $conversation->offre->titre ?? null,
            ],
            'messages' => $conversation->messages->map(function($msg) use ($user) {
                return [
                    'id' => $msg->id,
                    'sender' => $msg->sender_id === $user->id ? 'me' : 'recruiter',
                    'content' => $msg->content,
                    'time' => $msg->created_at->format('H:i'),
                    'date' => $msg->created_at->format('Y-m-d'),
                    'is_read' => $msg->is_read,
                ];
            }),
        ]);
    }

    /**
     * Obtenir ou créer une conversation
     */
    public function getOrCreateConversation(Request $request, $etudiantId, $offreId = null)
    {
        $user = $request->user();
        
        if ($user->role !== 'recruteur' && $user->role !== 'admin') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        // Vérifier si une conversation existe déjà
        $query = Conversation::where('recruteur_id', $user->id)
            ->where('etudiant_id', $etudiantId);
            
        if ($offreId) {
            $query->where('offre_id', $offreId);
        } else {
            $query->whereNull('offre_id');
        }
        
        $conversation = $query->first();

        // Si pas de conversation, en créer une
        if (!$conversation) {
            // Trouver le matching si disponible
            $matching = null;
            if ($offreId) {
                $matching = Matching::where('offre_id', $offreId)
                    ->where('etudiant_id', $etudiantId)
                    ->first();
            }

            $conversation = Conversation::create([
                'recruteur_id' => $user->id,
                'etudiant_id' => $etudiantId,
                'offre_id' => $offreId,
                'matching_id' => $matching?->id,
            ]);
        }

        // Charger les relations et messages
        $conversation->load(['etudiant.profilEtudiant', 'offre', 'messages' => function($query) {
            $query->orderBy('created_at', 'asc');
        }, 'messages.sender']);

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'candidate' => $conversation->etudiant->name,
                'candidate_id' => $conversation->etudiant_id,
                'position' => $conversation->etudiant->profilEtudiant->titre_profil ?? 'Candidat',
                'offre_id' => $conversation->offre_id,
                'offre_titre' => $conversation->offre->titre ?? null,
            ],
            'messages' => $conversation->messages->map(function($msg) use ($user) {
                return [
                    'id' => $msg->id,
                    'sender' => $msg->sender_id === $user->id ? 'me' : 'candidate',
                    'content' => $msg->content,
                    'time' => $msg->created_at->format('H:i'),
                    'date' => $msg->created_at->format('Y-m-d'),
                    'is_read' => $msg->is_read,
                ];
            }),
        ]);
    }

    /**
     * Envoyer un message
     */
    public function sendMessage(Request $request, $conversationId)
    {
        $user = $request->user();
        
        $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $conversation = Conversation::with(['recruteur', 'etudiant'])->findOrFail($conversationId);

        // Vérifier les permissions
        if ($conversation->recruteur_id !== $user->id && $conversation->etudiant_id !== $user->id) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        DB::beginTransaction();
        try {
            $message = Message::create([
                'conversation_id' => $conversationId,
                'sender_id' => $user->id,
                'content' => $request->content,
            ]);

            // Mettre à jour la conversation
            $conversation->update([
                'last_message_at' => now(),
                'recruteur_has_unread' => $user->id !== $conversation->recruteur_id,
                'etudiant_has_unread' => $user->id !== $conversation->etudiant_id,
            ]);

            // Envoyer une notification au destinataire
            $notificationService = new NotificationService();
            $destinataireId = $user->id === $conversation->recruteur_id 
                ? $conversation->etudiant_id 
                : $conversation->recruteur_id;
            $expediteurNom = $user->prenom . ' ' . $user->nom;
            
            $notificationService->nouveauMessage($destinataireId, $expediteurNom, $conversationId);

            DB::commit();

            $message->load('sender');

            return response()->json([
                'message' => [
                    'id' => $message->id,
                    'sender' => $message->sender_id === $conversation->recruteur_id ? 'me' : 'candidate',
                    'content' => $message->content,
                    'time' => $message->created_at->format('H:i'),
                    'date' => $message->created_at->format('Y-m-d'),
                    'is_read' => false,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de l\'envoi du message'], 500);
        }
    }

    /**
     * Marquer les messages comme lus
     */
    public function markAsRead(Request $request, $conversationId)
    {
        $user = $request->user();
        
        $conversation = Conversation::findOrFail($conversationId);

        // Vérifier les permissions
        if ($conversation->recruteur_id !== $user->id && $conversation->etudiant_id !== $user->id) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        // Marquer les messages comme lus
        Message::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $user->id)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        // Mettre à jour le statut de la conversation
        if ($user->id === $conversation->recruteur_id) {
            $conversation->update(['recruteur_has_unread' => false]);
        } else {
            $conversation->update(['etudiant_has_unread' => false]);
        }

        return response()->json(['message' => 'Messages marqués comme lus']);
    }
}

