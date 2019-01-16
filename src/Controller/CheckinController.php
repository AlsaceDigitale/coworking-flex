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
        $checkin = new CheckIn();
        $datetime = new \DateTime();
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

        $verifs=$this->checkInRepository->findByEmptyLeaving($datetime->format('Y-m-d'));
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
                $time = $interval->format('%h');
                $timeDay = $interval->format('%d');
                if ($time < 4 && $timeDay == 0) {
                    $checkout ->setHalfDay(1);
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
        $verif=$this->checkInRepository->findByEmptyLeaving($datetime->format('Y-m-d'));
        return $this->redirectToRoute('user_home');
    }

    /**
     * @Route("/user/checkout", name="user_checkout")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Exception
     */
    public function checkout()
    {
        $checkin = $this->checkInRepository->findOneBy(['customer' => $this->getUser(), 'leaving' => null]);
        $promo = $this->promoRepository->findOneBy(['customer' => $this->getUser()]);
        $checkin->setLeaving(new \DateTime());
        $interval = $checkin->getArrival()->diff($checkin->getLeaving());
        $checkin->setDiff(new \DateTime($interval->format('%h:%i:%s')));
        $time = $interval->format('%h');
        $timeDay = $interval->format('%d');


        if ($time < 4 && $timeDay == 0) {
            $checkin ->setHalfDay(1);
            if ($promo->getCounter() > 0) {
                $checkin->setFree(1);
                $promo->setCounter($promo->getCounter()-1);
            } else {
                $checkin->setFree(0);
            }
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

        return $this->redirectToRoute('user_home');
    }
}
