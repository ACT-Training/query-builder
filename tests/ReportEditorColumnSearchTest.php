<?php

it('has enableColumnSearch property defaulting to true on WithReportBuilder trait', function () {
    $trait = new class
    {
        use ACTTraining\QueryBuilder\Support\Concerns\WithReportBuilder;
    };

    expect($trait->enableColumnSearch)->toBeTrue();
});

it('report editor view contains the column search markup', function () {
    $viewContent = file_get_contents(
        __DIR__.'/../resources/views/report-editor.blade.php'
    );

    expect($viewContent)
        ->toContain('x-model="search"')
        ->toContain('placeholder="Search columns..."')
        ->toContain('enableColumnSearch')
        ->toContain("search: ''");
});

it('report editor view wraps search input in enableColumnSearch conditional', function () {
    $viewContent = file_get_contents(
        __DIR__.'/../resources/views/report-editor.blade.php'
    );

    expect($viewContent)
        ->toContain('@if($this->enableColumnSearch)')
        ->toContain('.toLowerCase().includes(search.toLowerCase())');
});

it('report editor view adds x-show filtering to individual column checkboxes', function () {
    $viewContent = file_get_contents(
        __DIR__.'/../resources/views/report-editor.blade.php'
    );

    expect($viewContent)
        ->toContain("x-show=\"'{{ e(\$column['label']) }}'.toLowerCase().includes(search.toLowerCase())\"");
});

it('report editor view adds x-show filtering to section wrappers', function () {
    $viewContent = file_get_contents(
        __DIR__.'/../resources/views/report-editor.blade.php'
    );

    expect($viewContent)
        ->toContain('.some(label => label.toLowerCase().includes(search.toLowerCase()))');
});
