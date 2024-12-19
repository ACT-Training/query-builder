<?php

namespace ACTTraining\QueryBuilder\Support\Conditions;

use Illuminate\Support\Str;

/**
 * Extends the BaseCondition class and adds an extra $options property
 */
class EnumCondition extends BaseCondition
{
    public string $inputType = 'enum';

    /**
     * The options available for the enum condition.
     */
    public array $options;

    /**
     * Constructor for EnumCondition
     * It initializes the $options property along with the properties inherited from BaseCondition.
     *
     * @param  string  $key
     * @param  string  $label
     */
    public function __construct($key, $label, array $options = [])
    {
        parent::__construct($key, $label);
        $this->options = $options;
    }

    /**
     * Static make method to create an instance of EnumCondition.
     *
     * @param  string  $label
     * @param  string|null  $key
     */
    public static function make($label, $key = null, array $options = []): static
    {
        if (is_null($key)) {
            $key = Str::snake($label);
        }

        return new static($key, $label, $options);
    }

    public function operations(): array
    {
        return [
            'equals' => 'is',
            'not_equals' => 'is not',
        ];
    }

    /**
     * Get the option array.
     */
    public function options(): array
    {
        return $this->options;
    }

    /**
     * Convert the EnumCondition instance to an array.
     * This overrides the toArray method to include options in the array.
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'options' => $this->options,
        ]);
    }
}
