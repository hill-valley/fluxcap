<?php

declare(strict_types=1);

return (new PhpCsFixer\Config())
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    ->setRules([
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        '@PHP81Migration' => true,
        '@PHP80Migration:risky' => true,
        '@PHPUnit84Migration:risky' => true,

        'blank_line_before_statement' => ['statements' => [
            'break',
            'continue',
            'declare',
            'return',
            'throw',
        ]],
        'braces' => ['allow_single_line_closure' => false],
        'comment_to_phpdoc' => false,
        'explicit_string_variable' => false,
        'final_class' => true,
        'global_namespace_import' => [
            'import_constants' => true,
            'import_functions' => true,
            'import_classes' => false,
        ],
        'list_syntax' => ['syntax' => 'short'],
        'no_extra_blank_lines' => [
            'tokens' => [
                'break',
                // 'case',
                'continue',
                'curly_brace_block',
                'default',
                'extra',
                'parenthesis_brace_block',
                'return',
                'square_brace_block',
                'switch',
                'throw',
                'use',
            ],
        ],
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
        'ordered_class_elements' => ['order' => [
            'use_trait',
            'case',
            'constant_public',
            'constant_protected',
            'constant_private',
            'property_public_static',
            'property_protected_static',
            'property_private_static',
            'property_public',
            'property_protected',
            'property_private',
            'construct',
            'destruct',
            'phpunit',
            'method_public',
            'method_protected',
            'method_private',
        ]],
        'ordered_imports' => ['imports_order' => [
            'class',
            'const',
            'function',
        ]],
        'php_unit_test_case_static_method_calls' => ['call_type' => 'self'],
        'phpdoc_add_missing_param_annotation' => false,
        'phpdoc_align' => false,
        'phpdoc_to_comment' => false,
        'phpdoc_types_order' => [
            'sort_algorithm' => 'none',
            'null_adjustment' => 'always_last',
        ],
    ])
    ->setFinder(
        (new PhpCsFixer\Finder())
            ->in(__DIR__.'/src')
            ->in(__DIR__.'/tests')
            ->append([__FILE__])
    )
;
