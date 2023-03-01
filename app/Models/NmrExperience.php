<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NmrExperience extends Model
{
    use HasFactory;

    protected $table = 'tb_nmr_experiences';
    protected $guarded = ['id'];
    protected $fillable = ['komo_username', 'total_experience'];
}
