<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CheckInRepository")
 */
class CheckIn
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $arrival;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $leaving;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Customer", inversedBy="checkIns")
     * @ORM\JoinColumn(nullable=false)
     */
    private $customer;

    /**
     * @ORM\Column(type="time", nullable=true)
     */
    private $diff;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $halfDay;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $free;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $arrivalDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $arrival_month;


    public function __construct()
    {
        $this->halfDays = new ArrayCollection();
    }
  
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArrival(): ?\DateTimeInterface
    {
        return $this->arrival;
    }

    public function setArrival(\DateTimeInterface $arrival): self
    {
        $this->arrival = $arrival;

        return $this;
    }

    public function getLeaving(): ?\DateTimeInterface
    {
        return $this->leaving;
    }

    public function setLeaving(?\DateTimeInterface $leaving): self
    {
        $this->leaving = $leaving;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function getDiff(): ?\DateTimeInterface
    {
        return $this->diff;
    }

    public function setDiff(?\DateTimeInterface $diff): self
    {
        $this->diff = $diff;

        return $this;
    }

    public function getHalfDay(): ?int
    {
        return $this->halfDay;
    }

    public function setHalfDay(?int $halfDay): self
    {
        $this->halfDay = $halfDay;

        return $this;
    }

    public function getFree(): ?int
    {
        return $this->free;
    }

    public function setFree(?int $free): self
    {
        $this->free = $free;

        return $this;
    }

    public function getArrivalDate(): ?string
    {
        return $this->arrivalDate;
    }

    public function setArrivalDate(string $arrivalDate): self
    {
        $this->arrivalDate = $arrivalDate;

        return $this;
    }

    public function getArrivalMonth(): ?string
    {
        return $this->arrival_month;
    }

    public function setArrivalMonth(?string $arrival_month): self
    {
        $this->arrival_month = $arrival_month;

        return $this;
    }
}
