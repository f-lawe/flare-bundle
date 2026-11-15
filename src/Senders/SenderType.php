<?php

declare(strict_types=1);

namespace Flawe\FlareBundle\Senders;

use Spatie\FlareClient\Senders\CurlSender;
use Spatie\FlareClient\Senders\DaemonSender;
use Spatie\FlareClient\Senders\GuzzleSender;
use Spatie\FlareClient\Senders\NullSender;
use Spatie\FlareClient\Senders\Sender;

enum SenderType: string
{
    case CURL   = 'curl';
    case DAEMON = 'daemon';
    case GUZZLE = 'guzzle';
    case NULL   = 'null';

    /**
     * @return class-string<Sender>
     */
    public function senderClass(): string
    {
        return match ($this) {
            self::CURL   => CurlSender::class,
            self::DAEMON => DaemonSender::class,
            self::GUZZLE => GuzzleSender::class,
            self::NULL   => NullSender::class,
        };
    }
}
