<?php

namespace ACTTraining\QueryBuilder\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Tells whether a name is a relationship of a model, before the package calls a
 * method by that name. Eloquent's own isRelation() accepts any method of the model.
 */
class RelationGuard
{
    public static function isRelation(Model $model, string $name): bool
    {
        if ($name === '') {
            return false;
        }

        if (! method_exists($model, $name)) {
            // Relationships added with Model::resolveRelationUsing().
            return (bool) $model->relationResolver($model::class, $name);
        }

        $method = new ReflectionMethod($model, $name);

        if (! $method->isPublic() || $method->isStatic() || $method->getNumberOfRequiredParameters() > 0) {
            return false;
        }

        // Methods of Eloquent's Model and Laravel's other base models are never
        // the app's relationships.
        if (str_starts_with($method->getDeclaringClass()->getName(), 'Illuminate\\')) {
            return false;
        }

        $type = $method->getReturnType();

        if ($type instanceof ReflectionNamedType) {
            return ! $type->isBuiltin() && is_a($type->getName(), Relation::class, true);
        }

        // No return type, or a union type: decide when the method has run.
        return true;
    }
}
