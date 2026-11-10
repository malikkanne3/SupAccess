<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categorie extends Model
{
    protected $table = 'categories';

    protected $fillable = ['nom', 'description'];

    public function equipements(): HasMany
    {
        return $this->hasMany(Equipement::class, 'categorie_id');
    }
}
