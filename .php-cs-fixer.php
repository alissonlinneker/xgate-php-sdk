<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        '@PHP84Migration' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'trailing_comma_in_multiline' => true,
        'phpdoc_scalar' => true,
        'phpdoc_var_without_name' => true,
        'declare_strict_types' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'single_quote' => true,
        'no_empty_statement' => true,
        'no_unneeded_control_parentheses' => true,
        'no_unneeded_curly_braces' => true,
        'no_superfluous_elseif' => true,
        'no_useless_else' => true,
        'phpdoc_align' => true,
        'phpdoc_order' => true,
        'phpdoc_separation' => true,
        'phpdoc_trim' => true,
        'blank_line_after_namespace' => true,
        'blank_line_after_opening_tag' => true,
        'compact_nullable_typehint' => true,
        'concat_space' => ['spacing' => 'one'],
        'new_with_braces' => true,
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
        'return_type_declaration' => true,
        'void_return' => true,
        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one',
                'property' => 'one',
            ],
        ],
    ])
    ->setFinder($finder);