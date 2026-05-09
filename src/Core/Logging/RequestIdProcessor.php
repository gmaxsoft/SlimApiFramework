<?php

declare(strict_types=1);

namespace App\Core\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Procesor Monolog: dopisuje pole `request_id` do każdego wpisu, gdy `RequestContext` zawiera identyfikator.
 */
final class RequestIdProcessor implements ProcessorInterface
{
    /**
     * Wzbogaca rekord logu o `request_id`, jeśli jest dostępny w kontekście żądania.
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        $rid = RequestContext::getRequestId();
        if ($rid !== null && $rid !== '') {
            $record->extra['request_id'] = $rid;
        }

        return $record;
    }
}
