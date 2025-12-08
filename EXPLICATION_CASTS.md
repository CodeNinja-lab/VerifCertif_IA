# 🎯 Explication Détaillée de la Fonction `casts()`

## 📖 Qu'est-ce que `casts()` ?

La fonction `casts()` indique à Laravel **comment convertir automatiquement** les données entre la base de données et PHP.

**Sans `casts()`** : Les données viennent de la base de données telles quelles (string, int, etc.)
**Avec `casts()`** : Laravel les convertit automatiquement en types PHP utiles (objets, tableaux, booléens, etc.)

---

## 🔄 Comment ça fonctionne ?

### Principe :
1. **Lors de la LECTURE** depuis la base → Laravel convertit automatiquement
2. **Lors de l'ÉCRITURE** vers la base → Laravel convertit automatiquement

---

## 📚 Exemples Concrets dans Notre Code

### 1. **Cast `'array'` - JSON ↔ Tableau PHP**

#### Dans le modèle Document :
```php
protected function casts(): array
{
    return [
        'metadata' => 'array',  // Convertit JSON ↔ Tableau
    ];
}
```

#### ❌ SANS cast (ce que tu devrais faire manuellement) :
```php
// Créer un document
$document = Document::create([
    'titre' => 'Diplôme Master',
    'metadata' => json_encode([  // ❌ Tu dois convertir manuellement
        'annee' => 2024,
        'mention' => 'Très Bien'
    ])
]);

// Lire les metadata
$metadata = json_decode($document->metadata, true);  // ❌ Tu dois décoder manuellement
echo $metadata['annee'];  // 2024
```

#### ✅ AVEC cast `'array'` (automatique) :
```php
// Créer un document
$document = Document::create([
    'titre' => 'Diplôme Master',
    'metadata' => [  // ✅ Tu passes directement un tableau PHP
        'annee' => 2024,
        'mention' => 'Très Bien'
    ]
]);
// Laravel convertit automatiquement en JSON pour la base de données

// Lire les metadata
echo $document->metadata['annee'];  // ✅ C'est déjà un tableau PHP !
// Laravel a automatiquement décodé le JSON
```

**Ce qui se passe en base de données :**
- **Stocké** : `{"annee":2024,"mention":"Très Bien"}` (JSON)
- **En PHP** : `['annee' => 2024, 'mention' => 'Très Bien']` (tableau)

---

### 2. **Cast `'date'` et `'datetime'` - String ↔ Objet Carbon**

#### Dans le modèle Document :
```php
protected function casts(): array
{
    return [
        'date_emission' => 'date',        // Convertit en Carbon (date seule)
        'date_certification' => 'datetime', // Convertit en Carbon (date + heure)
    ];
}
```

#### ❌ SANS cast :
```php
$document = Document::find(1);

// La date est une string
echo $document->date_emission;  // "2024-01-15" (string)

// Pour formater, tu dois faire :
$date = \Carbon\Carbon::parse($document->date_emission);
echo $date->format('d/m/Y');  // "15/01/2024"
echo $date->diffForHumans();   // "il y a 2 mois"
```

#### ✅ AVEC cast `'date'` :
```php
$document = Document::find(1);

// La date est automatiquement un objet Carbon
echo $document->date_emission->format('d/m/Y');  // ✅ "15/01/2024"
echo $document->date_emission->diffForHumans();  // ✅ "il y a 2 mois"
echo $document->date_emission->year;              // ✅ 2024
echo $document->date_emission->month;            // ✅ 1

// Tu peux aussi créer directement avec un objet Carbon
$document = Document::create([
    'titre' => 'Diplôme',
    'date_emission' => \Carbon\Carbon::now(),  // ✅ Fonctionne !
]);
```

**Ce qui se passe :**
- **Stocké en base** : `"2024-01-15"` (DATE)
- **En PHP** : Objet `Carbon` avec toutes les méthodes utiles

---

### 3. **Cast `'boolean'` - 0/1 ↔ true/false**

#### Dans le modèle ProfilEtudiant :
```php
protected function casts(): array
{
    return [
        'profil_public' => 'boolean',
    ];
}
```

#### ❌ SANS cast :
```php
$profil = ProfilEtudiant::find(1);

// En base : 0 ou 1 (INT)
echo $profil->profil_public;  // 1 (int)

// Pour vérifier, tu dois faire :
if ($profil->profil_public == 1) {  // ❌ Pas très lisible
    // ...
}
```

#### ✅ AVEC cast `'boolean'` :
```php
$profil = ProfilEtudiant::find(1);

// En PHP : true ou false (BOOLEAN)
echo $profil->profil_public;  // true (boolean)

// Vérification naturelle
if ($profil->profil_public) {  // ✅ Plus lisible !
    echo "Le profil est public";
}

// Créer avec boolean
$profil = ProfilEtudiant::create([
    'utilisateur_id' => 1,
    'profil_public' => true,  // ✅ Laravel convertit en 1
]);
```

**Ce qui se passe :**
- **Stocké en base** : `1` ou `0` (TINYINT/BOOLEAN)
- **En PHP** : `true` ou `false` (boolean)

---

### 4. **Cast `'decimal:2'` - Précision des décimales**

#### Dans le modèle Matching :
```php
protected function casts(): array
{
    return [
        'score_global' => 'decimal:2',  // 2 décimales
    ];
}
```

#### Exemple :
```php
$matching = Matching::create([
    'offre_id' => 1,
    'etudiant_id' => 1,
    'score_global' => 85.56789,  // Tu passes n'importe quel nombre
]);

// En base : 85.57 (arrondi à 2 décimales)
echo $matching->score_global;  // "85.57" (string avec 2 décimales)

// Pour calculer
$total = (float)$matching->score_global + 10;  // 95.57
```

**Ce qui se passe :**
- **Stocké en base** : `85.57` (DECIMAL(5,2))
- **En PHP** : `"85.57"` (string, mais avec précision garantie)

---

### 5. **Cast `'hashed'` - Hash automatique des mots de passe**

#### Dans le modèle User :
```php
protected function casts(): array
{
    return [
        'mot_de_passe_hash' => 'hashed',
    ];
}
```

#### ❌ SANS cast :
```php
$user = User::create([
    'prenom' => 'Babacar',
    'email' => 'babacar@example.com',
    'mot_de_passe_hash' => Hash::make('monMotDePasse'),  // ❌ Tu dois hasher manuellement
]);
```

#### ✅ AVEC cast `'hashed'` :
```php
$user = User::create([
    'prenom' => 'Babacar',
    'email' => 'babacar@example.com',
    'mot_de_passe_hash' => 'monMotDePasse',  // ✅ Laravel hash automatiquement !
]);
// Le mot de passe est automatiquement hashé avant d'être stocké
```

**Ce qui se passe :**
- **Tu passes** : `"monMotDePasse"` (string en clair)
- **Stocké en base** : `"$2y$12$..."` (hash bcrypt)
- **Sécurité** : Le mot de passe n'est jamais stocké en clair

---

## 🎯 Résumé des Types de Cast Disponibles

| Cast | Description | Exemple |
|------|-------------|---------|
| `'array'` | JSON ↔ Tableau PHP | `['key' => 'value']` |
| `'json'` | JSON ↔ String | `'{"key":"value"}'` |
| `'date'` | DATE → Carbon (date seule) | `Carbon::parse('2024-01-15')` |
| `'datetime'` | TIMESTAMP → Carbon (date + heure) | `Carbon::parse('2024-01-15 10:30:00')` |
| `'boolean'` | 0/1 ↔ true/false | `true` / `false` |
| `'integer'` | String → Int | `123` |
| `'float'` | String → Float | `12.34` |
| `'decimal:2'` | DECIMAL avec précision | `"85.57"` (2 décimales) |
| `'hashed'` | Hash automatique (bcrypt) | Hash du mot de passe |
| `'encrypted'` | Chiffrement automatique | Données sensibles |

---

## 🔍 Exemple Complet : Document avec Tous les Casts

```php
// Créer un document
$document = Document::create([
    'etudiant_id' => 1,
    'administration_id' => 1,
    'titre' => 'Diplôme Master Informatique',
    'date_emission' => '2024-06-15',  // ✅ Converti en Carbon
    'metadata' => [                    // ✅ Converti en JSON
        'annee' => 2024,
        'mention' => 'Très Bien',
        'credits' => 120
    ]
]);

// Utiliser le document
echo $document->date_emission->format('d/m/Y');  // ✅ "15/06/2024"
echo $document->date_emission->year;             // ✅ 2024
echo $document->metadata['annee'];               // ✅ 2024 (tableau PHP)
echo $document->metadata['mention'];             // ✅ "Très Bien"

// Mettre à jour
$document->update([
    'metadata' => array_merge($document->metadata, [
        'specialite' => 'IA'
    ])
]);
// Laravel convertit automatiquement le nouveau tableau en JSON
```

---

## 💡 Pourquoi Utiliser `casts()` ?

### Avantages :
1. ✅ **Code plus propre** : Pas besoin de `json_encode/decode` partout
2. ✅ **Moins d'erreurs** : Laravel gère les conversions
3. ✅ **Plus lisible** : `$document->metadata['key']` au lieu de `json_decode($document->metadata)['key']`
4. ✅ **Type safety** : Les types sont garantis (boolean, Carbon, etc.)
5. ✅ **Fonctionnalités** : Accès direct aux méthodes Carbon (`format()`, `diffForHumans()`, etc.)

### Sans `casts()` :
```php
// ❌ Code verbeux et sujet aux erreurs
$metadata = json_decode($document->metadata, true);
$date = \Carbon\Carbon::parse($document->date_emission);
$isPublic = $profil->profil_public == 1;
```

### Avec `casts()` :
```php
// ✅ Code simple et direct
$metadata = $document->metadata;  // Déjà un tableau
$date = $document->date_emission;  // Déjà un Carbon
$isPublic = $profil->profil_public;  // Déjà un boolean
```

---

## 🎓 Conclusion

La fonction `casts()` est un **gain de temps et de sécurité**. Elle convertit automatiquement les données entre la base de données (formats bruts) et PHP (types utiles), te permettant d'écrire du code plus simple et plus sûr.

**Règle d'or** : Si tu utilises JSON, dates, ou booléens dans tes modèles, ajoute-les dans `casts()` ! 🚀

