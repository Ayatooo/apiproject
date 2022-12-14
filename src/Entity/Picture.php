<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\PictureRepository;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Attributes as OA;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: PictureRepository::class)]
/**
 * @Vich\Uploadable()
 */
class Picture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['showPicture'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['showPicture'])]
    private ?string $realName = null;

    #[ORM\Column(length: 255)]
    #[Groups(['showPicture'])]
    private ?string $realPath = null;

    #[ORM\Column(length: 255)]
    #[Groups(['showPicture'])]
    private ?string $publicPath = null;

    #[ORM\Column(length: 50)]
    #[Groups(['showPicture'])]
    private ?string $mimeType = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    /**
     * @var File|null
     *@Vich\UploadableField(mapping="pictures", fileNameProperty="realPath")
     */
    #[OA\Property(type: "file")]
    private ?File $file = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRealName(): ?string
    {
        return $this->realName;
    }

    public function setRealName(string $realName): self
    {
        $this->realName = $realName;

        return $this;
    }

    public function getRealPath(): ?string
    {
        return $this->realPath;
    }

    public function setRealPath(string $realPath): self
    {
        $this->realPath = $realPath;

        return $this;
    }

    public function getPublicPath(): ?string
    {
        return $this->publicPath;
    }

    public function setPublicPath(string $publicPath): self
    {
        $this->publicPath = $publicPath;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): self
    {
        $this->file = $file;

        return $this;
    }
}
