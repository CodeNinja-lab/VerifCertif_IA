# Système de Gestion des Clés Ed25519

## 📋 Vue d'ensemble

Ce système gère automatiquement les clés privées Ed25519 pour la signature cryptographique des documents. Les clés sont stockées de manière sécurisée dans le système de fichiers, avec uniquement le chemin d'accès enregistré en base de données.

## 🏗️ Architecture

### Composants

1. **SimpleKeyManagementService** (`app/Services/SimpleKeyManagementService.php`)
   - Génération de paires de clés Ed25519
   - Stockage sécurisé des clés privées
   - Récupération des clés pour signature
   - Régénération et suppression de clés

2. **AdministrationObserver** (`app/Observers/AdministrationObserver.php`)
   - Génération automatique des clés à la création d'une administration
   - Suppression automatique des clés lors de la suppression d'une administration

3. **Commande Artisan** (`app/Console/Commands/GenerateAdministrationKeys.php`)
   - Génération manuelle de clés
   - Régénération de clés existantes
   - Traitement en masse

4. **DocumentEmissionService** (`app/Services/DocumentEmissionService.php`)
   - Utilise les clés pour signer les documents
   - Intégration transparente avec le système de clés

## 📁 Structure de stockage

```
storage/
└── app/
    └── private/
        └── keys/
            ├── .gitkeep (versionné)
            └── administrations/
                ├── 1/
                │   └── private.pem (NON versionné)
                ├── 2/
                │   └── private.pem (NON versionné)
                └── .../
```

**Base de données** (`administrations` table):
- `cle_publique_ed25519` (TEXT) : Clé publique en base64
- `private_key_path` (VARCHAR 500) : Chemin relatif vers la clé privée

## 🔐 Sécurité

### Principes

1. ✅ **Les clés privées ne sont JAMAIS stockées en base de données**
2. ✅ **Seul le chemin du fichier est enregistré**
3. ✅ **Les fichiers de clés sont exclus de Git** (via `.gitignore`)
4. ✅ **Permissions Unix restrictives** (chmod 600 sur les fichiers, 700 sur les dossiers)
5. ✅ **Stockage dans `storage/app/private/`** (non accessible publiquement)

### Configuration .gitignore

```gitignore
/storage/app/keys/*
!/storage/app/keys/.gitkeep
```

## 🚀 Utilisation

### Génération automatique

Les clés sont générées **automatiquement** lors de la création d'une administration :

```php
$administration = Administration::create([
    'nom' => 'Université XYZ',
    'type_administration' => 'universite',
    'pays' => 'France',
    'email_contact' => 'contact@univ-xyz.fr',
    // ...
]);

// ➜ Les clés sont générées automatiquement par l'Observer
```

### Génération manuelle

#### Pour une administration spécifique

```bash
php artisan keys:generate 1
```

#### Pour toutes les administrations

```bash
php artisan keys:generate
```

#### Régénérer des clés existantes

```bash
php artisan keys:generate 1 --regenerate
php artisan keys:generate --regenerate  # Pour toutes
```

### Utilisation dans le code

#### Vérifier si une administration a des clés

```php
use App\Services\SimpleKeyManagementService;

$keyService = new SimpleKeyManagementService();
$hasKeys = $keyService->hasKeyPair($administration);
```

#### Récupérer une clé privée

```php
$privateKey = $keyService->getPrivateKey($administration);
// ➜ Retourne la clé en format binaire (64 bytes)
```

#### Signer un document

Le `DocumentEmissionService` utilise automatiquement les clés :

```php
use App\Services\DocumentEmissionService;

$documentService = new DocumentEmissionService(
    new QrCodeService(),
    new BlockchainService(),
    new SimpleKeyManagementService()
);

$document = $documentService->emitDocument([
    'etudiant_id' => 1,
    'administration_id' => 1,  // ➜ Utilisera automatiquement ses clés
    'type_document' => 'diplome',
    'titre' => 'Licence Informatique',
    'file_url' => 'documents/diplome.pdf',
    // ...
]);
```

## 🛠️ Prérequis techniques

### Extension PHP Sodium

Le système utilise l'extension `sodium` de PHP pour la cryptographie Ed25519.

#### Vérifier si l'extension est installée

```bash
php -m | grep sodium
```

#### Activer l'extension (si nécessaire)

**Sur Windows (XAMPP)** :

1. Ouvrir `C:\xampp\php\php.ini`
2. Décommenter la ligne : `;extension=sodium` → `extension=sodium`
3. Redémarrer le serveur

**Sur Linux** :

```bash
# Ubuntu/Debian
sudo apt-get install php-sodium

# CentOS/RHEL
sudo yum install php-sodium
```

## 🧪 Tests

### Test complet du système

```bash
php test_complete_keys.php
```

Ce script teste :
- ✅ Présence des clés pour chaque administration
- ✅ Existence des fichiers physiques
- ✅ Lecture des clés privées
- ✅ Signature de messages
- ✅ Vérification des signatures

### Exemple de sortie

```
╔═══════════════════════════════════════════════════════════╗
║  TEST COMPLET DU SYSTÈME DE GESTION DES CLÉS ED25519     ║
╚═══════════════════════════════════════════════════════════╝

📊 Nombre d'administrations: 2

─────────────────────────────────────────────────────────
🏛️  Université de Test (ID: 1)
─────────────────────────────────────────────────────────
Clés configurées: ✓ OUI
Clé publique: PJa2qeECAjNDTCVghAWsvMAk951Wvvhr5LRcvOH3/sg=...
Chemin clé privée: keys/administrations/1/private.pem
Fichier physique: ✓ Existe
Taille du fichier: 88 bytes
Lecture clé privée: ✓ OK
Longueur clé (binaire): 64 bytes
Test signature: ✓ OK
Longueur signature: 64 bytes
Vérification signature: ✓ VALIDE
```

## 📊 Format des clés

### Clé publique Ed25519
- **Longueur** : 32 bytes (binaire)
- **Stockage** : Base64 (44 caractères)
- **Localisation** : Colonne `cle_publique_ed25519` en base de données

### Clé privée Ed25519
- **Longueur** : 64 bytes (binaire)
- **Stockage** : Base64 dans fichier `.pem` (88 caractères)
- **Localisation** : Fichier dans `storage/app/private/keys/administrations/{id}/private.pem`

### Signature Ed25519
- **Longueur** : 64 bytes (binaire)
- **Stockage** : Base64 (88 caractères)
- **Localisation** : Colonne `signature_ed25519` dans la table `documents`

## 🔄 Cycle de vie des clés

```
┌─────────────────────────────────────────────────────────┐
│ 1. Création d'une administration                       │
│    ↓                                                     │
│ 2. AdministrationObserver détecte la création          │
│    ↓                                                     │
│ 3. SimpleKeyManagementService génère une paire de clés │
│    ├─ Génère keypair avec sodium_crypto_sign_keypair() │
│    ├─ Extrait public key (32 bytes)                    │
│    ├─ Extrait private key (64 bytes)                   │
│    ├─ Encode en base64                                 │
│    ├─ Sauvegarde private key dans fichier .pem         │
│    └─ Met à jour la BDD avec public key + path         │
│    ↓                                                     │
│ 4. Clés prêtes pour signer des documents               │
│    ↓                                                     │
│ 5. [Si régénération nécessaire]                        │
│    ├─ Supprime l'ancien fichier .pem                   │
│    └─ Retour à l'étape 3                               │
│    ↓                                                     │
│ 6. [Si suppression de l'administration]                │
│    ├─ Supprime le fichier .pem                         │
│    ├─ Supprime le répertoire si vide                   │
│    └─ Met à jour la BDD (NULL)                         │
└─────────────────────────────────────────────────────────┘
```

## ⚠️ Considérations importantes

### Production

1. **Permissions des fichiers** : Assurez-vous que seul le serveur web peut lire les fichiers de clés
2. **Sauvegardes** : Les clés privées doivent être incluses dans les sauvegardes (mais chiffrées)
3. **Rotation des clés** : Utilisez `--regenerate` en cas de compromission
4. **Audit** : Toutes les opérations sur les clés sont loguées dans `storage/logs/laravel.log`

### Développement

- Les clés générées en développement ne doivent **JAMAIS** être utilisées en production
- Régénérez toutes les clés lors du déploiement en production

## 🆘 Dépannage

### Erreur : "Extension sodium non disponible"

```bash
# Vérifier l'installation
php -m | grep sodium

# Si absent, activer dans php.ini
extension=sodium
```

### Erreur : "Le fichier de clé privée est introuvable"

```bash
# Régénérer les clés
php artisan keys:generate {administration_id} --regenerate
```

### Erreur : "Mass assignment" lors de la mise à jour

Vérifiez que `private_key_path` est dans le `$fillable` du modèle `Administration`.

## 📝 Logs

Toutes les opérations importantes sont enregistrées :

```php
// Génération de clés
[2025-11-28 18:58:44] local.INFO: Paire de clés Ed25519 générée 
{
    "administration_id": 1,
    "administration_nom": "Université de Test",
    "private_key_path": "keys/administrations/1/private.pem"
}

// Suppression de clés
[2025-11-28 19:15:00] local.WARNING: Paire de clés supprimée
{
    "administration_id": 1,
    "administration_nom": "Université de Test"
}
```

## ✅ Checklist de déploiement

- [ ] Extension `sodium` activée
- [ ] Migration `add_private_key_path_to_administrations_table` exécutée
- [ ] `private_key_path` ajouté au `$fillable` du modèle `Administration`
- [ ] `AdministrationObserver` enregistré dans `AppServiceProvider`
- [ ] `.gitignore` mis à jour pour exclure `/storage/app/keys/*`
- [ ] `.gitkeep` présent dans `/storage/app/private/keys/`
- [ ] Permissions Unix configurées (si Linux/Mac)
- [ ] Clés générées pour toutes les administrations existantes
- [ ] Tests de signature/vérification réussis

---

**Système opérationnel et prêt pour la production ! ✓**
