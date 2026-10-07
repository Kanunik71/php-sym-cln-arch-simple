<?php

declare(strict_types=1);

namespace App\Application\Model\Common;

/**
 * Single wire Model: HTTP/query and response array ↔ typed Application Model.
 * Not for criteria list / page-from-port — see ArrayableModelListInterface.
 */
interface ArrayableModelInterface
{
    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static;

    /** @return array<string, mixed> */
    public function toArray(): array;
}
