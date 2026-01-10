<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Trouver un recruteur pour les tests
        $recruteur = User::where('role', 'recruteur')->first();
        
        if (!$recruteur) {
            $this->command->warn('Aucun recruteur trouvé. Créez un recruteur d\'abord.');
            return;
        }

        $this->command->info("Création de notifications pour le recruteur ID: {$recruteur->id}");

        // Notification 1 - Nouvelle candidature (récente)
        Notification::create([
            'destinataire_id' => $recruteur->id,
            'type' => 'matching', // Type matching pour nouvelle candidature
            'titre' => 'Nouvelle candidature',
            'message' => 'Jean Dupont a postulé pour votre offre "Développeur Full Stack". Consultez son profil pour plus de détails.',
            'priorite' => 'haute',
            'lue' => false,
            'date_envoi' => Carbon::now()->subMinutes(10),
            'lien_action' => '/recruiter/matching',
        ]);

        // Notification 2 - Message non lu (très récente)
        Notification::create([
            'destinataire_id' => $recruteur->id,
            'type' => 'message',
            'titre' => 'Nouveau message',
            'message' => 'Marie Martin vous a envoyé un message concernant le poste de Designer UI/UX.',
            'priorite' => 'normale',
            'lue' => false,
            'date_envoi' => Carbon::now()->subMinutes(5),
            'lien_action' => '/recruiter/messages',
        ]);

        // Notification 3 - Offre expirée (récente)
        Notification::create([
            'destinataire_id' => $recruteur->id,
            'type' => 'nouvelle_offre',
            'titre' => 'Offre expirée',
            'message' => 'Votre offre "Chef de projet digital" expire le ' . Carbon::now()->addDays(2)->format('d/m/Y') . '. Pensez à la renouveler.',
            'priorite' => 'urgente',
            'lue' => false,
            'date_envoi' => Carbon::now()->subHours(2),
            'lien_action' => '/recruiter/jobs',
        ]);

        // Notification 4 - Matching IA (lue, un peu plus ancienne)
        Notification::create([
            'destinataire_id' => $recruteur->id,
            'type' => 'matching',
            'titre' => 'Nouveaux matchs disponibles',
            'message' => 'Notre IA a trouvé 5 candidats qui correspondent parfaitement à votre offre "Data Scientist".',
            'priorite' => 'normale',
            'lue' => true,
            'date_envoi' => Carbon::now()->subHours(5),
            'date_lecture' => Carbon::now()->subHours(4),
            'lien_action' => '/recruiter/matching',
        ]);

        // Notification 5 - Candidature (lue, hier)
        Notification::create([
            'destinataire_id' => $recruteur->id,
            'type' => 'matching',
            'titre' => 'Candidature mise à jour',
            'message' => 'Le candidat Pierre Durand a mis à jour son CV et son profil. Consultez les modifications.',
            'priorite' => 'normale',
            'lue' => true,
            'date_envoi' => Carbon::now()->subDay(),
            'date_lecture' => Carbon::now()->subHours(20),
            'lien_action' => '/recruiter/matching',
        ]);

        // Notification 6 - Message (lue, il y a 2 jours)
        Notification::create([
            'destinataire_id' => $recruteur->id,
            'type' => 'message',
            'titre' => 'Réponse à votre message',
            'message' => 'Sophie Bernard a répondu à votre proposition d\'entretien.',
            'priorite' => 'normale',
            'lue' => true,
            'date_envoi' => Carbon::now()->subDays(2),
            'date_lecture' => Carbon::now()->subDays(2)->addHours(1),
            'lien_action' => '/recruiter/messages',
        ]);

        $this->command->info('✅ 6 notifications créées avec succès !');
    }
}
