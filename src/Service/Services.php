<?php

namespace App\Service;

use App\Entity\CheckIn;
use App\Entity\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class Services extends AbstractController
{
    public function countPlaces()
    {
        $optionsRepository = $this->getDoctrine()->getRepository(Options::class);
        $checkInRepository = $this->getDoctrine()->getRepository(CheckIn::class);
        $place = $optionsRepository->findOneBylabel('Place')->getContent();
        $checkins = $checkInRepository->findBy(['leaving' => null]);
        $placeCount = count($checkins);
        return $place-$placeCount;
    }
}
