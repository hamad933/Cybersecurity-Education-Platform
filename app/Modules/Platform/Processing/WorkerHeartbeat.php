<?php

namespace App\Modules\Platform\Processing;

use Illuminate\Database\Eloquent\Model;

class WorkerHeartbeat extends Model
{
    protected $primaryKey = 'worker_identifier';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['worker_identifier', 'provider', 'last_seen_at', 'ttl_seconds'];

    protected function casts(): array
    {
        return ['last_seen_at' => 'immutable_datetime', 'ttl_seconds' => 'integer'];
    }

    public function status(): string
    {
        if (! $this->last_seen_at) {
            return 'UNKNOWN';
        }
        
        $secondsSinceSeen = $this->last_seen_at->diffInSeconds(now(), false);
        
        if ($secondsSinceSeen < 0) {
            return 'UNKNOWN';
        }
        
        if ($secondsSinceSeen <= $this->ttl_seconds) {
            return 'HEALTHY';
        }
        
        if ($secondsSinceSeen <= ($this->ttl_seconds * 3)) {
            return 'STALE';
        }
        
        return 'DOWN';
    }
}
