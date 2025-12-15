<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Administration;
use App\Models\Document;
use App\Models\Offre;
use App\Models\Competence;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class RealDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer des compétences réelles
        $competences = [
            ['nom' => 'JavaScript', 'nom_normalise' => 'javascript', 'categorie' => 'technique', 'description' => 'Langage de programmation web'],
            ['nom' => 'Python', 'nom_normalise' => 'python', 'categorie' => 'technique', 'description' => 'Langage de programmation polyvalent'],
            ['nom' => 'PHP', 'nom_normalise' => 'php', 'categorie' => 'technique', 'description' => 'Langage de programmation serveur'],
            ['nom' => 'React', 'nom_normalise' => 'react', 'categorie' => 'framework', 'description' => 'Framework JavaScript pour interfaces'],
            ['nom' => 'Laravel', 'nom_normalise' => 'laravel', 'categorie' => 'framework', 'description' => 'Framework PHP moderne'],
            ['nom' => 'Node.js', 'nom_normalise' => 'nodejs', 'categorie' => 'framework', 'description' => 'Environnement d\'exécution JavaScript'],
            ['nom' => 'SQL', 'nom_normalise' => 'sql', 'categorie' => 'technique', 'description' => 'Langage de requête de bases de données'],
            ['nom' => 'MongoDB', 'nom_normalise' => 'mongodb', 'categorie' => 'logiciel', 'description' => 'Base de données NoSQL'],
            ['nom' => 'Git', 'nom_normalise' => 'git', 'categorie' => 'logiciel', 'description' => 'Système de contrôle de version'],
            ['nom' => 'Docker', 'nom_normalise' => 'docker', 'categorie' => 'logiciel', 'description' => 'Plateforme de conteneurisation'],
        ];

        foreach ($competences as $comp) {
            Competence::firstOrCreate(
                ['nom' => $comp['nom']],
                $comp
            );
        }

        // Créer des universités réelles
        $universites = [
            [
                'nom' => 'Université Cheikh Anta Diop (UCAD)',
                'type_administration' => 'universite',
                'pays' => 'Sénégal',
                'ville' => 'Dakar',
                'adresse' => 'Avenue Cheikh Anta Diop, BP 5005, Dakar-Fann',
                'email_contact' => 'contact@ucad.edu.sn',
                'telephone_contact' => '+221338246318',
                'statut' => 'verifie',
            ],
            [
                'nom' => 'Université Gaston Berger (UGB)',
                'type_administration' => 'universite',
                'pays' => 'Sénégal',
                'ville' => 'Saint-Louis',
                'adresse' => 'Route de Ngallèle, BP 234, Saint-Louis',
                'email_contact' => 'contact@ugb.edu.sn',
                'telephone_contact' => '+221339611313',
                'statut' => 'verifie',
            ],
            [
                'nom' => 'École Supérieure Polytechnique (ESP)',
                'type_administration' => 'ecole',
                'pays' => 'Sénégal',
                'ville' => 'Dakar',
                'adresse' => 'Corniche Ouest, BP 5085, Dakar-Fann',
                'email_contact' => 'contact@esp.sn',
                'telephone_contact' => '+221338257200',
                'statut' => 'verifie',
            ],
        ];

        foreach ($universites as $univ) {
            Administration::firstOrCreate(
                ['email_contact' => $univ['email_contact']],
                array_merge($univ, ['cle_publique_ed25519' => 'temp_will_be_replaced_by_observer'])
            );
        }

        // Créer des étudiants réels
        $etudiants = [
            [
                'prenom' => 'Amadou',
                'nom' => 'Diallo',
                'email' => 'amadou.diallo@etudiant.ucad.edu.sn',
                'telephone' => '+221771234567',
            ],
            [
                'prenom' => 'Fatou',
                'nom' => 'Seck',
                'email' => 'fatou.seck@etudiant.ucad.edu.sn',
                'telephone' => '+221772345678',
            ],
            [
                'prenom' => 'Moussa',
                'nom' => 'Ndiaye',
                'email' => 'moussa.ndiaye@etudiant.ugb.edu.sn',
                'telephone' => '+221773456789',
            ],
            [
                'prenom' => 'Aissatou',
                'nom' => 'Ba',
                'email' => 'aissatou.ba@etudiant.esp.sn',
                'telephone' => '+221774567890',
            ],
            [
                'prenom' => 'Ibrahima',
                'nom' => 'Sarr',
                'email' => 'ibrahima.sarr@etudiant.ucad.edu.sn',
                'telephone' => '+221775678901',
            ],
        ];

        foreach ($etudiants as $etud) {
            User::firstOrCreate(
                ['email' => $etud['email']],
                array_merge($etud, [
                    'mot_de_passe_hash' => Hash::make('password123'),
                    'role' => 'etudiant',
                    'langue' => 'fr',
                    'is_active' => true,
                ])
            );
        }

        // Créer des recruteurs réels
        $recruteurs = [
            [
                'prenom' => 'Jean',
                'nom' => 'Dupont',
                'nom_entreprise' => 'Sonatel',
                'email' => 'recrutement@sonatel.sn',
                'telephone' => '+221338393939',
            ],
            [
                'prenom' => 'Marie',
                'nom' => 'Martin',
                'nom_entreprise' => 'Orange Sénégal',
                'email' => 'rh@orange.sn',
                'telephone' => '+221338690000',
            ],
            [
                'prenom' => 'Cheikh',
                'nom' => 'Fall',
                'nom_entreprise' => 'Expresso Sénégal',
                'email' => 'carrieres@expresso.sn',
                'telephone' => '+221338500000',
            ],
        ];

        foreach ($recruteurs as $rec) {
            User::firstOrCreate(
                ['email' => $rec['email']],
                array_merge($rec, [
                    'mot_de_passe_hash' => Hash::make('password123'),
                    'role' => 'recruteur',
                    'langue' => 'fr',
                    'is_active' => true,
                ])
            );
        }

        // Créer des offres d'emploi réelles
        $sonatel = User::where('email', 'recrutement@sonatel.sn')->first();
        $orange = User::where('email', 'rh@orange.sn')->first();
        $expresso = User::where('email', 'carrieres@expresso.sn')->first();

        $offres = [
            [
                'recruteur_id' => $sonatel?->id,
                'titre' => 'Développeur Full Stack Senior',
                'description' => 'Nous recherchons un développeur Full Stack expérimenté pour rejoindre notre équipe de développement.',
                'entreprise' => 'Sonatel',
                'secteur_activite' => 'Télécommunications',
                'lieu' => 'Dakar, Sénégal',
                'type_contrat' => 'CDI',
                'salaire_min' => 800000,
                'salaire_max' => 1500000,
                'date_expiration' => Carbon::now()->addMonths(2)->toDateString(),
                'statut' => 'PUBLIEE',
                'niveau_etudes_requis' => 'bac+5',
                'annees_experience_min' => 3,
            ],
            [
                'recruteur_id' => $orange?->id,
                'titre' => 'Ingénieur DevOps',
                'description' => 'Rejoignez notre équipe infrastructure pour gérer et améliorer nos systèmes cloud.',
                'entreprise' => 'Orange Sénégal',
                'secteur_activite' => 'Télécommunications',
                'lieu' => 'Dakar, Sénégal',
                'type_contrat' => 'CDI',
                'salaire_min' => 1000000,
                'salaire_max' => 1800000,
                'date_expiration' => Carbon::now()->addMonths(1)->toDateString(),
                'statut' => 'PUBLIEE',
                'niveau_etudes_requis' => 'bac+3',
                'annees_experience_min' => 2,
            ],
            [
                'recruteur_id' => $expresso?->id,
                'titre' => 'Développeur Mobile React Native',
                'description' => 'Développement d\'applications mobiles innovantes pour nos clients.',
                'entreprise' => 'Expresso Sénégal',
                'secteur_activite' => 'Télécommunications',
                'lieu' => 'Dakar, Sénégal',
                'type_contrat' => 'CDD',
                'duree_contrat_mois' => 12,
                'salaire_min' => 600000,
                'salaire_max' => 1200000,
                'date_expiration' => Carbon::now()->addMonth()->toDateString(),
                'statut' => 'PUBLIEE',
                'niveau_etudes_requis' => 'bac+3',
                'annees_experience_min' => 1,
            ],
            [
                'recruteur_id' => $sonatel?->id,
                'titre' => 'Analyste de Données',
                'description' => 'Analyser les données clients pour améliorer nos services.',
                'entreprise' => 'Sonatel',
                'secteur_activite' => 'Télécommunications',
                'lieu' => 'Dakar, Sénégal',
                'type_contrat' => 'CDI',
                'salaire_min' => 700000,
                'salaire_max' => 1300000,
                'date_expiration' => Carbon::now()->addMonths(2)->toDateString(),
                'statut' => 'PUBLIEE',
                'niveau_etudes_requis' => 'bac+5',
                'annees_experience_min' => 2,
            ],
        ];

        foreach ($offres as $offre) {
            if ($offre['recruteur_id']) {
                Offre::firstOrCreate(
                    [
                        'titre' => $offre['titre'],
                        'recruteur_id' => $offre['recruteur_id']
                    ],
                    $offre
                );
            }
        }

        $this->command->info('Données réelles créées avec succès !');
        $this->command->info('Étudiants: ' . count($etudiants));
        $this->command->info('Recruteurs: ' . count($recruteurs));
        $this->command->info('Offres: ' . count($offres));
        $this->command->info('Compétences: ' . count($competences));
        $this->command->info('Universités: ' . count($universites));
    }
}
