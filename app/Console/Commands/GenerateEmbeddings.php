<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmbeddingService;
use App\Models\ProfilEtudiant;
use App\Models\Offre;

class GenerateEmbeddings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'embeddings:generate 
                            {--type=all : Type (all, candidates, jobs)}
                            {--force : Force regeneration even if already exists}
                            {--limit=10 : Limit number of records to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate embeddings for candidates and job offers using AI service';

    protected $embeddingService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(EmbeddingService $embeddingService)
    {
        parent::__construct();
        $this->embeddingService = $embeddingService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $type = $this->option('type');
        $force = $this->option('force');
        $limit = (int) $this->option('limit');

        $this->info('🚀 Starting embedding generation...');
        $this->info('Type: ' . $type);
        $this->info('Force: ' . ($force ? 'yes' : 'no'));
        $this->info('Limit: ' . $limit);
        $this->newLine();

        if ($type === 'all' || $type === 'candidates') {
            $this->generateCandidateEmbeddings($limit, $force);
        }

        if ($type === 'all' || $type === 'jobs') {
            $this->generateJobEmbeddings($limit, $force);
        }

        $this->newLine();
        $this->info('✅ Embedding generation completed!');

        return 0;
    }

    /**
     * Generate embeddings for candidates
     */
    protected function generateCandidateEmbeddings($limit, $force)
    {
        $this->info('📝 Generating candidate embeddings...');

        $query = ProfilEtudiant::with(['utilisateur', 'profilCompetences.competence']);

        if (!$force) {
            // Ne traiter que ceux qui n'ont pas d'embedding
            $query->whereDoesntHave('candidateEmbedding');
        }

        $profils = $query->limit($limit)->get();

        $this->info('Found ' . $profils->count() . ' candidates to process');

        $bar = $this->output->createProgressBar($profils->count());
        $bar->start();

        $success = 0;
        $errors = 0;

        foreach ($profils as $profil) {
            try {
                $result = $this->embeddingService->generateCandidateEmbedding($profil->utilisateur_id);
                
                if ($result) {
                    $success++;
                    $this->newLine();
                    $this->info("✓ Candidate {$profil->utilisateur->fullname} (ID: {$profil->utilisateur_id})");
                    $this->line("  Profile text length: " . strlen($result['profile_text']) . " chars");
                    $this->line("  Embedding dimension: " . count($result['embedding']) . " vectors");
                } else {
                    $errors++;
                    $this->newLine();
                    $this->error("✗ Failed for candidate ID: {$profil->utilisateur_id}");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("✗ Error for candidate {$profil->utilisateur_id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Candidates: {$success} success, {$errors} errors");
    }

    /**
     * Generate embeddings for job offers
     */
    protected function generateJobEmbeddings($limit, $force)
    {
        $this->info('💼 Generating job offer embeddings...');

        $query = Offre::with(['recruteur', 'offreCompetences.competence'])
                     ->where('statut', 'PUBLIEE');

        if (!$force) {
            // Ne traiter que ceux qui n'ont pas d'embedding
            $query->whereDoesntHave('jobEmbedding');
        }

        $offres = $query->limit($limit)->get();

        $this->info('Found ' . $offres->count() . ' job offers to process');

        $bar = $this->output->createProgressBar($offres->count());
        $bar->start();

        $success = 0;
        $errors = 0;

        foreach ($offres as $offre) {
            try {
                $result = $this->embeddingService->generateJobOfferEmbedding($offre->id);
                
                if ($result) {
                    $success++;
                    $this->newLine();
                    $this->info("✓ Job offer: {$offre->titre} (ID: {$offre->id})");
                    $this->line("  Job text length: " . strlen($result['job_text']) . " chars");
                    $this->line("  Embedding dimension: " . count($result['embedding']) . " vectors");
                } else {
                    $errors++;
                    $this->newLine();
                    $this->error("✗ Failed for job offer ID: {$offre->id}");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("✗ Error for job offer {$offre->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Job offers: {$success} success, {$errors} errors");
    }
}
