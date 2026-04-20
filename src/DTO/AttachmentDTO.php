<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class AttachmentDTO
{
    #[Assert\NotBlank]
    public string $userId;

    #[Assert\NotBlank]
    public string $filePath;

    public ?string $fileName;
}
