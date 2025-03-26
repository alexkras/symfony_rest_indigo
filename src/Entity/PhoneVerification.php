<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class PhoneVerification {
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  private ?int $id = null;

  #[ORM\ManyToOne(targetEntity: User::class)]
  private User $user;

  #[ORM\Column(length: 25)]
  private string $phone;

  #[ORM\Column]
  private \DateTimeImmutable $verifiedAt;

  public function getId(): ?int {
    return $this->id;
  }

  public function getUser(): ?User {
    return $this->user;
  }

  public function setUser(User $user): self {
    $this->user = $user;
    return $this;
  }

  public function getPhone(): ?string {
    return $this->phone;
  }

  public function setPhone(string $phone): self {
    $this->phone = $phone;
    return $this;
  }

  public function getVerifiedAt(): ?\DateTimeImmutable {
    return $this->verifiedAt;
  }

  public function setVerifiedAt(\DateTimeImmutable $verifiedAt): self {
    $this->verifiedAt = $verifiedAt;
    return $this;
  }
}
