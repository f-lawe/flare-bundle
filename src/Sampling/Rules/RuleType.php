<?php

declare(strict_types=1);

namespace Flawe\FlareBundle\Sampling\Rules;

enum RuleType: string
{
    case URL     = 'url';
    case PATH    = 'path';
    case ROUTE   = 'route';
    case COMMAND = 'command';
    case JOB     = 'job';
}
