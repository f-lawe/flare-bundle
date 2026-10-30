<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PhpCsFixer'            => true,
        '@PhpCsFixer:risky'      => true,
        'binary_operator_spaces' => [
            'default' => 'align_single_space_minimal',
        ],
        'concat_space' => [
            'spacing' => 'one',
        ],
        'control_structure_continuation_position' => [
            'position' => 'next_line',
        ],
        'declare_strict_types' => [
            'strategy' => 'enforce',
        ],
        'method_chaining_indentation' => false,
    ])
    ->setFinder(
        (new Finder())
            ->in(__DIR__)
    )
;
