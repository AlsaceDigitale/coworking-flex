<?php


namespace App\Entity;


use DateTimeInterface;

class ExportPeriod
{

    /**
     * @var DateTimeInterface|null
     */
    private $beginDate;

    /**
     * @var DateTimeInterface|null
     */
    private $endDate;

    public function __construct()
    {
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getBeginDate(): ?DateTimeInterface
    {
        return $this->beginDate;
    }

    /**
     * @param DateTimeInterface $beginDate
     * @return ExportPeriod
     */
    public function setBeginDate(DateTimeInterface $beginDate): ExportPeriod
    {
        $this->beginDate = $beginDate;
        return $this;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getEndDate(): ?DateTimeInterface
    {
        return $this->endDate;
    }

    /**
     * @param DateTimeInterface $endDate
     * @return ExportPeriod
     */
    public function setEndDate(DateTimeInterface $endDate): ExportPeriod
    {
        $this->endDate = $endDate;
        return $this;
    }

}