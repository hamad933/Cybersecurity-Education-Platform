<?php

namespace App\Modules\Enterprise\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;

class ImprovementProposal extends Model
{
    use UsesUuidV7;

    protected $fillable = ['enterprise_baseline_revision_id', 'scenario_run_id', 'proposal', 'status'];

    protected function casts(): array
    {
        return ['proposal' => 'array'];
    }
}
