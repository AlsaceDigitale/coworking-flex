<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\HalfDayAdjustmentRepository")
 */
class HalfDayAdjustment
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $customer_id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $counteradd;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $counterremove;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $arrival_month;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomerId(): ?int
    {
        return $this->customer_id;
    }

    public function setCustomerId(int $customer_id): self
    {
        $this->customer_id = $customer_id;

        return $this;
    }

    public function getCounteradd(): ?int
    {
        return $this->counteradd;
    }

    public function setCounteradd(?int $counteradd): self
    {
        $this->counteradd = $counteradd;

        return $this;
    }

    public function getCounterremove(): ?int
    {
        return $this->counterremove;
    }

    public function setCounterremove(?int $counterremove): self
    {
        $this->counterremove = $counterremove;

        return $this;
    }

    public function getArrivalMonth(): ?string
    {
        return $this->arrival_month;
    }

    public function setArrivalMonth(string $arrival_month): self
    {
        $this->arrival_month = $arrival_month;

        return $this;
    }
}
