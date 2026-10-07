<?php

declare(strict_types=1);

namespace App\Application\Model\Notification\Read;

use App\Application\Enum\Notification\NotificationChannelEnum;
use App\Application\Enum\Notification\NotificationTypeEnum;
use App\Application\Model\Common\ArrayableModelInterface;
use App\Shared\Utils\Asserts\InputAssertUtils;
use App\Shared\Utils\EnumUtils;

final readonly class NotificationPreferenceViewModel implements ArrayableModelInterface
{
    /**
     * @param list<NotificationChannelEnum> $channels
     */
    public function __construct(
        public NotificationTypeEnum $type,
        public bool $enabled,
        public array $channels,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            type: InputAssertUtils::requiredEnum($data['type'] ?? null, 'type', NotificationTypeEnum::class),
            enabled: InputAssertUtils::requiredBool($data['enabled'] ?? null, 'enabled'),
            channels: InputAssertUtils::enumList($data['channels'] ?? null, 'channels', NotificationChannelEnum::class),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'enabled' => $this->enabled,
            'channels' => EnumUtils::values($this->channels),
        ];
    }
}
