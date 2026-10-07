<?php

declare(strict_types=1);

namespace App\Application\Model\Http;

use App\Application\Model\Common\ArrayableModelInterface;
use App\Application\Exception\ErrorCodeEnum;
use App\Shared\Utils\Asserts\InputAssertUtils;
use InvalidArgumentException;

final readonly class ErrorResponseModel implements ArrayableModelInterface
{
    public function __construct(
        public string $error,
        public ErrorCodeEnum $codeId,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        $codeRaw = InputAssertUtils::requiredString($data['codeId'] ?? null, 'codeId');
        $codeId = ErrorCodeEnum::tryFrom($codeRaw);

        if ($codeId === null) {
            throw new InvalidArgumentException(sprintf('Unknown error codeId "%s".', $codeRaw));
        }

        return new self(
            error: InputAssertUtils::requiredString($data['error'] ?? null, 'error'),
            codeId: $codeId,
        );
    }

    /** @return array{error: string, codeId: string} */
    public function toArray(): array
    {
        return [
            'error' => $this->error,
            'codeId' => $this->codeId->value,
        ];
    }
}
