<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Application\Exception\File\FileNotFoundException;
use App\Application\Model\File\FileModel;
use App\Application\Port\File\FileRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Mapper\FileMapper;
use App\Infrastructure\Persistence\Doctrine\Query\File\FileEntityFetcher;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineFileRepository implements FileRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FileMapper $fileMapper,
        private FileEntityFetcher $fileEntityFetcher,
    ) {
    }

    public function save(FileModel $file): FileModel
    {
        $entity = $this->fileEntityFetcher->findById($file->id);

        $entity = $this->fileMapper->toEntity($file, $entity);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $this->findOrFail($file->id);
    }

    public function findById(string $id): ?FileModel
    {
        $entity = $this->fileEntityFetcher->findById($id);

        return $entity !== null ? $this->fileMapper->toModel($entity) : null;
    }

    public function findOrFail(string $id): FileModel
    {
        return $this->findById($id) ?? throw FileNotFoundException::withId($id);
    }

    public function listByIds(array $ids): array
    {
        return $this->fileMapper->toModelArray($this->fileEntityFetcher->listByIds($ids));
    }

    public function listExpiredTemporary(DateTimeImmutable $updatedBefore): array
    {
        return $this->fileMapper->toModelArray(
            $this->fileEntityFetcher->listExpiredTemporary($updatedBefore),
        );
    }

    public function listByOwnerId(string $ownerId): array
    {
        return $this->fileMapper->toModelArray($this->fileEntityFetcher->listByOwnerId($ownerId));
    }

    public function delete(FileModel $file): void
    {
        $entity = $this->fileEntityFetcher->findById($file->id);

        if ($entity === null) {
            return;
        }

        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }
}
