<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
class User {
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  private ?int $id = null;

  #[ORM\Column(length: 25, unique: true)]
  private string $phone;

  #[ORM\Column(length: 50)]
  private string $username;

  #[ORM\OneToMany(targetEntity: PhoneVerification::class, mappedBy: 'user')]
  private Collection $phoneVerifications;

  public function getId(): ?int {
    return $this->id;
  }

  public function getPhone(): ?string {
    return $this->phone;
  }

  public function setPhone(string $phone): self {
    $this->phone = $phone;
    return $this;
  }

  public function getUsername(): ?string {
    return $this->username;
  }

  public function setUsername(string $username): self {
    $this->username = $username;
    return $this;
  }
}
