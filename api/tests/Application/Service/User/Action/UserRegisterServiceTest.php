<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\User\Action;

use App\Application\Service\User\Action\UserRegisterService;
use App\Application\Model\User\Action\UserRegisterModel;
use App\Application\Model\User\UserLocationModel;
use App\Application\Enum\Notification\NotificationTypeEnum;
use App\Application\Event\User\UserRegisteredEvent;
use App\Application\Exception\User\UserAlreadyExistsException;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\Trait\Common\AssertMessengerTrait;

final class UserRegisterServiceTest extends DatabaseTestCase
{
    use AssertMessengerTrait;

    public function testRegistersNewUser(): void
    {
        $registerUser = $this->bed->get(UserRegisterService::class);

        $result = $registerUser->execute(new UserRegisterModel(
            email: 'john@example.com',
            password: 'secret123',
            fname: 'John',
            lname: 'Doe',
            location: new UserLocationModel(city: 'Berlin'),
            phone: '+79001234567',
        ));

        $this->assertMatchesRegularExpression('/^[0-9a-fA-F-]{36}$/', $result->id);
        $this->assertSame('John', $result->fname);
        $this->assertSame('Doe', $result->lname);
        $this->assertSame('john@example.com', $result->email);
        $this->assertSame('Berlin', $result->location?->city);
        $this->assertSame('+79001234567', $result->phone);
        $this->assertTrue($this->bed->userExistsByEmail('john@example.com'));

        $preference = $this->bed->findNotificationPreference($result->id, NotificationTypeEnum::UserUpdated);
        $this->assertNotNull($preference);
        $this->assertTrue($preference->isEnabled());
        $this->assertTrue($preference->isEmailEnabled());
        $this->assertTrue($preference->isSmsEnabled());

        $this->assertDispatched(
            UserRegisteredEvent::class,
            predicate: static fn (UserRegisteredEvent $event): bool => $event->userId === $result->id,
        );
    }

    public function testRegistersNewUserWithoutCity(): void
    {
        $registerUser = $this->bed->get(UserRegisterService::class);

        $result = $registerUser->execute(new UserRegisterModel(
            email: 'john@example.com',
            password: 'secret123',
            fname: 'John',
            lname: 'Doe',
        ));

        $this->assertNull($result->location);
    }

    public function testRegistersNewUserWithoutNames(): void
    {
        $registerUser = $this->bed->get(UserRegisterService::class);

        $result = $registerUser->execute(new UserRegisterModel(
            email: 'noname@example.com',
            password: 'secret123',
        ));

        $this->assertNull($result->fname);
        $this->assertNull($result->lname);
    }

    public function testThrowsWhenEmailAlreadyExists(): void
    {
        $this->bed->createUser(
            email: 'john@example.com',
            password: 'secret123',
            fname: 'John',
            lname: 'Doe',
        );

        $registerUser = $this->bed->get(UserRegisterService::class);

        $this->expectException(UserAlreadyExistsException::class);

        $registerUser->execute(new UserRegisterModel(
            email: 'john@example.com',
            password: 'secret123',
            fname: 'John',
            lname: 'Doe',
        ));
    }
}
