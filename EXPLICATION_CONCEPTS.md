# 📚 Explication des Concepts Utilisés dans VeriCertis Backend

## 1. 🗄️ **MIGRATIONS LARAVEL** (`database/migrations/`)

### Qu'est-ce qu'une migration ?
Une migration est un fichier PHP qui décrit la structure de ta base de données. C'est comme un "plan de construction" pour tes tables.

### Exemple concret :
```php
Schema::create('documents', function (Blueprint $table) {
    $table->id();  // Crée une colonne 'id' auto-incrémentée
    $table->string('titre', 255);  // Colonne texte de 255 caractères max
    $table->enum('statut', ['ACTIF', 'REVOQUE', 'EXPIRE']);  // Liste de valeurs possibles
});
```

### Concepts clés dans les migrations :

#### **Types de colonnes :**
- `$table->id()` → Colonne `id` (INT, clé primaire, auto-incrémentée)
- `$table->string('nom', 100)` → VARCHAR(100)
- `$table->text('description')` → TEXT (illimité)
- `$table->integer('age')` → INT
- `$table->boolean('is_active')` → BOOLEAN (true/false)
- `$table->date('date_naissance')` → DATE
- `$table->timestamp('created_at')` → TIMESTAMP (date + heure)
- `$table->jsonb('metadata')` → JSONB (PostgreSQL) - Stocke du JSON structuré
- `$table->uuid('uuid_document')` → UUID (identifiant unique universel)

#### **Contraintes et index :**
- `->unique()` → Garantit que la valeur est unique (ex: email)
- `->nullable()` → Permet les valeurs NULL
- `->default('valeur')` → Valeur par défaut si non renseigné
- `->index()` → Crée un index pour accélérer les recherches
- `->foreignId('user_id')->constrained('users')` → Clé étrangère vers la table `users`

#### **Actions sur suppression (onDelete) :**
```php
->onDelete('cascade')   // Si on supprime l'utilisateur, supprime aussi ses documents
->onDelete('restrict')  // Empêche la suppression si des documents existent
->onDelete('set null')  // Met NULL si l'élément référencé est supprimé
```

---

## 2. 🎯 **MODÈLES ELOQUENT** (`app/Models/`)

### Qu'est-ce qu'un modèle ?
Un modèle est une classe PHP qui représente une table de la base de données. C'est ton "pont" entre PHP et la base de données.

### Exemple :
```php
class Document extends Model
{
    // Le modèle Document représente la table 'documents'
}
```

### Propriétés importantes :

#### **1. `$fillable` - Champs modifiables en masse**
```php
protected $fillable = [
    'titre',
    'statut',
    'date_emission'
];
```
**Pourquoi ?** Sécurité ! Seuls ces champs peuvent être modifiés via `Document::create()` ou `$document->update()`. Protège contre l'injection de données non autorisées.

**Exemple d'utilisation :**
```php
// ✅ OK - Ces champs sont dans $fillable
Document::create([
    'titre' => 'Diplôme Master',
    'statut' => 'ACTIF'
]);

// ❌ IGNORÉ - 'hack_field' n'est pas dans $fillable
Document::create([
    'titre' => 'Diplôme Master',
    'hack_field' => 'valeur malveillante'  // Ignoré !
]);
```

#### **2. `$hidden` - Champs cachés dans les réponses JSON**
```php
protected $hidden = [
    'mot_de_passe_hash',
    'token_2fa_secret'
];
```
**Pourquoi ?** Quand tu fais `$user->toJson()`, ces champs ne seront jamais exposés. Sécurité !

**Exemple :**
```php
$user = User::find(1);
echo $user->toJson();
// {"id":1,"prenom":"Babacar","email":"babacar@example.com"}
// ❌ mot_de_passe_hash n'apparaît JAMAIS
```

#### **3. `casts()` - Conversion automatique des types**
```php
protected function casts(): array
{
    return [
        'date_emission' => 'date',        // Convertit en objet Carbon\Carbon
        'is_active' => 'boolean',          // Convertit 0/1 en true/false
        'metadata' => 'array',              // Convertit JSON en tableau PHP
        'mot_de_passe_hash' => 'hashed'    // Hash automatiquement le mot de passe
    ];
}
```

**Pourquoi ?** Laravel convertit automatiquement les types pour toi !

**Exemple :**
```php
$document = Document::find(1);
echo $document->date_emission->format('Y-m-d');  // ✅ Fonctionne car c'est un objet Carbon
echo $document->metadata['annee'];  // ✅ Fonctionne car c'est un tableau PHP

// Sans cast, tu devrais faire :
$date = \Carbon\Carbon::parse($document->date_emission);  // ❌ Plus long !
```

#### **4. Relations Eloquent**

Les relations définissent les liens entre les tables.

##### **hasMany** - "Un à plusieurs"
```php
// Un User a plusieurs Documents
public function documents()
{
    return $this->hasMany(Document::class, 'etudiant_id');
}
```
**Utilisation :**
```php
$user = User::find(1);
$documents = $user->documents;  // Récupère tous les documents de l'utilisateur
```

##### **belongsTo** - "Plusieurs appartiennent à un"
```php
// Un Document appartient à un User
public function etudiant()
{
    return $this->belongsTo(User::class, 'etudiant_id');
}
```
**Utilisation :**
```php
$document = Document::find(1);
$etudiant = $document->etudiant;  // Récupère l'utilisateur propriétaire
```

##### **hasOne** - "Un à un"
```php
// Un User a un seul ProfilEtudiant
public function profilEtudiant()
{
    return $this->hasOne(ProfilEtudiant::class, 'utilisateur_id');
}
```

**Résumé des relations :**
- `hasMany` → 1 User a N Documents
- `belongsTo` → 1 Document appartient à 1 User
- `hasOne` → 1 User a 1 ProfilEtudiant

---

## 3. 🔄 **ÉVÉNEMENTS DU MODÈLE (boot method)**

### Qu'est-ce que `boot()` ?
C'est une méthode qui s'exécute automatiquement lors d'événements du modèle (création, mise à jour, suppression).

### Exemple dans Document :
```php
protected static function boot()
{
    parent::boot();

    static::creating(function ($document) {
        if (empty($document->uuid_document)) {
            $document->uuid_document = (string) Str::uuid();
        }
    });
}
```

**Ce que ça fait :** Avant de créer un document, si `uuid_document` est vide, génère automatiquement un UUID unique.

**Événements disponibles :**
- `creating` → Avant la création
- `created` → Après la création
- `updating` → Avant la mise à jour
- `updated` → Après la mise à jour
- `deleting` → Avant la suppression
- `deleted` → Après la suppression

---

## 4. 🔐 **AUTHENTIFICATION LARAVEL (getAuthPassword)**

### Dans le modèle User :
```php
public function getAuthPassword()
{
    return $this->mot_de_passe_hash;
}
```

**Pourquoi ?** Laravel cherche par défaut une colonne `password`, mais nous avons `mot_de_passe_hash`. Cette méthode dit à Laravel : "Utilise cette colonne pour l'authentification".

**Utilisation :**
```php
// Laravel utilise automatiquement getAuthPassword()
Auth::attempt(['email' => $email, 'password' => $password]);
// Laravel va chercher mot_de_passe_hash grâce à getAuthPassword()
```

---

## 5. 📊 **JSONB vs JSON (PostgreSQL)**

### Différence :
- **JSON** → Stocke du texte JSON brut
- **JSONB** → Stocke du JSON binaire optimisé (plus rapide, indexable)

### Dans nos migrations :
```php
$table->jsonb('metadata')->nullable();
```

### Dans le modèle :
```php
'metadata' => 'array'  // Convertit automatiquement JSON ↔ Tableau PHP
```

### Exemple d'utilisation :
```php
// Créer un document avec metadata
$document = Document::create([
    'titre' => 'Diplôme Master',
    'metadata' => [
        'annee' => 2024,
        'mention' => 'Très Bien',
        'credits' => 120
    ]
]);

// Lire les metadata
echo $document->metadata['annee'];  // 2024
```

---

## 6. 🔗 **CLÉS ÉTRANGÈRES (Foreign Keys)**

### Dans la migration :
```php
$table->foreignId('etudiant_id')->constrained('users')->onDelete('cascade');
```

**Décomposition :**
- `foreignId('etudiant_id')` → Crée une colonne `etudiant_id` de type INT
- `constrained('users')` → Référence la table `users` (colonne `id`)
- `onDelete('cascade')` → Si on supprime un User, supprime aussi ses Documents

### Pourquoi c'est important ?
**Intégrité référentielle** : Impossible d'avoir un document avec un `etudiant_id` qui n'existe pas dans `users`.

---

## 7. 📈 **INDEX - Pourquoi c'est important ?**

### Dans la migration :
```php
$table->index('etudiant_id');
$table->index('statut');
```

**Qu'est-ce qu'un index ?** C'est comme un "sommaire" de livre. Au lieu de parcourir toutes les pages, tu vas directement à la page.

**Impact sur les performances :**
```php
// SANS index : Laravel doit parcourir TOUS les documents (lent)
Document::where('etudiant_id', 1)->get();

// AVEC index : PostgreSQL va directement aux documents de l'étudiant 1 (rapide)
```

**Règle :** Indexe les colonnes utilisées dans `WHERE`, `JOIN`, `ORDER BY`.

---

## 8. 🎨 **ENUM - Liste de valeurs possibles**

### Dans la migration :
```php
$table->enum('statut', ['ACTIF', 'REVOQUE', 'EXPIRE'])->default('ACTIF');
```

**Ce que ça fait :** La colonne `statut` ne peut contenir QUE ces 3 valeurs. Impossible d'insérer autre chose.

**Avantages :**
- Validation automatique au niveau base de données
- Plus rapide qu'une table de référence
- Lisibilité du code

---

## 9. 🔄 **TIMESTAMPS automatiques**

### Dans la migration :
```php
$table->timestamps();  // Crée 'created_at' et 'updated_at'
```

**Ce que ça fait :**
- `created_at` → Rempli automatiquement à la création
- `updated_at` → Mis à jour automatiquement à chaque modification

**Dans le modèle :**
```php
// Laravel gère automatiquement ces colonnes
$document = Document::create(['titre' => 'Test']);
echo $document->created_at;  // Date de création automatique
```

---

## 10. 🎯 **RÉSUMÉ - Comment tout ça fonctionne ensemble**

### Scénario : Créer un document

1. **Migration** → Crée la table `documents` avec toutes les colonnes
2. **Modèle Document** → Définit les règles (`$fillable`, `casts`, relations)
3. **Code PHP** :
```php
$document = Document::create([
    'etudiant_id' => 1,
    'administration_id' => 1,
    'titre' => 'Diplôme Master',
    'statut' => 'ACTIF',
    'metadata' => ['annee' => 2024]
]);
```

4. **Laravel fait automatiquement :**
   - ✅ Vérifie que les champs sont dans `$fillable`
   - ✅ Génère un UUID (grâce à `boot()`)
   - ✅ Convertit `metadata` en JSON (grâce à `casts`)
   - ✅ Remplit `created_at` et `updated_at`
   - ✅ Vérifie que `etudiant_id` existe dans `users` (foreign key)

5. **Utilisation ensuite :**
```php
$document = Document::find(1);
echo $document->etudiant->prenom;  // Relation belongsTo
echo $document->metadata['annee'];  // Cast array
```

---

## 🚀 **Prochaines étapes : Créer les endpoints API**

Maintenant que tu comprends ces concepts, on peut créer les contrôleurs et routes pour l'API !

Les endpoints utiliseront ces modèles pour :
- Créer des documents
- Lister les utilisateurs
- Vérifier l'authenticité des documents
- Gérer les matchings
- etc.

**Questions ?** N'hésite pas à demander des clarifications sur un point précis ! 🎓

