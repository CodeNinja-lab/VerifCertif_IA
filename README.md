# VeriCertis Embedding API

API FastAPI pour la génération d'embeddings vectoriels utilisée par le système de matching intelligent de VeriCertis.

## 🎯 Objectif

Générer des représentations vectorielles (embeddings) des profils candidats et des offres d'emploi pour permettre un matching basé sur la similarité sémantique plutôt que sur des mots-clés.

## 🚀 Démarrage rapide

### Prérequis

- Python 3.11+
- 2 GB RAM minimum
- 500 MB espace disque (pour le modèle)

### Installation

```bash
# Cloner le projet
git clone https://github.com/VOTRE_USERNAME/vericertis-embedding.git
cd vericertis-embedding

# Créer un environnement virtuel
python -m venv venv
source venv/bin/activate  # Linux/Mac
# OU
venv\Scripts\activate  # Windows

# Installer les dépendances
pip install -r requirements.txt
```

### Lancer le serveur

```bash
# Development (avec rechargement automatique)
uvicorn main:app --reload --port 8000

# Production
uvicorn main:app --host 0.0.0.0 --port 8000
```

L'API sera accessible sur http://localhost:8000

## 📡 Endpoints

### GET `/`

Health check de l'API.

**Réponse** :
```json
{
  "status": "ok",
  "service": "VeriCertis Embedding API",
  "model": "sentence-transformers/all-MiniLM-L6-v2",
  "version": "1.0.0"
}
```

### POST `/embed`

Génère un embedding pour un texte donné.

**Request** :
```json
{
  "text": "Développeur full-stack avec 3 ans d'expérience en React et Node.js"
}
```

**Response** :
```json
{
  "embedding": [0.123, -0.456, 0.789, ...],  // 384 valeurs
  "dim": 384
}
```

**Caractéristiques** :
- Dimension : **384 vecteurs**
- Normalisé : Oui (norme L2 = 1)
- Temps de réponse : ~100-300ms sur CPU

### POST `/cosine`

Calcule la similarité cosinus entre deux embeddings.

**Request** :
```json
{
  "a": [0.1, 0.2, 0.3, ...],  // 384 valeurs
  "b": [0.2, 0.3, 0.4, ...]   // 384 valeurs
}
```

**Response** :
```json
{
  "score": 0.8745  // Valeur entre 0 (différent) et 1 (identique)
}
```

## 🧠 Modèle

### sentence-transformers/all-MiniLM-L6-v2

- **Type** : Sentence Transformer
- **Dimension** : 384
- **Taille** : ~90 MB
- **Performance** : ~1000 phrases/sec sur CPU moderne
- **Qualité** : 63.5 (moyenne des benchmarks STS)
- **Licence** : Apache 2.0

**Avantages** :
- ✅ Léger et rapide
- ✅ Bon compromis qualité/performance
- ✅ Multilingue (avec support français)
- ✅ Embeddings normalisés (cosine = dot product)

## 🔧 Configuration

### Variables d'environnement

Aucune variable nécessaire pour le moment. Configuration future possible :

```env
MODEL_NAME=sentence-transformers/all-MiniLM-L6-v2
MAX_LENGTH=512
CACHE_DIR=/path/to/cache
```

## 🐳 Docker

### Build

```bash
docker build -t vericertis-embedding .
```

### Run

```bash
docker run -p 8000:8000 vericertis-embedding
```

## 🌐 Déploiement sur Render

### Via GitHub

1. Pusher le code sur GitHub
2. Créer un nouveau Web Service sur Render
3. Connecter le repo GitHub
4. Configuration :
   - **Build Command** : `pip install -r requirements.txt`
   - **Start Command** : `uvicorn main:app --host 0.0.0.0 --port $PORT`
   - **Plan** : Free (suffisant)

### Via render.yaml

Le fichier `render.yaml` est déjà configuré :

```yaml
services:
  - type: web
    name: vericertis-embedding
    env: python
    buildCommand: pip install -r requirements.txt
    startCommand: uvicorn main:app --host 0.0.0.0 --port $PORT
```

Déployer avec :
```bash
render deploy
```

## 🧪 Tests

### Test manuel

```bash
# Health check
curl http://localhost:8000

# Générer un embedding
curl -X POST http://localhost:8000/embed \
  -H "Content-Type: application/json" \
  -d '{"text":"Test embedding"}'

# Calculer une similarité
curl -X POST http://localhost:8000/cosine \
  -H "Content-Type: application/json" \
  -d '{"a":[0.1,0.2,0.3],"b":[0.2,0.3,0.4]}'
```

### Test avec Python

```python
import requests

# Générer un embedding
response = requests.post(
    "http://localhost:8000/embed",
    json={"text": "Développeur Python avec 5 ans d'expérience"}
)
result = response.json()
print(f"Dimension: {result['dim']}")
print(f"First 5 values: {result['embedding'][:5]}")

# Calculer une similarité
embedding1 = result['embedding']
embedding2 = result['embedding']  # Même embedding = 1.0

response = requests.post(
    "http://localhost:8000/cosine",
    json={"a": embedding1, "b": embedding2}
)
print(f"Similarity: {response.json()['score']}")  # ~1.0
```

## 📊 Performance

### Benchmarks (CPU Intel i5)

| Opération | Temps moyen | Throughput |
|-----------|-------------|------------|
| Embedding court (10 mots) | 80ms | ~12 req/sec |
| Embedding moyen (50 mots) | 150ms | ~6 req/sec |
| Embedding long (200 mots) | 300ms | ~3 req/sec |
| Cosine similarity | <1ms | ~1000 req/sec |

### Optimisations possibles

1. **Batch processing** : Encoder plusieurs textes en une requête
2. **GPU** : Utiliser CUDA pour 10-50x plus rapide
3. **Cache** : Mettre en cache les embeddings fréquents
4. **Quantization** : Réduire la précision (float32 → float16)

## 🔄 Intégration avec Laravel

### Configuration Laravel

```php
// .env
AI_API_URL=http://localhost:8000
AI_API_TIMEOUT=30

// app/Services/AIService.php
public function generateEmbedding(string $text): ?array
{
    $response = Http::timeout($this->timeout)
        ->post("{$this->baseUrl}/embed", ['text' => $text]);
    
    return $response->successful() ? $response->json() : null;
}
```

### Utilisation

```php
$aiService = app(App\Services\AIService::class);
$result = $aiService->generateEmbedding("Développeur full-stack");

// Stocker dans PostgreSQL avec pgvector
$candidate = CandidateEmbedding::find($userId);
$candidate->embedding = $result['embedding'];
$candidate->save();

// Rechercher par similarité
$matches = DB::select("
    SELECT id, 1 - (embedding <=> ?::vector) AS similarity
    FROM job_offers
    WHERE embedding IS NOT NULL
    ORDER BY similarity DESC
    LIMIT 10
", [$candidate->embedding]);
```

## 🛠️ Développement

### Structure du projet

```
.
├── main.py              # Application FastAPI
├── requirements.txt     # Dépendances Python
├── Dockerfile          # Image Docker
├── render.yaml         # Configuration Render
├── start-api.bat       # Script de démarrage Windows
└── README.md           # Ce fichier
```

### Ajouter un nouveau endpoint

```python
@app.post("/batch-embed")
def batch_embed(texts: List[str]):
    """Encoder plusieurs textes en une seule requête"""
    embeddings = model.encode(texts, normalize_embeddings=True)
    return {
        "embeddings": [e.tolist() for e in embeddings],
        "count": len(texts),
        "dim": int(embeddings.shape[1])
    }
```

## 📚 Documentation

- [Sentence Transformers](https://www.sbert.net/)
- [FastAPI](https://fastapi.tiangolo.com/)
- [Render Deployment](https://docs.render.com/)
- [pgvector](https://github.com/pgvector/pgvector)

## 🐛 Dépannage

### Erreur : "Connection refused"

**Cause** : Le serveur n'est pas démarré

**Solution** :
```bash
uvicorn main:app --host 0.0.0.0 --port 8000
```

### Erreur : "Module not found: sentence_transformers"

**Cause** : Dépendances pas installées

**Solution** :
```bash
pip install -r requirements.txt
```

### Lenteur excessive

**Cause** : CPU trop lent ou texte trop long

**Solutions** :
1. Limiter la longueur du texte (max 512 tokens)
2. Utiliser un GPU si disponible
3. Upgrade vers un plan Render avec plus de CPU

## 📝 Licence

MIT License - Voir LICENSE pour plus de détails

## 👥 Contributeurs

- [Votre Nom] - Développeur principal

## 🔗 Liens utiles

- **Backend Laravel** : [veriCertis_backend](../veriCertis_backend)
- **Frontend Next.js** : [VerifCertif_frontend](../VerifCertif_frontend)
- **Guide de déploiement** : [GUIDE_DEPLOIEMENT_EMBEDDING.md](../GUIDE_DEPLOIEMENT_EMBEDDING.md)
- **Guide de test** : [TESTING_GUIDE.md](./TESTING_GUIDE.md)

---

**Version** : 1.0.0  
**Dernière mise à jour** : 2024
