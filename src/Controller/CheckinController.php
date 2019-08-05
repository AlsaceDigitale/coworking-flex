<?php

namespace App\Controller;

use App\Entity\CheckIn;
use App\Repository\CheckInRepository;
use App\Repository\PromoRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints;

class CheckinController extends AbstractController
{
    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * @var CheckInRepository
     */
    private $checkInRepository;

    /**
     * @var PromoRepository
     */
    private $promoRepository;

    /**
     * CheckinController constructor.
     * @param ObjectManager $manager
     * @param CheckInRepository $checkInRepository
     * @param PromoRepository $promoRepository
     */
    public function __construct(
        ObjectManager $manager,
        CheckInRepository $checkInRepository,
        PromoRepository $promoRepository
    ) {
        $this->manager = $manager;
        $this->checkInRepository = $checkInRepository;
        $this->promoRepository = $promoRepository;
    }

    /**
     * @Route("/user/checkin", name="user_checkin")
     * @throws \Exception
     */
    public function checkin()
    {   

        // Limit the total halfDay at 2 per customer per day
        /* example :
        *  customer XXX checkin at Y-m-d 12:00:00  |
        *  customer XXX checkout at Y-m-d 12:10:00 |-> +1 halfDay
        *  customer XXX checkin at Y-m-d 12:11:00  |
        *  customer XXX checkout at Y-m-d 12:21:00 |-> +1 halfDay 
        *  customer XXX checkin at Y-m-d 12:22:00  |
        *  customer XXX checkout at Y-m-d 12:32:00 |-> +1 halfDay
        * 
        *  total : 3 halfDays in the same day
        *  => take total=sum(halfDays) per customer_id for days in the checkins
        *     and if halfday_count>=2 then set the others to 0.
        * 
        *  look at the findByHalfDayCount[---] functions in the CheckinRepository for more informations
        */

        $datetime = new \DateTime();
        $verifs=$this->checkInRepository->findByEmptyLeaving($datetime->format('Y-m-d'));

        /*
        * Check if the customer has an other checkin that hasn't been checked out 
        */
        if ($verifs) {
            foreach ($verifs as $verif) {
                $checkout = $this->checkInRepository->findOneBy([
                    'customer' => $verif->getCustomer(),
                    'leaving' => null]);
                $promo = $this->promoRepository->findOneBy([
                    'customer' => $verif->getCustomer()
                ]);
                $checkout->setLeaving(new \DateTime());
                $interval = $checkout->getArrival()
                    ->diff($checkout->getLeaving());
                $checkout->setDiff(new \DateTime($interval->format('%h:%i:%s')));
                $time_hours = $interval->format('%h');
                $time_min = $interval->format('%i');
                $timeDay = $interval->format('%d');

                if ($halfday_count >= 2) {
                    /*
                    * Limit the total number of halfdays in the case where a customer forget to checkout 
                    */
                    $checkin->setHalfDay(0);
                } elseif ($time_hours <= 4 && $time_min <= 30 && $timeDay == 0) {
                    /*
                    * If the difference (checkout-checkin) <= 4h30 : setHalfDay(1)
                    */
                    $checkout->setHalfDay(1);
                    if ($promo->getCounter() > 0) {
                        $checkout->setFree(1);
                        $promo->setCounter($promo->getCounter()-1);
                    } else {
                        $checkout->setFree(0);
                    }
                } else {
                    $checkout->setHalfDay(2);
                    if ($promo->getCounter() == 1) {
                        $checkout->setFree(1);
                        $promo->setCounter($promo->getCounter()-1);
                    } elseif ($promo->getCounter() > 1) {
                        $checkout->setFree(2);
                        $promo->setCounter($promo->getCounter()-2);
                    } else {
                        $checkout->setFree(0);
                    }
                }
                $this->manager->persist($checkout);
                $this->manager->persist($promo);
                $this->manager->flush();
            }
        }

        $checkin = new CheckIn();
        $checkin->setCustomer($this->getUser())
            ->setArrival($datetime)
            ->setArrivalDate($datetime->format('Y-m-d'))
            ->setArrivalMonth($datetime->format('Y-m'));
        $this->manager->persist($checkin);
        $this->manager->flush();

        $this->addFlash(
            'arrival',
            'Bonne baignade '
        );


        return $this->redirectToRoute('user_home');
    }

    /**
     * @Route("/user/checkout", name="user_checkout")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Exception
     */
    public function checkout()
    {
        
        // Limit the total halfDay at 2 per customer per day
        /* example :
        *  customer XXX checkin at Y-m-d 12:00:00  |
        *  customer XXX checkout at Y-m-d 12:10:00 |-> +1 halfDay
        *  customer XXX checkin at Y-m-d 12:11:00  |
        *  customer XXX checkout at Y-m-d 12:21:00 |-> +1 halfDay 
        *  customer XXX checkin at Y-m-d 12:22:00  |
        *  customer XXX checkout at Y-m-d 12:32:00 |-> +1 halfDay
        * 
        *  total : 3 halfDays in the same day
        *  => take total=sum(halfDays) per customer_id for days in the checkins
        *     and if halfday_count>=2 then set the others to 0.
        * 
        *  look at the findByHalfDayCount[---] functions in the CheckinRepository for more informations
        */

        $datetime = new \DateTime();
        $customer = $this->getUser();
        $halfday_count = $this->checkInRepository->findByHalfDayCountForCustomer($datetime->format('Y-m-d'),$customer->getId());

        $checkin = $this->checkInRepository->findOneBy(['customer' => $this->getUser(), 'leaving' => null]);
        $promo = $this->promoRepository->findOneBy(['customer' => $this->getUser()]);
        $checkin->setLeaving($datetime);
        $interval = $checkin->getArrival()->diff($checkin->getLeaving());
        $checkin->setDiff(new \DateTime($interval->format('%h:%i:%s')));
        $time_hours = $interval->format('%h');
        $time_min = $interval->format('%i');
        $timeDay = $interval->format('%d');

        if ($halfday_count >= 2) {
            /*
            * Limit the total number of halfdays in the case where a customer forget to checkout 
            */
            $checkin->setHalfDay(0);
        } elseif ($time_hours <= 4 && $time_min <= 30 && $timeDay == 0) {
            /*
            * If the difference (checkout-checkin) <= 4h30 : setHalfDay(1)
            */
            $checkin->setHalfDay(1);
            if ($promo->getCounter() > 0) {
                $checkin->setFree(1);
                $promo->setCounter($promo->getCounter()-1);
            } else {
                $checkin->setFree(0);
            }echo 'CAS 2';
        } else {
            $checkin->setHalfDay(2);
            if ($promo->getCounter() == 1) {
                $checkin->setFree(1);
                $promo->setCounter($promo->getCounter()-1);
            } elseif ($promo->getCounter() > 1) {
                $checkin->setFree(2);
                $promo->setCounter($promo->getCounter()-2);
            } else {
                $checkin->setFree(0);
            }
        }

        $this->manager->persist($checkin);
        $this->manager->persist($promo);
        $this->manager->flush();

        $this->addFlash(
            'bye',
            'A la prochaine '
        );
        die();
        return $this->redirectToRoute('user_home');
    }
}
