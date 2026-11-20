<?php

namespace ACTTraining\QueryBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|string $user_id
 * @property string $table_key
 * @property string $name
 * @property array $state
 * @property bool $is_shared
 * @property string|null $source
 */
class SavedViewRecord extends Model
{
    protected $table = 'query_builder_saved_views';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'state' => 'array',
            'is_shared' => 'boolean',
        ];
    }

    public function preferences(): HasMany
    {
        return $this->hasMany(SavedViewPreference::class, 'saved_view_id');
    }
}
