<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BlockchainService
{
    protected $network;
    protected $contractAddress;
    protected $apiKey;

    public function __construct()
    {
        $this->network = config('blockchain.network', 'ethereum');
        $this->contractAddress = config('blockchain.contract_address');
        $this->apiKey = config('blockchain.api_key');
    }

    /**
     * Ancrer un document sur la blockchain
     */
    public function anchorDocument(Document $document): ?string
    {
        try {
            // Préparer les données à ancrer
            $data = [
                'uuid' => $document->uuid_document,
                'hash' => $document->hash_sha256,
                'signature' => $document->signature_ed25519,
                'administration_id' => $document->administration_id,
                'date_emission' => $document->date_emission->toIso8601String(),
                'timestamp' => Carbon::now()->timestamp,
            ];

            // Générer le hash des données
            $dataHash = hash('sha256', json_encode($data));

            // Appeler l'API blockchain (exemple avec Etherscan pour Ethereum)
            $txHash = $this->submitToBlockchain($dataHash, $document->uuid_document);

            if ($txHash) {
                // Mettre à jour le document avec le hash de transaction
                $document->update([
                    'blockchain_tx_hash' => $txHash,
                    'blockchain_network' => $this->network,
                ]);

                Log::info("Document ancré sur blockchain", [
                    'document_id' => $document->id,
                    'uuid' => $document->uuid_document,
                    'tx_hash' => $txHash,
                    'network' => $this->network,
                ]);

                return $txHash;
            }

            return null;

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'ancrage blockchain", [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Vérifier l'ancrage sur la blockchain
     */
    public function verifyAnchor(Document $document): bool
    {
        if (!$document->blockchain_tx_hash) {
            return false;
        }

        try {
            // Vérifier la transaction sur la blockchain
            return $this->checkTransaction($document->blockchain_tx_hash);

        } catch (\Exception $e) {
            Log::error("Erreur lors de la vérification blockchain", [
                'document_id' => $document->id,
                'tx_hash' => $document->blockchain_tx_hash,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Soumettre à la blockchain (exemple pour Ethereum via API)
     */
    protected function submitToBlockchain(string $dataHash, string $uuid): ?string
    {
        // Si pas de configuration blockchain, on simule pour le développement
        if (!$this->contractAddress || !$this->apiKey) {
            // En développement, on retourne un hash simulé
            if (app()->environment('local', 'testing')) {
                return '0x' . bin2hex(random_bytes(32)); // Hash simulé
            }
            return null;
        }

        try {
            // Exemple d'intégration avec une API blockchain
            // Pour Ethereum, vous pourriez utiliser Etherscan, Infura, ou Alchemy
            // Pour Polygon, utiliser PolygonScan API
            // Pour d'autres réseaux, adapter l'API

            $response = Http::timeout(30)->post(config('blockchain.api_url'), [
                'method' => 'storeHash',
                'params' => [
                    'hash' => $dataHash,
                    'metadata' => [
                        'uuid' => $uuid,
                        'type' => 'document_certification',
                    ],
                ],
                'apiKey' => $this->apiKey,
            ]);

            if ($response->successful() && $response->json('success')) {
                return $response->json('txHash');
            }

            return null;

        } catch (\Exception $e) {
            Log::error("Erreur lors de la soumission blockchain", [
                'data_hash' => $dataHash,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Vérifier une transaction sur la blockchain
     */
    protected function checkTransaction(string $txHash): bool
    {
        if (app()->environment('local', 'testing')) {
            // En développement, on accepte les hash simulés
            return strlen($txHash) === 66 && str_starts_with($txHash, '0x');
        }

        try {
            // Vérifier la transaction via l'API blockchain
            $response = Http::timeout(30)->get(config('blockchain.api_url') . '/transaction/' . $txHash, [
                'apiKey' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $tx = $response->json();
                return isset($tx['status']) && $tx['status'] === 'confirmed';
            }

            return false;

        } catch (\Exception $e) {
            Log::error("Erreur lors de la vérification transaction", [
                'tx_hash' => $txHash,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}

