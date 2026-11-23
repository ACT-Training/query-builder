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
     * Whether people may pick several choices, with "is any of" and "is none of".
     */
    public bool $allowsMany = false;

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

    /**
     * Lets people pick several choices: adds "is any of" and "is none of" to "is" and "is not".
     */
    public function multiple(bool $allowsMany = true): static
    {
        $this->allowsMany = $allowsMany;
        $this->operations = $this->operations();

        return $this;
    }

    public function operations(): array
    {
        $operations = [
            'equals' => 'is',
            'not_equals' => 'is not',
        ];

        if ($this->allowsMany) {
            $operations['is_any_of'] = 'is any of';
            $operations['is_none_of'] = 'is none of';
        }

        return $operations;
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
