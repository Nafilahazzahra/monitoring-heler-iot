<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorReading extends Model
{
    protected $fillable = [
        'recorded_at',
        'temperature',
        'flow_rate',
        'flow_velocity',
        'flow_status',
        'water_level',
        'vibration_status',
        'status',
        'raw_payload',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'raw_payload' => 'array',
    ];
}
