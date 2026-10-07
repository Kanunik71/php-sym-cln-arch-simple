<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Notification;

use App\Application\Service\Notification\Action\NotificationUpdateService;
use App\Application\Model\Notification\Action\UpdateNotificationPreferencesModel;
use App\Application\Service\Notification\Query\NotificationQueryService;
use App\Application\Exception\Notification\NotificationPreferenceNotFoundException;
use App\Presentation\Controller\BaseController;
use App\Shared\Utils\Http\ErrorResponseUtils;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users/me/notification-preferences', name: 'notification_preferences_')]
#[IsGranted('ROLE_USER')]
final class NotificationPreferenceController extends BaseController
{
    public function __construct(
        private readonly NotificationQueryService $getNotificationPreferences,
        private readonly NotificationUpdateService $updateNotificationPreferences,
    ) {
    }

    #[Route('', name: 'get', methods: ['GET'])]
    public function get(): JsonResponse
    {
        $preferences = $this->getNotificationPreferences->execute(
            currentUserId: $this->getCurrentUserId(),
        );

        return $this->json($preferences);
    }

    #[Route('', name: 'update', methods: ['PUT'])]
    public function update(Request $request): JsonResponse
    {
        try {
            $preferences = $this->updateNotificationPreferences->execute(
                model: UpdateNotificationPreferencesModel::fromArray($this->requestPayload($request)),
                currentUserId: $this->getCurrentUserId(),
            );
        } catch (NotificationPreferenceNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (InvalidArgumentException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_BAD_REQUEST);
        }

        return $this->json($preferences);
    }
}
