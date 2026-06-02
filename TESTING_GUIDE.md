# Guide de test et déploiement de l'API Embedding

## 🚀 Démarrer l'API FastAPI localement

### 1. Prérequis
```bash
cd C:\Mes_Dossiers\Memoire\VerifCertif_IA
python -m venv venv
venv\Scripts\activate
pip install -r requirements.txt
```

### 2. Démarrer le serveur
```bash
# Development avec rechargement automatique
uvicorn main:app --reload --port 8000

# Production
uvicorn main:app --host 0.0.0.0 --port 8000
```

### 3. Tester l'API

#### Test via navigateur
```
http://localhost:8000
http://localhost:8000/docs (Documentation Swagger)
```

#### Test via PowerShell
```powershell
# Test du endpoint racine
Invoke-RestMethod -Uri "http://localhost:8000" -Method Get

# Test de génération d'embedding
$body = @{
    text = "Développeur full-stack avec 3 ans d'expérience en React et Node.js"
} | ConvertTo-Json

$response = Invoke-RestMethod -Uri "http://localhost:8000/embed" -Method Post -Body $body -ContentType "application/json"
$response

# Test de similarité cosinus
$body = @{
    embedding_a = @(1..384 | ForEach-Object { Get-Random -Minimum 0.0 -Maximum 1.0 })
    embedding_b = @(1..384 | ForEach-Object { Get-Random -Minimum 0.0 -Maximum 1.0 })
} | ConvertTo-Json

$response = Invoke-RestMethod -Uri "http://localhost:8000/cosine" -Method Post -Body $body -ContentType "application/json"
$response
```

#### Test via CURL (Git Bash ou WSL)
```bash
# Test du endpoint racine
curl http://localhost:8000

# Test de génération d'embedding
curl -X POST http://localhost:8000/embed \
  -H "Content-Type: application/json" \
  -d '{"text": "Développeur full-stack avec 3 ans d'\''expérience en React et Node.js"}'

# Test de similarité cosinus
curl -X POST http://localhost:8000/cosine \
  -H "Content-Type: application/json" \
  -d '{"embedding_a": [0.1, 0.2, 0.3], "embedding_b": [0.2, 0.3, 0.4]}'
```

---

## 🔧 Configuration Laravel

### 1. Ajouter dans `.env`
```env
# API IA Embedding
AI_API_URL=http://localhost:8000
AI_API_TIMEOUT=30
```

### 2. Pour la production (Render)
```env
AI_API_URL=https://vericertis-embedding.onrender.com
AI_API_TIMEOUT=60
```

---

## 🧪 Tester avec les commandes Artisan

### 1. Health check de l'API IA
```bash
php artisan tinker
```
```php
$aiService = app(App\Services\AIService::class);
$health = $aiService->healthCheck();
dd($health); // Devrait retourner true
```

### 2. Test de génération d'embedding
```bash
php artisan tinker
```
```php
$aiService = app(App\Services\AIService::class);
$result = $aiService->generateEmbedding("Développeur full-stack avec 3 ans d'expérience en React et Node.js");
dd($result);
// Devrait retourner: ['embedding' => [384 floats], 'dim' => 384]
```

### 3. Générer les embeddings pour les candidats
```bash
# Générer pour tous les candidats (limité à 10 par défaut)
php artisan embeddings:generate --type=candidates --limit=10

# Forcer la régénération
php artisan embeddings:generate --type=candidates --force

# Générer pour toutes les offres
php artisan embeddings:generate --type=jobs --limit=10

# Générer pour tout (candidats + offres)
php artisan embeddings:generate --type=all
```

### 4. Tester le matching AI
```bash
# Remplacer USER_ID par un ID d'utilisateur existant
php artisan embeddings:test-matching USER_ID --limit=10
```

---

## 📊 Vérifier les données dans la base

### Vérifier les embeddings candidats
```sql
SELECT id, fullname, 
       CASE WHEN embedding IS NULL THEN 'NO' ELSE 'YES' END as has_embedding,
       LENGTH(embedding::text) as embedding_length
FROM candidates
LIMIT 10;
```

### Vérifier les embeddings offres
```sql
SELECT id, title, 
       CASE WHEN embedding IS NULL THEN 'NO' ELSE 'YES' END as has_embedding,
       LENGTH(embedding::text) as embedding_length
FROM job_offers
LIMIT 10;
```

### Tester la similarité cosinus avec pgvector
```sql
-- Trouver les offres similaires pour le candidat ID 1
SELECT 
    j.id,
    j.title,
    1 - (c.embedding <=> j.embedding) AS similarity
FROM candidates c
CROSS JOIN job_offers j
WHERE c.id = 1
  AND c.embedding IS NOT NULL
  AND j.embedding IS NOT NULL
ORDER BY similarity DESC
LIMIT 10;
```

---

## 🚀 Déploiement sur Render

### 1. Préparer le dépôt
```bash
cd C:\Mes_Dossiers\Memoire\VerifCertif_IA
git init
git add .
git commit -m "Initial commit - FastAPI embedding service"

# Créer un repo sur GitHub et pusher
git remote add origin https://github.com/VOTRE_USERNAME/vericertis-embedding.git
git branch -M main
git push -u origin main
```

### 2. Sur Render.com

1. **New Web Service**
2. **Connect GitHub repository** : `vericertis-embedding`
3. **Settings** :
   - Name: `vericertis-embedding`
   - Region: `Frankfurt`
   - Branch: `main`
   - Root Directory: (vide)
   - Runtime: `Python 3`
   - Build Command: `pip install -r requirements.txt`
   - Start Command: `uvicorn main:app --host 0.0.0.0 --port $PORT`
4. **Environment Variables** : (aucune nécessaire pour le moment)
5. **Plan** : `Free`
6. Cliquer sur **Create Web Service**

### 3. Mettre à jour le .env Laravel en production
```env
AI_API_URL=https://vericertis-embedding.onrender.com
AI_API_TIMEOUT=60
```

### 4. Test de l'API déployée
```powershell
Invoke-RestMethod -Uri "https://vericertis-embedding.onrender.com" -Method Get
```

---

## 🔍 Workflow complet de test

### 1. Démarrer l'API FastAPI
```bash
cd C:\Mes_Dossiers\Memoire\VerifCertif_IA
venv\Scripts\activate
uvicorn main:app --reload --port 8000
```

### 2. Vérifier la connexion depuis Laravel
```bash
php artisan tinker
```
```php
$aiService = app(App\Services\AIService::class);
$aiService->healthCheck() // true
```

### 3. Générer les embeddings
```bash
# Candidats
php artisan embeddings:generate --type=candidates --limit=5

# Offres
php artisan embeddings:generate --type=jobs --limit=5
```

### 4. Tester le matching
```bash
# Obtenir un ID d'utilisateur depuis la base
php artisan tinker
```
```php
$user = App\Models\User::where('role', 'etudiant')->first();
echo $user->id;
exit
```
```bash
# Tester le matching avec cet ID
php artisan embeddings:test-matching 1 --limit=10
```

### 5. Tester via le frontend
1. Démarrer le backend : `php artisan serve`
2. Démarrer le frontend : `pnpm dev`
3. Se connecter en tant qu'étudiant
4. Aller sur `/candidate/matching`
5. Observer les recommandations IA

---

## 🐛 Dépannage

### Erreur : "Connection refused"
```bash
# Vérifier que l'API est démarrée
netstat -ano | findstr :8000

# Démarrer l'API
cd C:\Mes_Dossiers\Memoire\VerifCertif_IA
uvicorn main:app --reload --port 8000
```

### Erreur : "Module not found"
```bash
# Réinstaller les dépendances
pip install -r requirements.txt

# Vérifier l'installation
pip list | findstr -i "fastapi uvicorn sentence torch"
```

### Erreur : "No matching records found"
- Vérifier que les profils étudiants ont des compétences
- Vérifier que les offres sont publiées (statut = 'PUBLIEE')
- Générer les embeddings : `php artisan embeddings:generate --type=all`

### Logs Laravel
```bash
# Voir les logs en temps réel
Get-Content -Path "storage\logs\laravel.log" -Tail 50 -Wait
```

---

## 📝 Checklist de déploiement

- [ ] API FastAPI fonctionne localement
- [ ] Health check retourne `true`
- [ ] Génération d'embedding fonctionne
- [ ] Embeddings candidats générés
- [ ] Embeddings offres générés
- [ ] Test de matching fonctionne
- [ ] Frontend affiche les recommandations
- [ ] API déployée sur Render
- [ ] Variables d'environnement configurées en production
- [ ] Tests end-to-end en production

---

## 🎯 Prochaines étapes

1. **Optimisations** :
   - Cache des embeddings
   - Batch processing
   - Webhook pour mise à jour automatique

2. **Améliorations** :
   - Multi-langue
   - Pondération dynamique
   - Fine-tuning du modèle

3. **Monitoring** :
   - Logs d'utilisation
   - Métriques de performance
   - Alertes en cas d'erreur
