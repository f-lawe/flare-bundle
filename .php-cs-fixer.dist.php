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
    ])
    ->setFinder(
        (new Finder())
            ->in(__DIR__)
    )
;
