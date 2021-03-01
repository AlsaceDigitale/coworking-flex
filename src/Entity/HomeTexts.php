<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

class HomeTexts
{

    /**
     * @Assert\NotNull()
     */
    private $firstText;

    private $firstActive;

    /**
     * @var File|null
     * @Assert\File(mimeTypes={"image/png", "image/jpeg"})
     */
    private $firstPictureFile;

    /**
     * @Assert\NotNull()
     */
    private $secondText;

    private $secondActive;

    /**
     * @var File|null
     * @Assert\File(mimeTypes={"image/png", "image/jpeg"})
     */
    private $secondPictureFile;

    /**
     * @Assert\NotNull()
     */
    private $thirdText;

    private $thirdActive;

    /**
     * @var File|null
     * @Assert\File(mimeTypes={"image/png", "image/jpeg"})
     */
    private $thirdPictureFile;

    public function __construct(Options $firstHomeText, Options $secondHomeText, Options $thirdHomeText)
    {
        $this->firstText = $firstHomeText->getContent();
        $this->firstActive = $firstHomeText->getActive();
        $this->secondText = $secondHomeText->getContent();
        $this->secondActive = $secondHomeText->getActive();
        $this->thirdText = $thirdHomeText->getContent();
        $this->thirdActive = $thirdHomeText->getActive();
    }

    public function getData(): ?array {
        return [
            ['text' => $this->firstText, 'file' => $this->firstPictureFile],
            ['text' => $this->secondText, 'file' => $this->secondPictureFile],
            ['text' => $this->thirdText, 'file' => $this->thirdPictureFile],
        ];
    }

    /**
     * @return string|null
     */
    public function getFirstText(): ?string
    {
        return $this->firstText;
    }

    /**
     * @param string|null $firstText
     * @return HomeTexts
     */
    public function setFirstText(?string $firstText): self
    {
        $this->firstText = $firstText;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getFirstActive(): ?bool
    {
        return $this->firstActive;
    }

    /**
     * @param bool|null $firstActive
     * @return HomeTexts
     */
    public function setFirstActive(?bool $firstActive): HomeTexts
    {
        $this->firstActive = $firstActive;
        return $this;
    }

    /**
     * @return File|null
     */
    public function getFirstPictureFile(): ?File
    {
        return $this->firstPictureFile;
    }

    /**
     * @param File|null $firstPictureFile
     * @return HomeTexts
     */
    public function setFirstPictureFile(?File $firstPictureFile): self
    {
        $this->firstPictureFile = $firstPictureFile;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSecondText(): ?string
    {
        return $this->secondText;
    }

    /**
     * @param string|null $secondText
     * @return HomeTexts
     */
    public function setSecondText(?string $secondText): self
    {
        $this->secondText = $secondText;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getSecondActive(): ?bool
    {
        return $this->secondActive;
    }

    /**
     * @param bool|null $secondActive
     * @return HomeTexts
     */
    public function setSecondActive(?bool $secondActive): HomeTexts
    {
        $this->secondActive = $secondActive;
        return $this;
    }

    /**
     * @return File|null
     */
    public function getSecondPictureFile(): ?File
    {
        return $this->secondPictureFile;
    }

    /**
     * @param File|null $secondPictureFile
     * @return HomeTexts
     */
    public function setSecondPictureFile(?File $secondPictureFile): self
    {
        $this->secondPictureFile = $secondPictureFile;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getThirdText(): ?string
    {
        return $this->thirdText;
    }

    /**
     * @param string|null $thirdText
     * @return HomeTexts
     */
    public function setThirdText(?string $thirdText): self
    {
        $this->thirdText = $thirdText;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getThirdActive(): ?bool
    {
        return $this->thirdActive;
    }

    /**
     * @param bool|null $thirdActive
     * @return HomeTexts
     */
    public function setThirdActive(?bool $thirdActive): HomeTexts
    {
        $this->thirdActive = $thirdActive;
        return $this;
    }

    /**
     * @return File|null
     */
    public function getThirdPictureFile(): ?File
    {
        return $this->thirdPictureFile;
    }

    /**
     * @param File|null $thirdPictureFile
     * @return HomeTexts
     */
    public function setThirdPictureFile(?File $thirdPictureFile): self
    {
        $this->thirdPictureFile = $thirdPictureFile;
        return $this;
    }

}