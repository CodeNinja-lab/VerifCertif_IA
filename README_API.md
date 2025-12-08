# Backend VeriCertis - Documentation API

Backend Laravel complet pour le système de certification de diplômes et matching d'offres d'emploi.

## Installation

1. Installer les dépendances :
```bash
composer install
```

2. Créer le fichier .env :
```bash
cp .env.example .env
```

3. Générer la clé d'application :
```bash
php artisan key:generate
```

4. Configurer la base de données dans `.env`

5. Exécuter les migrations :
```bash
php artisan migrate
```

6. Publier la configuration Sanctum (optionnel) :
```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

## Structure du Backend

### Modèles
- **User** : Utilisateurs (étudiants, recruteurs, administrations, admins)
- **Administration** : Administrations certifiantes
- **Document** : Documents certifiés (diplômes, attestations, etc.)
- **Competence** : Compétences référencées
- **ProfilEtudiant** : Profils étudiants avec informations complémentaires
- **ProfilCompetence** : Compétences associées aux profils étudiants
- **Offre** : Offres d'emploi
- **OffreCompetence** : Compétences requises pour les offres
- **Matching** : Matchings entre offres et étudiants
- **Notification** : Notifications système
- **VerificationLog** : Logs de vérification de documents
- **Revocation** : Révocations de documents
- **AuditLog** : Logs d'audit système

### Contrôleurs API (V1)

#### Authentification (`/api/v1/auth`)
- `POST /register` - Inscription
- `POST /login` - Connexion
- `POST /logout` - Déconnexion (authentifié)
- `GET /me` - Profil utilisateur connecté (authentifié)
- `PUT /profile` - Mettre à jour le profil (authentifié)
- `POST /change-password` - Changer le mot de passe (authentifié)
- `POST /forgot-password` - Demander réinitialisation (public)
- `POST /reset-password` - Réinitialiser le mot de passe (public)

#### Utilisateurs (`/api/v1/users`) - Admin uniquement
- `GET /` - Liste des utilisateurs
- `GET /{id}` - Afficher un utilisateur
- `POST /` - Créer un utilisateur
- `PUT /{id}` - Mettre à jour un utilisateur
- `DELETE /{id}` - Supprimer un utilisateur
- `POST /{id}/activate` - Activer un utilisateur
- `POST /{id}/deactivate` - Désactiver un utilisateur

#### Documents (`/api/v1/documents`)
- `GET /` - Liste des documents (authentifié)
- `POST /` - Créer un document (authentifié)
- `GET /{id}` - Afficher un document (authentifié)
- `PUT /{id}` - Mettre à jour un document (authentifié)
- `DELETE /{id}` - Supprimer un document (authentifié)
- `GET /{id}/download` - Télécharger un document (authentifié)
- `GET /{id}/logs` - Logs de vérification (authentifié)
- `POST /{id}/revoke` - Révoquer un document (administration/admin)
- `POST /verify` - Vérifier un document (public)
- `GET /{uuid}/verify` - Vérifier par UUID (public)
- `GET /{uuid}/qr-code` - Obtenir QR code (public)

#### Administrations (`/api/v1/administrations`)
- `GET /` - Liste des administrations (public)
- `GET /{id}` - Afficher une administration (public)
- `POST /` - Créer une administration (admin)
- `PUT /{id}` - Mettre à jour (admin)
- `DELETE /{id}` - Supprimer (admin)
- `POST /{id}/verify` - Vérifier une administration (admin)
- `POST /{id}/suspend` - Suspendre (admin)

#### Compétences (`/api/v1/competences`)
- `GET /` - Liste des compétences (public)
- `GET /{id}` - Afficher une compétence (public)
- `GET /search/{query}` - Rechercher des compétences (public)
- `POST /` - Créer une compétence (admin)
- `PUT /{id}` - Mettre à jour (admin)
- `DELETE /{id}` - Supprimer (admin)

#### Profil Étudiant (`/api/v1/profil-etudiant`)
- `GET /` - Afficher le profil (authentifié)
- `POST /` - Créer le profil (authentifié)
- `PUT /` - Mettre à jour le profil (authentifié)
- `DELETE /` - Supprimer le profil (authentifié)
- `GET /competences` - Liste des compétences du profil (authentifié)
- `POST /competences` - Ajouter une compétence (authentifié)
- `PUT /competences/{competenceId}` - Mettre à jour une compétence (authentifié)
- `DELETE /competences/{competenceId}` - Supprimer une compétence (authentifié)

#### Offres d'Emploi (`/api/v1/offres`)
- `GET /` - Liste des offres (public pour PUBLIEE)
- `GET /{id}` - Afficher une offre (public)
- `POST /` - Créer une offre (recruteur/admin)
- `PUT /{id}` - Mettre à jour (recruteur/admin)
- `DELETE /{id}` - Supprimer (recruteur/admin)
- `POST /{id}/publish` - Publier une offre (recruteur/admin)
- `POST /{id}/archive` - Archiver une offre (recruteur/admin)
- `GET /{id}/competences` - Compétences de l'offre (authentifié)
- `POST /{id}/competences` - Ajouter une compétence (recruteur/admin)
- `PUT /{id}/competences/{competenceId}` - Mettre à jour (recruteur/admin)
- `DELETE /{id}/competences/{competenceId}` - Supprimer (recruteur/admin)
- `GET /{id}/matchings` - Matchings de l'offre (recruteur/admin)

#### Matchings (`/api/v1/matchings`)
- `GET /` - Liste des matchings de l'étudiant (authentifié)
- `GET /{id}` - Afficher un matching (authentifié)
- `POST /{id}/view` - Marquer comme vu (authentifié)
- `POST /{id}/interest` - Définir l'intérêt (authentifié)
- `POST /calculate` - Calculer les matchings (admin)

#### Notifications (`/api/v1/notifications`)
- `GET /` - Liste des notifications (authentifié)
- `GET /unread` - Notifications non lues (authentifié)
- `GET /{id}` - Afficher une notification (authentifié)
- `POST /{id}/read` - Marquer comme lue (authentifié)
- `POST /read-all` - Tout marquer comme lu (authentifié)
- `POST /{id}/archive` - Archiver (authentifié)
- `DELETE /{id}` - Supprimer (authentifié)

#### Vérification Logs (`/api/v1/verification-logs`)
- `GET /` - Liste des logs (authentifié)
- `GET /{id}` - Afficher un log (authentifié)

#### Révocations (`/api/v1/revocations`)
- `GET /` - Liste des révocations (administration/admin)
- `GET /{id}` - Afficher une révocation (administration/admin)
- `POST /` - Créer une révocation (administration/admin)

#### Audit Logs (`/api/v1/audit-logs`)
- `GET /` - Liste des logs d'audit (admin)
- `GET /{id}` - Afficher un log (admin)

#### Statistiques (`/api/v1/statistics`)
- `GET /dashboard` - Statistiques du dashboard (admin)
- `GET /documents` - Statistiques sur les documents (admin)
- `GET /matchings` - Statistiques sur les matchings (admin)
- `GET /users` - Statistiques sur les utilisateurs (admin)

## Authentification

L'API utilise Laravel Sanctum pour l'authentification par token.

### Obtenir un token
```http
POST /api/v1/auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password"
}
```

### Utiliser le token
```http
Authorization: Bearer {token}
```

## Rôles

- **etudiant** : Peut gérer son profil, ses documents, voir les matchings
- **recruteur** : Peut créer et gérer des offres d'emploi
- **administration** : Peut certifier et révoquer des documents
- **admin** : Accès complet au système

## Services

### DocumentVerificationService
Service de vérification de documents avec logs.

### MatchingService
Service de calcul de matchings entre offres et profils étudiants.

## Notes

- Toutes les dates sont au format ISO 8601
- Les réponses paginées utilisent la pagination Laravel standard
- Les erreurs suivent le format JSON standard avec message et erreurs

Backend complété
Fonctionnalités ajoutées
1. Vérification publique
Service de génération de QR code (QrCodeService)
Génération SVG et PNG
Stockage automatique
URL de vérification intégrée
Endpoints publics de vérification :
POST /api/v1/documents/verify — Vérification par UUID, hash ou fichier uploadé
GET /api/v1/documents/{uuid}/verify — Vérification par UUID (QR code)
GET /verify/{uuid} — URL courte pour QR code
GET /api/v1/documents/{uuid}/qr-code — Obtenir/générer QR code
2. Flux d'émission complet (DocumentEmissionService)
Téléversement du document
Calcul automatique du hash SHA-256
Signature Ed25519 avec clé privée de l'administration
Création du certificat avec métadonnées
Génération du QR code (SVG et PNG)
Ancrage sur blockchain (optionnel)
3. Flux de vérification complet (DocumentVerificationService)
Utilisateur scanne le QR ou soumet le fichier
Calcul du hash du document
Comparaison avec le registre + vérification de la signature
Affichage du résultat et des métadonnées
4. Ancrage blockchain (BlockchainService)
Support Ethereum, Polygon, BSC
Ancrage optionnel des documents
Vérification de l'ancrage
Configuration via .env
5. Tableau de bord analytique (StatisticsController)
Documents certifiés par période
Répartition des demandes (public/privé)
Pics d'activité (par mois, par jour de la semaine)
Compétences les plus demandées
Diplômes les plus demandés
Fichiers créés/modifiés
Services :
app/Services/QrCodeService.php
app/Services/BlockchainService.php
app/Services/DocumentEmissionService.php
app/Services/DocumentVerificationService.php (amélioré)
Configuration :
config/blockchain.php
Contrôleurs améliorés :
app/Http/Controllers/Api/V1/DocumentController.php
app/Http/Controllers/Api/V1/StatisticsController.php
Documentation :
RESUME_FONCTIONNALITES_COMPLETEES.md
Packages ajoutés
"simplesoftwareio/simple-qrcode": "^4.2"
Configuration requise
Ajoutez dans votre .env :
# Blockchain (optionnel)BLOCKCHAIN_NETWORK=ethereumBLOCKCHAIN_CONTRACT_ADDRESS=BLOCKCHAIN_API_KEY=BLOCKCHAIN_API_URL=BLOCKCHAIN_ENABLED=false
Installation
Installer les dépendances :
composer install
Créer le lien symbolique pour le stockage :
php artisan storage:link
Configurer .env avec vos paramètres
Utilisation
Le backend est prêt avec :
Vérification publique via QR code
Vérification publique via URL
Vérification publique via upload de fichier
Flux d'émission complet automatisé
Ancrage blockchain optionnel
Dashboard analytique complet
Tout est opérationnel.