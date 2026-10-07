<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'asset_file')]
class AssetFileEntity
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: AssetEntity::class, inversedBy: 'assetFiles')]
    #[ORM\JoinColumn(name: 'asset_id', referencedColumnName: 'id', nullable: false)]
    private AssetEntity $asset;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: FileEntity::class)]
    #[ORM\JoinColumn(name: 'file_id', referencedColumnName: 'id', nullable: false)]
    private FileEntity $file;

    #[ORM\Column(type: 'integer')]
    private int $position = 0;

    public function getAsset(): AssetEntity
    {
        return $this->asset;
    }

    public function setAsset(AssetEntity $asset): self
    {
        $this->asset = $asset;

        return $this;
    }

    public function getFile(): FileEntity
    {
        return $this->file;
    }

    public function setFile(FileEntity $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getFileId(): Uuid
    {
        return $this->file->getId();
    }
}
