<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'recruteur_id',
        'etudiant_id',
        'offre_id',
        'matching_id',
        'last_message_at',
        'recruteur_has_unread',
        'etudiant_has_unread',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'recruteur_has_unread' => 'boolean',
        'etudiant_has_unread' => 'boolean',
    ];

    public function recruteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recruteur_id');
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'etudiant_id');
    }

    public function offre(): BelongsTo
    {
        return $this->belongsTo(Offre::class);
    }

    public function matching(): BelongsTo
    {
        return $this->belongsTo(Matching::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }
}

