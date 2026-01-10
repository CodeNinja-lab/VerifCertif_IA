# Guide de Déploiement Render - VeriCertis Backend

## 🔧 Corrections Apportées

### 1. **Dockerfile**
- ✅ Supprimé la syntaxe `COPY <<'EOF'` incompatible avec les anciennes versions Docker
- ✅ Ajout de l'extension `libzip-dev` pour PHP
- ✅ Optimisation des layers Docker (cache des dépendances)
- ✅ Création des dossiers storage au build
- ✅ Copie des configurations depuis des fichiers externes
- ✅ Ajout d'un script d'entrée (entrypoint)

### 2. **Configuration Nginx** (`conf/nginx/nginx-site.conf`)
- ✅ Port changé de 80 à 10000 (requis par Render)
- ✅ Root changé de `/var/www/html/public` à `/app/public`
- ✅ FastCGI passé de socket Unix à TCP `127.0.0.1:9000`
- ✅ Ajout `client_max_body_size 20M` pour uploads
- ✅ Ajout `fastcgi_buffering off` pour meilleures performances

### 3. **Configuration Supervisor** (`conf/supervisor/supervisord.conf`)
- ✅ Fichier créé pour gérer PHP-FPM et Nginx
- ✅ Logs vers stdout/stderr pour Render
- ✅ Gestion des priorités de démarrage

### 4. **Script d'Entrée** (`docker-entrypoint.sh`)
- ✅ Génération automatique de APP_KEY
- ✅ Clear des caches au démarrage
- ✅ Migrations automatiques (optionnel)
- ✅ Cache des configurations pour performance
- ✅ Permissions correctes sur storage

### 5. **.dockerignore**
- ✅ Exclusion de vendor (sera installé au build)
- ✅ Exclusion des fichiers de développement
- ✅ Conservation des .gitkeep pour storage

### 6. **render.yaml**
- ✅ Configuration complète pour déploiement automatique
- ✅ Variables d'environnement pré-configurées
- ✅ Connexion automatique à la base PostgreSQL

---

## 🚀 Étapes de Déploiement sur Render

### Option 1 : Via render.yaml (Recommandé)

1. **Commit et Push des modifications**
   ```bash
   git add .
   git commit -m "Fix: Configuration Docker pour Render"
   git push origin main
   ```

2. **Créer un compte Render**
   - Allez sur https://render.com
   - Connectez-vous avec GitHub

3. **Créer le service depuis render.yaml**
   - Cliquez sur "New +"
   - Sélectionnez "Blueprint"
   - Choisissez votre dépôt `veriCertis_backend`
   - Render détectera automatiquement `render.yaml`
   - Cliquez sur "Apply"

4. **Configurer les variables d'environnement**
   - Render créera automatiquement la base PostgreSQL
   - Ajoutez `APP_KEY` manuellement :
     ```bash
     # Générer une clé Laravel
     php artisan key:generate --show
     ```
   - Copiez la clé générée dans Render

### Option 2 : Configuration Manuelle

1. **Créer la base de données**
   - Dashboard Render → "New +" → "PostgreSQL"
   - Name: `veriCertis-db`
   - Plan: Free
   - Notez les credentials

2. **Créer le Web Service**
   - Dashboard Render → "New +" → "Web Service"
   - Connectez votre repo GitHub
   - Configurez :
     - **Name**: veriCertis-backend
     - **Environment**: Docker
     - **Region**: Frankfurt (plus proche de l'Europe)
     - **Branch**: main
     - **Plan**: Free

3. **Variables d'Environnement**
   Ajoutez ces variables dans Render :
   ```env
   APP_NAME=VeriCertis
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=[votre_clé_générée]
   APP_URL=[votre_url_render.com]
   
   DB_CONNECTION=pgsql
   DB_HOST=[host_de_votre_db]
   DB_PORT=5432
   DB_DATABASE=[nom_db]
   DB_USERNAME=[user_db]
   DB_PASSWORD=[password_db]
   
   CACHE_DRIVER=file
   SESSION_DRIVER=file
   QUEUE_CONNECTION=database
   
   LOG_CHANNEL=stack
   LOG_LEVEL=info
   ```

4. **Déployer**
   - Cliquez sur "Create Web Service"
   - Render va automatiquement :
     - Cloner le repo
     - Build le Dockerfile
     - Déployer l'application

---

## ✅ Vérifications Post-Déploiement

1. **Vérifier les logs**
   - Dashboard Render → Votre service → "Logs"
   - Cherchez : "✅ Laravel setup complete!"

2. **Tester l'API**
   ```bash
   curl https://votre-app.onrender.com/api/v1/health
   ```

3. **Vérifier la connexion DB**
   - Les logs doivent montrer : "⏳ Waiting for database..."
   - Puis : "✅ Laravel setup complete!"

---

## 🐛 Debugging des Erreurs Courantes

### Erreur : "Exited with status 1"
**Causes possibles :**
- Syntaxe Docker incompatible (✅ Corrigé)
- Fichiers manquants (✅ Tous créés)
- Dépendances manquantes (✅ Ajoutées)

**Solution :** Les fichiers ont été corrigés, re-push le code

### Erreur : "Cannot write to storage"
**Cause :** Permissions incorrectes

**Solution :**
- Vérifiez que `docker-entrypoint.sh` s'exécute
- Logs devraient montrer les permissions définies

### Erreur : "APP_KEY not set"
**Cause :** Variable d'environnement manquante

**Solution :**
```bash
# Générer une clé
php artisan key:generate --show
# Ajouter dans Render : Environment → Add Environment Variable
```

### Erreur : "Database connection failed"
**Cause :** Variables DB incorrectes

**Solution :**
- Copiez les credentials depuis Render Dashboard → PostgreSQL
- Utilisez `DATABASE_URL` ou les variables individuelles

---

## 📝 Commandes Utiles

### Rebuild Force
```bash
# Si vous modifiez le Dockerfile
git commit --allow-empty -m "Trigger rebuild"
git push
```

### Voir les Logs en Direct
```bash
# Dashboard Render → Logs → Auto-scroll activé
```

### Exécuter des Commandes
```bash
# Dashboard Render → Shell
php artisan migrate:status
php artisan config:cache
```

---

## 🔐 Sécurité Post-Déploiement

1. **Désactiver le debug**
   ```env
   APP_DEBUG=false
   ```

2. **Configurer CORS**
   - Ajoutez votre domaine frontend dans `config/cors.php`

3. **SSL/TLS**
   - Render fournit automatiquement HTTPS

4. **Rate Limiting**
   - Déjà configuré dans `routes/api.php`

---

## 📊 Performance

### Render Free Tier
- **Specs** : 512 MB RAM, CPU partagé
- **Sleep** : Après 15 min d'inactivité
- **Wake** : ~30 secondes au premier accès

### Optimisations Appliquées
- ✅ Cache des configs Laravel
- ✅ Cache des routes
- ✅ Composer optimized autoloader
- ✅ Nginx avec fastcgi_buffering off

---

## 🆘 Support

Si le déploiement échoue encore :

1. **Vérifiez les logs Render** (très détaillés)
2. **Testez localement** avec Docker :
   ```bash
   docker build -t test .
   docker run -p 10000:10000 test
   ```
3. **Vérifiez les commits** :
   ```bash
   git log --oneline -5
   ```

---

## 📦 Fichiers Modifiés/Créés

- ✅ `Dockerfile` - Configuration Docker optimisée
- ✅ `conf/nginx/nginx-site.conf` - Config Nginx pour Render
- ✅ `conf/supervisor/supervisord.conf` - Config Supervisor
- ✅ `docker-entrypoint.sh` - Script de démarrage
- ✅ `.dockerignore` - Exclusions Docker
- ✅ `render.yaml` - Blueprint Render
- ✅ `storage/**/.gitkeep` - Dossiers storage

Tous les fichiers sont maintenant prêts pour un déploiement réussi sur Render ! 🚀
