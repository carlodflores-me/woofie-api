<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PetAttachment extends Model
{
    protected $fillable = ['pet_id', 'url', 'type'];

    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }
}