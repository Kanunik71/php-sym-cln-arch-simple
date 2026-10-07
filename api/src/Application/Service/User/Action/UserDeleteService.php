<?php

declare(strict_types=1);

namespace App\Application\Service\User\Action;

use App\Application\Cache\Task\TaskCache;
use App\Application\Model\User\UserModel;
use App\Application\Policy\User\UserAccessPolicy;
use App\Application\Port\Asset\AssetRepositoryInterface;
use App\Application\Port\File\FileRepositoryInterface;
use App\Application\Port\Notification\NotificationPreferenceRepositoryInterface;
use App\Application\Port\Task\TaskRepositoryInterface;
use App\Application\Port\TransactionManagerInterface;
use App\Application\Port\User\UserRepositoryInterface;
use App\Application\Service\Asset\Lifecycle\AssetLifecycleService;
use App\Application\Service\File\Lifecycle\FileLifecycleService;
use App\Application\Service\Task\Lifecycle\TaskUserListService;

final readonly class UserDeleteService
{
    public function __construct(
        private UserAccessPolicy $userAccessPolicy,
        private UserRepositoryInterface $userRepository,
        private AssetRepositoryInterface $assetRepository,
        private TaskRepositoryInterface $taskRepository,
        private NotificationPreferenceRepositoryInterface $preferenceRepository,
        private FileRepositoryInterface $fileRepository,
        private AssetLifecycleService $assetLifecycleService,
        private FileLifecycleService $fileLifecycleService,
        private TransactionManagerInterface $transactionManager,
        private TaskCache $taskCache,
    ) {
    }

    public function execute(string $userId, string $currentUserId): void
    {
        $user = $this->userRepository->findOrFail($userId);

        $this->userAccessPolicy->assertSelfAccess($userId, $currentUserId);

        $storageKeys = $this->transactionManager->run(
            function () use ($userId, $user): array {
                return $this->deleteDatabaseRecords($userId, $user);
            },
        );

        $this->fileLifecycleService->purgeStorage($storageKeys);
        $this->taskCache->invalidateUserList($userId);
    }

    /**
     * @return list<string> storage keys to purge after commit
     */
    private function deleteDatabaseRecords(string $userId, UserModel $user): array
    {
        $storageKeys = [];

        foreach ($this->assetRepository->listByOwnerId($userId) as $asset) {
            $storageKeys = [...$storageKeys, ...$this->assetLifecycleService->deleteRecords($asset)];
        }

        foreach ($this->taskRepository->listByUserId($userId) as $task) {
            $users = TaskUserListService::unassign(
                $this->taskRepository->listUsers($task->id),
                $userId,
            );
            $this->taskRepository->save($task, $users);
        }

        foreach ($this->preferenceRepository->listByOwnerId($userId) as $preference) {
            $this->preferenceRepository->delete($preference);
        }

        foreach ($this->fileRepository->listByOwnerId($userId) as $file) {
            $storageKeys = [...$storageKeys, ...$this->fileLifecycleService->deleteRecords([$file->id])];
        }

        $this->userRepository->delete($user);

        return $storageKeys;
    }
}
