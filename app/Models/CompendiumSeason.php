<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompendiumSeason extends Model
{
    use HasFactory;

    protected $table = 'xp_compendium_seasons';
    protected $guarded = ['id'];
    protected $fillable = ['name', 'start_date', 'end_date'];
}
