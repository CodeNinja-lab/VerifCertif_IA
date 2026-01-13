<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OffreView extends Model
{
    use HasFactory;

    protected $fillable = [
        'offre_id',
        'user_id',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public $timestamps = false;

    /**
     * Relations
     */
    public function offre()
    {
        return $this->belongsTo(Offre::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
