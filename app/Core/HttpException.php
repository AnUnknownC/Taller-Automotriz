<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/** Corta la petición con un código HTTP (403, 404, 405, 419...). */
final class HttpException extends RuntimeException
{
    private int $status;

    public function __construct(int $status, string $message = '')
    {
        parent::__construct($message, $status);
        $this->status = $status;
    }

    public function status(): int
    {
        return $this->status;
    }
}
