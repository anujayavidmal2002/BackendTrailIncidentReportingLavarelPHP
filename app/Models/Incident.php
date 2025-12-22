<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    use HasUuids;

    protected $table = 'incidents';

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'type',
        'description',
        'location',
        'locationText',
        'latitude',
        'longitude',
        'severity',
        'date',
        'time',
        'status',
        'photos',
        'photoUrl',
        'photoKey',
        'reportedBy',
    ];

    protected $casts = [
        'photos' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'createdAt' => 'datetime',
        'updatedAt' => 'datetime',
    ];

    // Constants for validation
    const SEVERITIES = ['Low', 'Medium', 'High'];
    const STATUSES = ['Open', 'In Progress', 'Resolved', 'Closed'];

    // Query scopes
    public function scopeFilterBySeverity($query, $severity)
    {
        if ($severity) {
            return $query->where('severity', $severity);
        }
        return $query;
    }

    public function scopeFilterByType($query, $type)
    {
        if ($type) {
            return $query->where('type', $type);
        }
        return $query;
    }

    public function scopeFilterByStatus($query, $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }
        return $query;
    }

    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where('description', 'ILIKE', "%{$search}%")
                        ->orWhere('locationText', 'ILIKE', "%{$search}%");
        }
        return $query;
    }
}
