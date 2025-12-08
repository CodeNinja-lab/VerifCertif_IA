<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    protected $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', env('GEMINI_API_KEY'));
    }

    /**
     * Générer du contenu avec Gemini
     */
    public function generateContent(string $prompt, array $options = []): ?string
    {
        if (!$this->apiKey) {
            Log::warning('Gemini API key not configured');
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '?key=' . $this->apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => $options['temperature'] ?? 0.7,
                    'topK' => $options['topK'] ?? 40,
                    'topP' => $options['topP'] ?? 0.95,
                    'maxOutputTokens' => $options['maxOutputTokens'] ?? 2048,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    return $data['candidates'][0]['content']['parts'][0]['text'];
                }
            }

            Log::error('Gemini API error', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Gemini API exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Générer une description d'offre d'emploi
     */
    public function generateJobDescription(array $jobData): ?string
    {
        $prompt = "Génère une description d'offre d'emploi professionnelle et engageante en français pour le poste suivant:\n\n";
        $prompt .= "Titre: {$jobData['titre']}\n";
        $prompt .= "Entreprise: {$jobData['entreprise']}\n";
        $prompt .= "Localisation: {$jobData['lieu']}\n";
        $prompt .= "Type de contrat: {$jobData['type_contrat']}\n";
        
        if (isset($jobData['secteur_activite'])) {
            $prompt .= "Secteur: {$jobData['secteur_activite']}\n";
        }
        
        $prompt .= "\nGénère une description qui:\n";
        $prompt .= "- Présente l'entreprise de manière attractive\n";
        $prompt .= "- Décrit le contexte et les enjeux du poste\n";
        $prompt .= "- Est adaptée au marché sénégalais\n";
        $prompt .= "- Utilise un ton professionnel mais accessible\n";
        $prompt .= "- Fait entre 150 et 250 mots\n";

        return $this->generateContent($prompt);
    }

    /**
     * Générer les missions principales
     */
    public function generateResponsibilities(string $jobTitle, string $description): ?array
    {
        $prompt = "Génère une liste de 5 à 7 missions principales pour le poste suivant:\n\n";
        $prompt .= "Titre: {$jobTitle}\n";
        $prompt .= "Description: {$description}\n\n";
        $prompt .= "Retourne uniquement une liste à puces, une mission par ligne, format:\n";
        $prompt .= "- Mission 1\n";
        $prompt .= "- Mission 2\n";
        $prompt .= "etc.\n";
        $prompt .= "Sois précis et actionnable. Adapte au contexte sénégalais.";

        $result = $this->generateContent($prompt);
        
        if ($result) {
            $lines = explode("\n", $result);
            return array_filter(array_map(function($line) {
                $line = trim($line);
                // Enlever les puces
                $line = preg_replace('/^[-•*]\s*/', '', $line);
                return $line ?: null;
            }, $lines));
        }

        return null;
    }

    /**
     * Générer le profil recherché
     */
    public function generateRequirements(string $jobTitle, string $description): ?array
    {
        $prompt = "Génère une liste de 6 à 8 compétences et qualifications requises pour le poste suivant:\n\n";
        $prompt .= "Titre: {$jobTitle}\n";
        $prompt .= "Description: {$description}\n\n";
        $prompt .= "Retourne uniquement une liste à puces, une compétence par ligne, format:\n";
        $prompt .= "- Compétence 1\n";
        $prompt .= "- Compétence 2\n";
        $prompt .= "etc.\n";
        $prompt .= "Sois spécifique et réaliste pour le marché sénégalais.";

        $result = $this->generateContent($prompt);
        
        if ($result) {
            $lines = explode("\n", $result);
            return array_filter(array_map(function($line) {
                $line = trim($line);
                $line = preg_replace('/^[-•*]\s*/', '', $line);
                return $line ?: null;
            }, $lines));
        }

        return null;
    }
}

