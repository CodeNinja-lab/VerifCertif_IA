<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncExpatDakarOffers extends Command
{
    protected $signature = 'offers:sync-expat-dakar
                            {--pages=1 : Number of Expat Dakar pages to scrape}
                            {--service-url= : Override the Python sync service URL}
                            {--token= : Optional bearer token for the Python sync service}';

    protected $description = 'Triggers the Python Expat Dakar sync service so it scrapes and pushes offers to Laravel';

    public function handle(): int
    {
        $serviceUrl = rtrim((string) ($this->option('service-url') ?: env('OFFERS_SYNC_SERVICE_URL', '')), '/');
        if ($serviceUrl === '') {
            $this->error('OFFERS_SYNC_SERVICE_URL is not configured.');
            return Command::FAILURE;
        }

        $pages = max(1, (int) $this->option('pages'));
        $token = (string) ($this->option('token') ?: env('OFFERS_SYNC_SERVICE_TOKEN', ''));

        $request = Http::acceptJson()->timeout((int) env('OFFERS_SYNC_TIMEOUT', 120));

        if ($token !== '') {
            $request = $request->withToken($token);
        }

        $response = $request->post($serviceUrl . '/sync/run?pages=' . $pages);

        if (!$response->successful()) {
            $this->error('Sync failed with status ' . $response->status());
            $this->line($response->body());
            return Command::FAILURE;
        }

        $payload = $response->json();
        $this->info('Weekly sync triggered successfully.');
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return Command::SUCCESS;
    }
}