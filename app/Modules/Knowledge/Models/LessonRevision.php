<?php

namespace App\Modules\Knowledge\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use App\Modules\Platform\Support\ImmutableWhenFinal;
use Illuminate\Database\Eloquent\Model;
use UnexpectedValueException;

class LessonRevision extends Model
{
    use ImmutableWhenFinal, UsesUuidV7;

    protected $fillable = ['knowledge_unit_id', 'revision', 'state', 'lock_version', 'blocks', 'citations', 'authority_baseline_id', 'content_digest', 'review_decision', 'review_rationale', 'reviewed_by', 'published_by', 'published_at', 'derived_from_revision_id'];

    protected function casts(): array
    {
        return ['blocks' => 'array', 'citations' => 'array', 'published_at' => 'immutable_datetime'];
    }

    protected function wasFinalBeforeUpdate(): bool
    {
        return $this->getOriginal('state') === 'published';
    }

    /** @return list<array<string, mixed>> */
    public function blockList(): array
    {
        $value = $this->getAttribute('blocks');
        if (! is_array($value) || ! array_is_list($value)) {
            throw new UnexpectedValueException('Lesson blocks must be a JSON list.');
        }
        $blocks = [];
        foreach ($value as $block) {
            if (! is_array($block)) {
                throw new UnexpectedValueException('Lesson block must be an object.');
            }
            $blocks[] = $block;
        }

        return $blocks;
    }

    /** @return list<string> */
    public function citationIds(): array
    {
        $value = $this->getAttribute('citations');
        if (! is_array($value) || ! array_is_list($value)) {
            throw new UnexpectedValueException('Lesson citations must be a JSON list.');
        }
        $citations = [];
        foreach ($value as $citation) {
            if (! is_string($citation)) {
                throw new UnexpectedValueException('Lesson citation ID must be a string.');
            }
            $citations[] = $citation;
        }

        return $citations;
    }
}
