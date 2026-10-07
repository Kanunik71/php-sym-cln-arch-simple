<?php

declare(strict_types=1);

namespace App\Application\Model\Notification\Action;

use App\Application\Model\Common\ArrayableModelInterface;
use App\Application\Model\Notification\Read\NotificationPreferenceViewModel;
use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class UpdateNotificationPreferencesModel implements ArrayableModelInterface
{
    /**
     * @param list<NotificationPreferenceViewModel> $preferences
     */
    public function __construct(
        public array $preferences,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            preferences: InputAssertUtils::nestedModelList(
                $data['preferences'] ?? null,
                'preferences',
                NotificationPreferenceViewModel::fromArray(...),
            ),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'preferences' => array_map(
                static fn (NotificationPreferenceViewModel $item): array => $item->toArray(),
                $this->preferences,
            ),
        ];
    }
}
