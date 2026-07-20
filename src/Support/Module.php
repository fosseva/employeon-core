<?php

namespace Employeon\Support;

use InvalidArgumentException;

final class Module
{
    public function __construct(
        public readonly string $handle,
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly bool $enabled = true,
        public readonly array $meta = [],
    ) {
        if ($handle === '') {
            throw new InvalidArgumentException('Employeon module handles cannot be empty.');
        }
    }
}
