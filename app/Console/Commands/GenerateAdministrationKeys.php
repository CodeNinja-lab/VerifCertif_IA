<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Administration;
use App\Services\SimpleKeyManagementService;

class GenerateAdministrationKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'keys:generate 
                            {administration_id? : ID de l\'administration (optionnel, génère pour toutes si omis)}
                            {--regenerate : Régénère les clés même si elles existent déjà}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Génère les paires de clés Ed25519 pour les administrations';

    protected $keyService;

    /**
     * Create a new command instance.
     */
    public function __construct(SimpleKeyManagementService $keyService)
    {
        parent::__construct();
        $this->keyService = $keyService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $administrationId = $this->argument('administration_id');
        $regenerate = $this->option('regenerate');

        if ($administrationId) {
            // Générer pour une administration spécifique
            $administration = Administration::find($administrationId);

            if (!$administration) {
                $this->error("Administration avec ID {$administrationId} introuvable.");
                return Command::FAILURE;
            }

            return $this->generateForAdministration($administration, $regenerate);
        } else {
            // Générer pour toutes les administrations
            return $this->generateForAllAdministrations($regenerate);
        }
    }

    /**
     * Génère les clés pour une administration spécifique
     */
    protected function generateForAdministration(Administration $administration, bool $regenerate): int
    {
        $this->info("Administration : {$administration->nom} (ID: {$administration->id})");

        // Vérifier si les clés existent déjà
        if ($this->keyService->hasKeyPair($administration) && !$regenerate) {
            $this->warn("Les clés existent déjà. Utilisez --regenerate pour forcer la régénération.");
            return Command::SUCCESS;
        }

        try {
            if ($regenerate && $this->keyService->hasKeyPair($administration)) {
                $this->warn("Régénération des clés...");
                $result = $this->keyService->regenerateKeyPair($administration);
            } else {
                $this->info("Génération d'une nouvelle paire de clés...");
                $result = $this->keyService->generateKeyPair($administration);
            }

            $this->info("✓ Clés générées avec succès !");
            $this->line("  - Clé publique : " . substr($result['public_key'], 0, 20) . "...");
            $this->line("  - Clé privée stockée : {$result['private_key_path']}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Erreur : " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Génère les clés pour toutes les administrations
     */
    protected function generateForAllAdministrations(bool $regenerate): int
    {
        $administrations = Administration::all();

        if ($administrations->isEmpty()) {
            $this->warn("Aucune administration trouvée dans la base de données.");
            return Command::SUCCESS;
        }

        $this->info("Génération des clés pour {$administrations->count()} administration(s)...");
        $this->line("");

        $successCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($administrations as $administration) {
            $this->line("→ {$administration->nom} (ID: {$administration->id})");

            // Vérifier si les clés existent déjà
            if ($this->keyService->hasKeyPair($administration) && !$regenerate) {
                $this->warn("  ⊗ Clés déjà existantes (ignoré)");
                $skippedCount++;
                continue;
            }

            try {
                if ($regenerate && $this->keyService->hasKeyPair($administration)) {
                    $result = $this->keyService->regenerateKeyPair($administration);
                    $this->info("  ✓ Clés régénérées");
                } else {
                    $result = $this->keyService->generateKeyPair($administration);
                    $this->info("  ✓ Clés générées");
                }
                $successCount++;

            } catch (\Exception $e) {
                $this->error("  ✗ Erreur : " . $e->getMessage());
                $errorCount++;
            }

            $this->line("");
        }

        // Résumé
        $this->line("===========================================");
        $this->info("Terminé !");
        $this->line("  - Succès : {$successCount}");
        if ($skippedCount > 0) {
            $this->line("  - Ignorées : {$skippedCount}");
        }
        if ($errorCount > 0) {
            $this->error("  - Erreurs : {$errorCount}");
        }

        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
