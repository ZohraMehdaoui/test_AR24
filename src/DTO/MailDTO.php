<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class MailDTO
{
    #[Assert\NotBlank]
    public string $id_user;

    #[Assert\NotBlank]
    public string $to_lastname;

    #[Assert\NotBlank]
    public string $to_firstname;

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $to_email;

    #[Assert\NotBlank]
    public string $dest_statut;

    #[Assert\NotBlank]
    public string $content;

    public ?string $to_company = null;

    public ?string $attachment = null;

    #[Assert\Type('bool')]
    public bool $is_eidas = false;

    public function toArray(): array
    {
        return [
            'id_user' => $this->id_user,
            'to_firstname' => $this->to_firstname,
            'to_lastname' => $this->to_lastname,
            'to_email' => $this->to_email,
            'dest_statut' => $this->dest_statut,
            'content' => $this->content,
            'to_company' => $this->to_company,
            'attachment' => $this->formatAttachments()
        ];
    }

    private function formatAttachments(): ?array
    {
        if (!$this->attachment) {
            return null;
        }

        return explode(',', $this->attachment);
    }
}
