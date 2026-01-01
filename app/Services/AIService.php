<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected $baseUrl;
    protected $timeout;

    public function __construct()
    {
        $this->baseUrl = env('AI_API_URL', 'http://localhost:8000');
        $this->timeout = env('AI_API_TIMEOUT', 30);
    }

    /**
     * Générer un embedding pour un texte donné
     *
     * @param string $text
     * @return array|null [embedding: array, dim: int]
     */
    public function generateEmbedding(string $text): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/embed", [
                    'text' => $text,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('AI API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('AI API exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Vérifier la santé de l'API IA
     *
     * @return bool
     */
    public function healthCheck(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
