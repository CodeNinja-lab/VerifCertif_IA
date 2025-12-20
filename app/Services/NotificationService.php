<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Créer une notification générique
     */
    public function create(int $destinataireId, string $type, string $titre, string $message, array $options = []): Notification
    {
        return Notification::create([
            'destinataire_id' => $destinataireId,
            'type' => $type,
            'titre' => $titre,
            'message' => $message,
            'lien_action' => $options['lien_action'] ?? null,
            'icone' => $options['icone'] ?? null,
            'priorite' => $options['priorite'] ?? 'normale',
            'lue' => false,
            'archivee' => false,
            'date_expiration' => $options['date_expiration'] ?? null,
            'metadata' => $options['metadata'] ?? null,
        ]);
    }

    /**
     * Notification quand un recruteur consulte une candidature
     */
    public function candidatureConsultee(int $etudiantId, string $recruteurNom, string $offreTitre, ?int $candidatureId = null): Notification
    {
        return $this->create(
            $etudiantId,
            'candidature_consultee',
            'Candidature consultée',
            "Le recruteur {$recruteurNom} a consulté votre candidature pour le poste \"{$offreTitre}\".",
            [
                'lien_action' => $candidatureId ? "/candidate/applications/{$candidatureId}" : '/candidate/applications',
                'icone' => 'user-check',
                'priorite' => 'normale',
            ]
        );
    }

    /**
     * Notification quand un certificat est disponible
     */
    public function certificatDisponible(int $etudiantId, string $diplomeTitre, ?int $diplomeId = null): Notification
    {
        return $this->create(
            $etudiantId,
            'certificat_disponible',
            'Certificat disponible',
            "Votre certificat pour \"{$diplomeTitre}\" est maintenant disponible. Vous pouvez le télécharger.",
            [
                'lien_action' => $diplomeId ? "/candidate/degrees/{$diplomeId}" : '/candidate/degrees',
                'icone' => 'award',
                'priorite' => 'haute',
            ]
        );
    }

    /**
     * Notification quand un nouveau message est reçu
     */
    public function nouveauMessage(int $destinataireId, string $expediteurNom, ?int $conversationId = null): Notification
    {
        return $this->create(
            $destinataireId,
            'nouveau_message',
            'Nouveau message',
            "Vous avez reçu un nouveau message de {$expediteurNom}.",
            [
                'lien_action' => $conversationId ? "/candidate/messages?conversation={$conversationId}" : '/candidate/messages',
                'icone' => 'message-square',
                'priorite' => 'normale',
            ]
        );
    }

    /**
     * Notification quand le matching dépasse 90%
     */
    public function matchingEleve(int $etudiantId, string $offreTitre, string $entrepriseNom, int $score, ?int $offreId = null): Notification
    {
        return $this->create(
            $etudiantId,
            'matching_eleve',
            'Excellent matching trouvé !',
            "Votre profil correspond à {$score}% avec l'offre \"{$offreTitre}\" de {$entrepriseNom}.",
            [
                'lien_action' => $offreId ? "/jobs/{$offreId}" : '/candidate/matching',
                'icone' => 'sparkles',
                'priorite' => 'haute',
            ]
        );
    }

    /**
     * Notification quand une candidature change de statut
     */
    public function candidatureStatutChange(int $etudiantId, string $nouveauStatut, string $offreTitre, ?int $candidatureId = null): Notification
    {
        $messages = [
            'en_attente' => "Votre candidature pour \"{$offreTitre}\" est en attente d'examen.",
            'en_cours' => "Votre candidature pour \"{$offreTitre}\" est en cours d'examen.",
            'acceptee' => "Félicitations ! Votre candidature pour \"{$offreTitre}\" a été acceptée.",
            'refusee' => "Votre candidature pour \"{$offreTitre}\" n'a pas été retenue.",
            'entretien' => "Vous êtes invité à un entretien pour le poste \"{$offreTitre}\".",
        ];

        $titres = [
            'en_attente' => 'Candidature enregistrée',
            'en_cours' => 'Candidature en cours d\'examen',
            'acceptee' => 'Candidature acceptée !',
            'refusee' => 'Candidature non retenue',
            'entretien' => 'Invitation à un entretien',
        ];

        $priorites = [
            'en_attente' => 'normale',
            'en_cours' => 'normale',
            'acceptee' => 'haute',
            'refusee' => 'normale',
            'entretien' => 'haute',
        ];

        $message = $messages[$nouveauStatut] ?? "Le statut de votre candidature pour \"{$offreTitre}\" a changé.";
        $titre = $titres[$nouveauStatut] ?? 'Mise à jour de candidature';
        $priorite = $priorites[$nouveauStatut] ?? 'normale';

        return $this->create(
            $etudiantId,
            'candidature_statut',
            $titre,
            $message,
            [
                'lien_action' => $candidatureId ? "/candidate/applications/{$candidatureId}" : '/candidate/applications',
                'icone' => 'briefcase',
                'priorite' => $priorite,
                'metadata' => ['statut' => $nouveauStatut],
            ]
        );
    }

    /**
     * Compter les notifications non lues pour un utilisateur
     */
    public function countUnread(int $userId): int
    {
        return Notification::where('destinataire_id', $userId)
            ->where('lue', false)
            ->where('archivee', false)
            ->count();
    }
}
