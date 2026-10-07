<?php

declare(strict_types=1);

namespace App\Application\Exception;

enum ErrorCodeEnum: string
{
    case UserAlreadyExists = 'USER_ALREADY_EXISTS';
    case UserNotFound = 'USER_NOT_FOUND';
    case UnauthorizedUserAccess = 'UNAUTHORIZED_USER_ACCESS';
    case InvalidCredentials = 'INVALID_CREDENTIALS';
    case TaskNotFound = 'TASK_NOT_FOUND';
    case UnauthorizedTaskAccess = 'UNAUTHORIZED_TASK_ACCESS';
    case InvalidTaskStatusTransition = 'INVALID_TASK_STATUS_TRANSITION';
    case AssetNotFound = 'ASSET_NOT_FOUND';
    case UnauthorizedAssetAccess = 'UNAUTHORIZED_ASSET_ACCESS';
    case FileNotFound = 'FILE_NOT_FOUND';
    case UnauthorizedFileAccess = 'UNAUTHORIZED_FILE_ACCESS';
    case InvalidFileState = 'INVALID_FILE_STATE';
    case TaskGroupNotFound = 'TASK_GROUP_NOT_FOUND';
    case UnauthorizedTaskGroupAccess = 'UNAUTHORIZED_TASK_GROUP_ACCESS';
    case NotificationPreferenceNotFound = 'NOTIFICATION_PREFERENCE_NOT_FOUND';
    case ValidationError = 'VALIDATION_ERROR';
}
