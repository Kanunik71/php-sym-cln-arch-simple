<?php

declare(strict_types=1);

namespace App\Application\Model\Common;

use App\Shared\Utils\Asserts\InputAssertUtils;
use InvalidArgumentException;

final readonly class PaginationModel implements ArrayableModelInterface
{
    public const DEFAULT_PAGE = 1;
    public const DEFAULT_PER_PAGE = 20;
    public const MAX_PER_PAGE = 100;

    public function __construct(
        public int $page,
        public int $perPage,
    ) {
        if ($this->page < 1) {
            throw new InvalidArgumentException('Field "page" must be >= 1.');
        }

        if ($this->perPage < 1 || $this->perPage > self::MAX_PER_PAGE) {
            throw new InvalidArgumentException(sprintf('Field "perPage" must be between 1 and %d.', self::MAX_PER_PAGE));
        }
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        $page = InputAssertUtils::optionalInt($data['page'] ?? null, 'page') ?? self::DEFAULT_PAGE;
        $perPage = InputAssertUtils::optionalInt($data['perPage'] ?? null, 'perPage') ?? self::DEFAULT_PER_PAGE;

        return new self(page: $page, perPage: $perPage);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'perPage' => $this->perPage,
        ];
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
