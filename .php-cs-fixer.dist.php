<?php

declare(strict_types=1);

/*
 * This file is part of ocubom/twig-svg-extension
 *
 * © Oscar Cubo Medina <https://ocubom.github.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$header = <<<'EOF'
This file is part of ocubom/twig-svg-extension

© Oscar Cubo Medina <https://ocubom.github.io>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

if (!file_exists(__DIR__.'/src') && !file_exists(__DIR__.'/tests')) {
    exit(0);
}

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setFinder(
        (new PhpCsFixer\Finder())
            ->in([
                __DIR__.'/src',
                __DIR__.'/tests',
            ])
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
            ->ignoreVCSIgnored(true)
            ->append([__FILE__])
    )
    ->setRules([
        // Rulesets
        '@Symfony' => true,
        '@Symfony:risky' => false,
        // Alias
        'modernize_strpos' => true,
        // Class Notation
        'protected_to_private' => false,
        'visibility_required' => ['elements' => ['method', 'property']],
        // Comment
        'header_comment' => ['header' => $header, 'separate' => 'both'],
        // Constant Notation
        'native_constant_invocation' => ['strict' => false],
        // Function Notation
        'nullable_type_declaration_for_default_null_value' => ['use_nullable_type_declaration' => false],
        'single_line_throw' => false,
        // PHPdoc
        'no_superfluous_phpdoc_tags' => ['remove_inheritdoc' => true],
    ])
    ->setCacheFile(tempnam(sys_get_temp_dir(), 'php-cs-fixer'))
;
