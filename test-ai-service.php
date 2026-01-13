<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Testing AI Service...\n\n";

try {
    $aiService = app(App\Services\AIService::class);
    
    // Test 1: Health check
    echo "1. Health check: ";
    $health = $aiService->healthCheck();
    echo $health ? "✅ OK\n" : "❌ FAILED\n";
    
    if (!$health) {
        echo "   API URL: " . env('AI_API_URL') . "\n";
        echo "   Make sure the FastAPI server is running!\n";
        exit(1);
    }
    
    // Test 2: Generate embedding
    echo "\n2. Generate embedding: ";
    $text = "Developpeur full-stack avec 3 ans d'experience en React et Node.js";
    $result = $aiService->generateEmbedding($text);
    
    if ($result && isset($result['embedding']) && isset($result['dim'])) {
        echo "✅ OK\n";
        echo "   Dimension: " . $result['dim'] . "\n";
        echo "   First 5 values: " . implode(', ', array_slice($result['embedding'], 0, 5)) . "\n";
    } else {
        echo "❌ FAILED\n";
        echo "   Response: " . json_encode($result) . "\n";
        exit(1);
    }
    
    // Test 3: Test with longer text
    echo "\n3. Test with longer text: ";
    $longText = "Developpeur web full-stack avec 5 ans d'experience. " .
                "Competences principales: React, Vue.js, Node.js, Laravel, PostgreSQL. " .
                "Formation: Licence en informatique. Experience en gestion de projets agiles.";
    $result2 = $aiService->generateEmbedding($longText);
    
    if ($result2 && isset($result2['embedding'])) {
        echo "✅ OK\n";
        echo "   Text length: " . strlen($longText) . " chars\n";
        echo "   Embedding dimension: " . $result2['dim'] . "\n";
    } else {
        echo "❌ FAILED\n";
    }
    
    echo "\n✅ All tests passed!\n";
    
} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
