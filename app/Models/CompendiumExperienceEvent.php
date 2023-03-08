<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompendiumExperienceEvent extends Model
{
    use HasFactory;

    protected $table = 'tb_compendium_experience_events';
    protected $guarded = ['id'];
    protected $fillable = ['compendium_experience_id', 'api_key', 'delta'];
}
