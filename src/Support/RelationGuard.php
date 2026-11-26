<?php

namespace ACTTraining\QueryBuilder\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Tells whether a name is a relationship of a model, before the package calls a
 * method by that name. Eloquent's own isRelation() accepts any method of the model.
 *
 * This is a second check only. The main check is the allow-list: a table only uses
 * keys that it defines itself (its sortable columns, conditions and report columns).
 * It still accepts an app's own public method with no return type, so never use it
 * as the only check on a name that comes from the browser.
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

        // A method that a Laravel trait (such as SoftDeletes) adds to the model is a
        // relationship only when it says so.
        if (self::comesFromLaravelTrait($model, $name)) {
            return $type instanceof ReflectionNamedType
                && ! $type->isBuiltin()
                && is_a($type->getName(), Relation::class, true);
        }

        if ($type instanceof ReflectionNamedType) {
            return ! $type->isBuiltin() && is_a($type->getName(), Relation::class, true);
        }

        // No return type, or a union type: decide when the method has run.
        return true;
    }

    private static function comesFromLaravelTrait(Model $model, string $name): bool
    {
        foreach (class_uses_recursive($model) as $trait) {
            if (str_starts_with($trait, 'Illuminate\\') && method_exists($trait, $name)) {
                return true;
            }
        }

        return false;
    }
}
