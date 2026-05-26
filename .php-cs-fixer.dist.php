<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        'method_chaining_indentation' => false,
        'control_structure_continuation_position' => [
            'position' => 'next_line'
        ],
    ])
    ->setFinder(
        (new Finder())
            ->in(__DIR__)
    )
;
