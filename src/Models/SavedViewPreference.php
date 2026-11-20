<?php

namespace ACTTraining\QueryBuilder\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One user's favourite and default flags for a saved view.
 *
 * @property int $saved_view_id
 * @property int|string $user_id
 * @property bool $is_favourite
 * @property bool $is_default
 */
class SavedViewPreference extends Model
{
    protected $table = 'query_builder_saved_view_user';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_favourite' => 'boolean',
            'is_default' => 'boolean',
        ];
    }
}
