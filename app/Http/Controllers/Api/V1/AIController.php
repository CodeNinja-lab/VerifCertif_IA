<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AIController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Générer du contenu pour une offre d'emploi
     */
    public function generateJobContent(Request $request)
    {
        $request->validate([
            'type' => 'required|in:description,missions,requirements',
            'jobData' => 'required|array',
            'jobData.titre' => 'required|string',
            'jobData.entreprise' => 'required|string',
            'jobData.lieu' => 'required|string',
        ]);

        $type = $request->type;
        $jobData = $request->jobData;

        try {
            if ($type === 'description') {
                $content = $this->geminiService->generateJobDescription($jobData);
            } elseif ($type === 'missions') {
                $responsibilities = $this->geminiService->generateResponsibilities(
                    $jobData['titre'],
                    $jobData['description'] ?? ''
                );
                $content = $responsibilities ? implode("\n", $responsibilities) : null;
            } elseif ($type === 'requirements') {
                $requirements = $this->geminiService->generateRequirements(
                    $jobData['titre'],
                    $jobData['description'] ?? ''
                );
                $content = $requirements ? implode("\n", $requirements) : null;
            } else {
                return response()->json(['message' => 'Type non supporté'], 400);
            }

            if (!$content) {
                return response()->json([
                    'message' => 'Impossible de générer le contenu. Vérifiez votre clé API Gemini.',
                ], 500);
            }

            return response()->json([
                'content' => $content,
                'type' => $type,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération IA', [
                'error' => $e->getMessage(),
                'type' => $type,
            ]);

            return response()->json([
                'message' => 'Erreur lors de la génération du contenu',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

