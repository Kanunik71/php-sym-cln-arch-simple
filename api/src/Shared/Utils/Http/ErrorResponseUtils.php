<?php

declare(strict_types=1);

namespace App\Shared\Utils\Http;

use App\Application\Model\Http\ErrorResponseModel;
use App\Application\Exception\CodedExceptionInterface;
use App\Application\Exception\ErrorCodeEnum;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

final class ErrorResponseUtils
{
    public static function json(Throwable $exception, int $status): JsonResponse
    {
        return new JsonResponse(self::body($exception)->toArray(), $status);
    }

    public static function body(Throwable $exception): ErrorResponseModel
    {
        return new ErrorResponseModel(
            error: $exception->getMessage(),
            codeId: self::resolveCodeId($exception),
        );
    }

    private static function resolveCodeId(Throwable $exception): ErrorCodeEnum
    {
        if ($exception instanceof CodedExceptionInterface) {
            return $exception->getCodeId();
        }

        return ErrorCodeEnum::ValidationError;
    }
}
