<?php

declare(strict_types=1);

namespace Employeon\Support;

final readonly class SavedViewOwner
{
    public function __construct(
        public string $type,
        public int $id,
    ) {}
}
