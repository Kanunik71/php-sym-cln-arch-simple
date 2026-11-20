<?php

declare(strict_types=1);

namespace App\Application\ValueObject\Common;

use InvalidArgumentException;

final class MoneyVo
{
    /** Column shape of assets.price: decimal(PRECISION, SCALE). */
    public const PRECISION = 10;

    public const SCALE = 2;

    /** Max value for decimal(10, 2): 8 digits before the decimal point. */
    public const MAX_AMOUNT = 99_999_999.99;

    private readonly float $value;

    /**
     * Тексты исключений — часть wire-контракта `400` эндпоинтов ассетов,
     * менять их можно только вместе со спекой и тестами.
     */
    public function __construct(float $value)
    {
        if (!is_finite($value) || $value < 0) {
            throw new InvalidArgumentException('Price must be a non-negative number with up to 2 decimal places.');
        }

        $normalized = round($value, self::SCALE);

        if ($normalized > self::MAX_AMOUNT) {
            throw new InvalidArgumentException(sprintf('Price must not exceed %s.', number_format(self::MAX_AMOUNT, self::SCALE, '.', '')));
        }

        $this->value = $normalized;
    }

    public static function fromString(string $amount): self
    {
        return new self((float) $amount);
    }

    public function toFloat(): float
    {
        return $this->value;
    }

    public function toString(): string
    {
        return number_format($this->value, self::SCALE, '.', '');
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
