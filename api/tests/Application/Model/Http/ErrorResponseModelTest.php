<?php

declare(strict_types=1);

namespace App\Tests\Application\Model\Http;

use App\Application\Exception\ErrorCodeEnum;
use App\Application\Exception\User\UserAlreadyExistsException;
use App\Application\Model\Http\ErrorResponseModel;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ErrorResponseModelTest extends TestCase
{
    public function testFromThrowableUsesOwnCodeId(): void
    {
        $model = ErrorResponseModel::fromThrowable(UserAlreadyExistsException::withEmail('john@example.com'));

        $this->assertSame('User with email "john@example.com" already exists.', $model->error);
        $this->assertSame(ErrorCodeEnum::UserAlreadyExists, $model->codeId);
    }

    public function testFromThrowableFallsBackToValidationError(): void
    {
        $model = ErrorResponseModel::fromThrowable(new InvalidArgumentException('Field "name" is required.'));

        $this->assertSame('Field "name" is required.', $model->error);
        $this->assertSame(ErrorCodeEnum::ValidationError, $model->codeId);
    }

    public function testToArrayIsWireShape(): void
    {
        $model = new ErrorResponseModel(
            error: 'Field "name" is required.',
            codeId: ErrorCodeEnum::ValidationError,
        );

        $this->assertSame(['error' => 'Field "name" is required.', 'codeId' => 'VALIDATION_ERROR'], $model->toArray());
    }
}
