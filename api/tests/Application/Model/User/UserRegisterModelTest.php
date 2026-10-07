<?php

declare(strict_types=1);

namespace App\Tests\Application\Model\User;

use App\Application\Model\User\Action\UserRegisterModel;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class UserRegisterModelTest extends TestCase
{
    public function testRejectsMissingEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "email" is required.');

        UserRegisterModel::fromArray([
            'password' => 'secret123',
        ]);
    }
}
