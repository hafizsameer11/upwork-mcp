<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkillPerformance extends Model
{
    protected $table = 'skill_performance';

    protected $fillable = ['skill', 'strategy', 'sent', 'replies', 'hires'];
}
