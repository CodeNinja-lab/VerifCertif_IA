<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmbeddingService;

class TestEmbeddingMatching extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'embeddings:test-matching 
                            {userId : The user ID to test matching for}
                            {--limit=10 : Number of matches to display}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test embedding-based matching for a specific user';

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
        $userId = $this->argument('userId');
        $limit = (int) $this->option('limit');

        $this->info("🔍 Testing embedding matching for user ID: {$userId}");
        $this->newLine();

        try {
            // Obtenir les offres compatibles
            $matches = $this->embeddingService->getCompatibleJobOffers($userId, $limit);

            if (empty($matches)) {
                $this->warn('No matches found. Make sure embeddings are generated.');
                return 1;
            }

            $this->info("Found {$matches->count()} compatible job offers:");
            $this->newLine();

            // Afficher les résultats
            $headers = ['Rank', 'Job ID', 'Title', 'Similarity Score', 'Match %'];
            $rows = [];

            foreach ($matches as $index => $match) {
                $rows[] = [
                    $index + 1,
                    $match['offre_id'],
                    substr($match['title'], 0, 50) . (strlen($match['title']) > 50 ? '...' : ''),
                    number_format($match['similarity_score'], 4),
                    number_format($match['similarity_score'] * 100, 2) . '%',
                ];
            }

            $this->table($headers, $rows);

            $this->newLine();
            $this->info('✅ Test completed successfully!');
            $this->info('Algorithm: Cosine Similarity with pgvector');
            $this->info('Model: sentence-transformers/all-MiniLM-L6-v2');

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }
}
