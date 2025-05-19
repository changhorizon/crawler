<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . DIRECTORY_SEPARATOR . 'src',
        __DIR__ . DIRECTORY_SEPARATOR . 'tests',
    ])
    ->name('*.php')
    ->append([
        __DIR__ . DIRECTORY_SEPARATOR . '.php-cs-fixer.dist.php',
    ]);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        // ==================== 基礎規範 ====================
        '@PSR12'               => true,
        '@PHP84Migration'      => true,
        'declare_strict_types' => true,
        'strict_param'         => true,

        // ==================== 類型系統 ====================
        'void_return'                                      => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'phpdoc_to_param_type'                             => true,
        'phpdoc_to_property_type'                          => true,
        'phpdoc_to_return_type'                            => true,
        'no_superfluous_phpdoc_tags'                       => ['allow_mixed' => false],

        // ==================== 現代語法 ====================
        'array_syntax'               => ['syntax' => 'short'],
        'list_syntax'                => ['syntax' => 'short'],
        'modernize_types_casting'    => true,
        'get_class_to_class_keyword' => true,
        'ternary_to_null_coalescing' => true,

        // ==================== 代碼風格 ====================
        'single_quote' => true,
        'concat_space' => ['spacing' => 'one'],
        'phpdoc_align' => [
            'align' => 'vertical',
            'tags'  => ['param', 'return', 'throws', 'var', 'type'],
        ],
        'phpdoc_indent'     => true,
        'phpdoc_separation' => true,
        'phpdoc_order'      => [
            'order' => ['param', 'throws', 'return', 'var', 'type'],
        ],
        'phpdoc_trim'                    => true,
        'phpdoc_scalar'                  => true,
        'phpdoc_single_line_var_spacing' => true,
        'blank_line_before_statement'    => [
            'statements' => ['return', 'throw', 'try', 'if', 'foreach', 'while', 'do'],
        ],
        'no_unused_imports'            => true,
        'fully_qualified_strict_types' => true,
        'global_namespace_import'      => [
            'import_classes'   => true,
            'import_functions' => true,
            'import_constants' => true,
        ],
        'ordered_interfaces' => true,
        'ordered_imports'    => [
            'sort_algorithm' => 'alpha',
            'imports_order'  => ['class', 'function', 'const'],
        ],

        // ==================== 多行結構 ====================
        'array_indentation'      => true,
        'binary_operator_spaces' => [
            'default'   => 'align_single_space_minimal',
            'operators' => ['=>' => 'align_single_space'],
        ],
        'trailing_comma_in_multiline' => [
            'elements'      => ['arrays', 'arguments', 'parameters', 'match'],
            'after_heredoc' => true,
        ],
        'method_argument_space' => [
            'on_multiline'                     => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => false,
        ],

        // ==================== 安全規則 ====================
        'no_alias_functions'       => true,
        'no_mixed_echo_print'      => ['use' => 'echo'],
        'no_php4_constructor'      => true,
        'no_unneeded_final_method' => true,

        // ==================== 屬性控制 ====================
        'class_attributes_separation' => [
            'elements' => ['method' => 'one', 'property' => 'one', 'trait_import' => 'none'],
        ],
        'attribute_empty_parentheses'             => true,
        'declare_parentheses'                     => true,
        'single_space_around_construct'           => true,
        'control_structure_continuation_position' => true,
        'control_structure_braces'                => true,
        'braces_position'                         => true,
        'statement_indentation'                   => true,
        'no_multiple_statements_per_line'         => true,
        'no_extra_blank_lines'                    => true,
    ])
    ->setFinder($finder);
