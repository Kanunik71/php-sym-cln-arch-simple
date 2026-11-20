<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Shared\Utils\Asserts\InputAssertUtils;
use InvalidArgumentException;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class BaseController extends AbstractController
{
    protected function getCurrentUserId(): string
    {
        $user = $this->getUser();

        if (!$user instanceof UserInterface) {
            throw new LogicException('Authenticated user is missing.');
        }

        return $user->getUserIdentifier();
    }

    /** @return array<string, mixed> */
    protected function requestPayload(Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            throw new InvalidArgumentException('Invalid JSON payload.');
        }

        return InputAssertUtils::stringKeyedArray($data, 'payload');
    }
}
