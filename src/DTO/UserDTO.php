<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;


class UserDTO
{
    #[Assert\NotBlank]
    public string $firstname;

    #[Assert\NotBlank]
    public string $lastname;

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    public string $gender;

    #[Assert\NotBlank]
    public string $statut;

    #[Assert\When(
        expression: 'this.statut == "professionnel"',
        constraints: [
            new Assert\NotBlank(message: 'Company name is required for professional users')
        ]
    )]
    public ?string $company;

    #[Assert\When(
        expression: 'this.statut == "professionnel"',
        constraints: [
            new Assert\NotBlank(message: 'Company SIRET is required for professional users'),
            new Assert\Length(
                min: 14,
                max: 14,
                exactMessage: 'Company SIRET must be exactly {{ limit }} characters long'
            )
        ]
    )]
    public ?string $company_siret;


    public string $company_tva;

    #[Assert\NotBlank]
    public string $country;

    #[Assert\NotBlank]
    public string $address1;

    #[Assert\NotBlank]
    public string $zipcode;

    #[Assert\NotBlank]
    public string $city;

    public ?string $address2 = null;

    public function toArray(): array
    {
        return array_filter([
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'email' => $this->email,
            'gender' => $this->gender,
            'statut' => $this->statut,
            'company' => $this->company,
            'company_siret' => $this->company_siret,
            'company_tva' => $this->company_tva,
            'country' => $this->country,
            'address1' => $this->address1,
            'address2' => $this->address2,
            'zipcode' => $this->zipcode,
            'city' => $this->city,
        ]);
    }
}
