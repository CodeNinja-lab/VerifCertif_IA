# Configuration des Embeddings IA

## Description
Ce fichier contient la configuration nécessaire pour intégrer le système d'embeddings IA basé sur PostgreSQL (pgvector) et FastAPI.

## Prérequis

### 1. PostgreSQL avec extension pgvector
```sql
-- Se connecter à PostgreSQL
psql -U postgres

-- Créer la base de données
CREATE DATABASE verifcertis_embeddings;

-- Se connecter à la base
\c verifcertis_embeddings

-- Installer l'extension pgvector
CREATE EXTENSION vector;

-- Créer les tables
CREATE TABLE candidates (
    id SERIAL PRIMARY KEY,
    fullname TEXT,
    profile_text TEXT,
    embedding VECTOR(384)
);

CREATE TABLE job_offers (
    id SERIAL PRIMARY KEY,
    title TEXT,
    description TEXT,
    embedding VECTOR(384)
);

-- Créer les index HNSW pour des recherches rapides
CREATE INDEX candidates_embedding_hnsw
ON candidates
USING hnsw (embedding vector_cosine_ops);

CREATE INDEX job_offers_embedding_hnsw
ON job_offers
USING hnsw (embedding vector_cosine_ops);
```

### 2. API IA (FastAPI)
L'API IA doit être démarrée avant d'utiliser le système de matching.

```bash
cd ../VerifCertif_IA
python -m venv venv
source venv/bin/activate  # Windows: venv\Scripts\activate
pip install fastapi uvicorn sentence-transformers
uvicorn main:app --reload
```

L'API sera disponible sur http://localhost:8000

## Configuration .env

Ajoutez ces lignes dans votre fichier `.env` :

```env
# API IA
AI_API_URL=http://localhost:8000
AI_API_TIMEOUT=30

# PostgreSQL pour embeddings
PGSQL_HOST=127.0.0.1
PGSQL_PORT=5432
PGSQL_DATABASE=verifcertis_embeddings
PGSQL_USERNAME=postgres
PGSQL_PASSWORD=votre_mot_de_passe
```

## Fonctionnement

### Déclencheurs automatiques (Observers)

Le système génère/régénère automatiquement les embeddings dans ces situations :

**Pour les CANDIDATS :**
- ✅ Ajout/modification/suppression de compétences
- ✅ Ajout/modification/suppression de certifications

**Pour les OFFRES :**
- ✅ Publication d'une offre
- ✅ Modification d'une offre publiée
- ✅ Ajout/modification/suppression de compétences requises
- ✅ Suppression d'une offre

### Règle des 75% pour les candidats

- **Avec diplôme(s) certifié(s)** : Le profil est composé à 75%+ de compétences certifiées (validées blockchain)
- **Sans diplôme certifié** : 100% compétences déclarées + badge "Profil non certifié"

### API Endpoints

**Récupérer les offres compatibles :**
```http
GET /api/v1/matchings?limit=20&min_score=70
Authorization: Bearer {token}
```

**Paramètres :**
- `limit` (optionnel, défaut: 20) : Nombre d'offres à retourner
- `min_score` (optionnel, défaut: 0) : Score minimum de similarité (0-100)

**Réponse :**
```json
{
  "data": [
    {
      "offre": { ... },
      "ai_score": 94.5,
      "title": "Développeur Full Stack"
    }
  ],
  "meta": {
    "total": 10,
    "algorithm": "AI Embeddings (Cosine Similarity)",
    "model": "all-MiniLM-L6-v2"
  }
}
```

## Commandes utiles

### Générer l'embedding d'un candidat manuellement
```php
use App\Services\EmbeddingService;

$embeddingService = app(EmbeddingService::class);
$embeddingService->generateCandidateEmbedding($userId);
```

### Générer l'embedding d'une offre manuellement
```php
use App\Services\EmbeddingService;

$embeddingService = app(EmbeddingService::class);
$embeddingService->generateJobOfferEmbedding($offreId);
```

### Vérifier la santé de l'API IA
```php
use App\Services\AIService;

$aiService = app(AIService::class);
$isHealthy = $aiService->healthCheck(); // true/false
```

## Troubleshooting

### Erreur : "could not connect to server"
- Vérifiez que PostgreSQL est démarré
- Vérifiez les paramètres de connexion dans `.env`

### Erreur : "extension vector does not exist"
- Installez l'extension pgvector : `CREATE EXTENSION vector;`

### Erreur : "Connection refused" (API IA)
- Vérifiez que FastAPI est démarrée : `cd VerifCertif_IA && uvicorn main:app --reload`
- Vérifiez l'URL dans `.env` : `AI_API_URL=http://localhost:8000`

### Les embeddings ne sont pas générés
- Vérifiez les logs Laravel : `tail -f storage/logs/laravel.log`
- Vérifiez que les observers sont bien enregistrés dans `AppServiceProvider`
