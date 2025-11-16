<?php

namespace App\Entity;

use App\Repository\IpAddressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IpAddressRepository::class)]
class IpAddress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 15)]
    private ?string $ip = null;
    #[ORM\Column(name: "created_at", type: "datetime_immutable", insertable: false, updatable: false)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: "updated_at", type: "datetime_immutable", insertable: false, updatable: false)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToOne(mappedBy: 'ipAddress', cascade: ['persist', 'remove'])]
    private ?BlackList $blacklisted = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): static
    {
        $this->ip = $ip;

        return $this;
    }
    public function getAddress(): ?string
    {
        return $this->ip;
    }
    public function setAddress(string $address): static
    {
        $this->ip = $address;

        return $this;
    }
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
    public function isBlacklisted(): bool
    {
        return $this->blacklisted !== null;
    }
    public function getBlacklisted(): ?BlackList
    {
        return $this->blacklisted;
    }

    public function setBlacklisted(BlackList $blacklisted): static
    {
        // set the owning side of the relation if necessary
        if ($blacklisted->getIpAddress() !== $this) {
            $blacklisted->setIpAddress($this);
        }

        $this->blacklisted = $blacklisted;

        return $this;
    }
    public function isTooOld():bool
    {
        $now = new \DateTimeImmutable();
        $interval = $now->getTimestamp() - $this->updatedAt->getTimestamp();
        $lifeCycleSeconds = (int)$_ENV['IP_LIFE_CYCLE_SECONDS'];
        return $interval > $lifeCycleSeconds;
    }
}
