<?php

declare(strict_types=1);

namespace App\Application\Model\File\Read;

use App\Application\Enum\File\FileStatusEnum;
use App\Application\Model\Common\ArrayableModelInterface;
use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class FileViewModel implements ArrayableModelInterface
{
    public function __construct(
        public string $id,
        public string $ownerId,
        public string $originalName,
        public string $mimeType,
        public int $sizeBytes,
        public FileStatusEnum $status,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            id: InputAssertUtils::requiredString($data['id'] ?? null, 'id'),
            ownerId: InputAssertUtils::requiredString($data['ownerId'] ?? null, 'ownerId'),
            originalName: InputAssertUtils::requiredString($data['originalName'] ?? null, 'originalName'),
            mimeType: InputAssertUtils::requiredString($data['mimeType'] ?? null, 'mimeType'),
            sizeBytes: InputAssertUtils::requiredInt($data['sizeBytes'] ?? null, 'sizeBytes'),
            status: InputAssertUtils::requiredEnum($data['status'] ?? null, 'status', FileStatusEnum::class),
            createdAt: InputAssertUtils::requiredString($data['createdAt'] ?? null, 'createdAt'),
            updatedAt: InputAssertUtils::requiredString($data['updatedAt'] ?? null, 'updatedAt'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'ownerId' => $this->ownerId,
            'originalName' => $this->originalName,
            'mimeType' => $this->mimeType,
            'sizeBytes' => $this->sizeBytes,
            'status' => $this->status->value,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
