<?php

declare(strict_types=1);

namespace App\Application\Model\Http;

use App\Application\Exception\CodedExceptionInterface;
use App\Application\Exception\ErrorCodeEnum;
use App\Application\Model\Common\ArrayableModelInterface;
use App\Application\Utils\Wire\InputAssertUtils;
use InvalidArgumentException;
use Throwable;

final readonly class ErrorResponseModel implements ArrayableModelInterface
{
    public function __construct(
        public string $error,
        public ErrorCodeEnum $codeId,
    ) {
    }

    /**
     * Формирует конверт ошибки из исключения: `codeId` own кода — у
     * `CodedExceptionInterface`, всё прочее — `VALIDATION_ERROR`.
     */
    public static function fromThrowable(Throwable $exception): self
    {
        return new self(
            error: $exception->getMessage(),
            codeId: $exception instanceof CodedExceptionInterface ? $exception->getCodeId() : ErrorCodeEnum::ValidationError,
        );
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
