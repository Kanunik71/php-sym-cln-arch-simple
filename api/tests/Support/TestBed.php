<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Application\Port\Asset\AssetRepositoryInterface;
use App\Application\Port\File\FileRepositoryInterface;
use App\Application\Port\Notification\NotificationPreferenceRepositoryInterface;
use App\Application\Port\PasswordHasherInterface;
use App\Application\Port\Task\TaskRepositoryInterface;
use App\Application\Port\TaskGroup\TaskGroupRepositoryInterface;
use App\Application\Port\User\UserRepositoryInterface;
use App\Application\ValueObject\Common\Email;
use App\Application\Model\User\UserModel;
use App\Shared\Utils\ContainerUtils;
use App\Tests\Support\Trait\TestBed\AssetTestBedTrait;
use App\Tests\Support\Trait\TestBed\FileTestBedTrait;
use App\Tests\Support\Trait\TestBed\TaskGroupTestBedTrait;
use App\Tests\Support\Trait\TestBed\TaskTestBedTrait;
use App\Tests\Support\Trait\TestBed\UserTestBedTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Psr\Container\ContainerInterface;

final class TestBed
{
    use UserTestBedTrait;
    use TaskTestBedTrait;
    use AssetTestBedTrait;
    use FileTestBedTrait;
    use TaskGroupTestBedTrait;

    public const DEMO_EMAIL = 'demo@example.com';

    public const DEMO_PASSWORD = 'secret123';

    private UserRepositoryInterface $users;

    private NotificationPreferenceRepositoryInterface $notificationPreferences;

    private TaskRepositoryInterface $tasks;

    private AssetRepositoryInterface $assets;

    private FileRepositoryInterface $files;

    private TaskGroupRepositoryInterface $taskGroups;

    private PasswordHasherInterface $passwordHasher;

    private EntityManagerInterface $em;

    private function __construct(
        private readonly ContainerInterface $container,
    ) {
        $this->em = ContainerUtils::get($container, EntityManagerInterface::class);
        $this->users = ContainerUtils::get($container, UserRepositoryInterface::class);
        $this->notificationPreferences = ContainerUtils::get($container, NotificationPreferenceRepositoryInterface::class);
        $this->tasks = ContainerUtils::get($container, TaskRepositoryInterface::class);
        $this->assets = ContainerUtils::get($container, AssetRepositoryInterface::class);
        $this->files = ContainerUtils::get($container, FileRepositoryInterface::class);
        $this->taskGroups = ContainerUtils::get($container, TaskGroupRepositoryInterface::class);
        $this->passwordHasher = ContainerUtils::get($container, PasswordHasherInterface::class);
    }

    public static function create(ContainerInterface $container): self
    {
        return new self($container);
    }

    public function resetSchema(): void
    {
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();

        if ($metadata === []) {
            return;
        }

        $schemaTool = new SchemaTool($this->em);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function clear(): void
    {
        $this->em->clear();
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $id
     *
     * @return T
     */
    public function get(string $id): object
    {
        return ContainerUtils::get($this->container, $id);
    }

    public function seedDefaultUser(
        ?string $email = null,
        ?string $password = null,
        ?string $fname = null,
        ?string $lname = null,
    ): UserModel {
        return $this->createUser(
            email: $email ?? self::DEMO_EMAIL,
            password: $password ?? self::DEMO_PASSWORD,
            fname: $fname ?? 'Demo',
            lname: $lname ?? 'User',
        );
    }

    public function userExistsByEmail(string $email): bool
    {
        return $this->users->existsByEmail(new Email($email));
    }
}
