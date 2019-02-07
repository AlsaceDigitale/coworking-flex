<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class AdminFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $admin = new Customer();
        $admin->setUsername("admin");
        $admin->setMail("coworking-flex@alsacedigitale.org");
        $admin->setLastname("LaPlage");
        $admin->setFirstname("Admin");
        $admin->setAddress("15 avenue du Rhin");
        $admin->setCity("Strasbourg");
        $admin->setCountry("France");
        $admin->setPhone("0600000000");
        $admin->setZip("67100");
        $admin->setRole("ROLE_ADMIN");
        $admin->setPassword('$2y$12$Mhti/4iWNL0VvJvmVAvfMuix8J3rnzUnTxYpINuzyrzI23Pvuohau');

        $manager->persist($admin);
        $manager->flush();
    }
}
