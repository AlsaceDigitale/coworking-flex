<?php

namespace App\DataFixtures;

use App\Entity\Options;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

/* INSERT INTO `options` (`id`, `label`, `content`, `active`) VALUES */
/* (1, 'Place', '18', null), */
/* (2, 'Text', 'Votre texte ici.', 0), */
/* (3, 'HalfDay', '6', null), */
/* (4, 'Month', '140', null); */
class OptionsFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $allOptions = [
            ['Place', '18', null],
            ['Text', 'Votre texte ici.', false],
            ['HalfDay', '6', null],
            ['Month', '140', null]
        ];
        foreach ($allOptions as $o) {
            $options = new Options();
            $options->setLabel($o[0]);
            $options->setContent($o[1]);
            $options->setActive($o[2]);
            $manager->persist($options);
        }
        $manager->flush();
    }
}
