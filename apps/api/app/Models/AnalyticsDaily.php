<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsDaily extends Model
{
    protected $table = 'analytics_daily';

    protected $fillable = [
        'date', 'jobs_found', 'strong_matches', 'reviewed', 'proposals', 'replies', 'interviews', 'hires',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }
}
