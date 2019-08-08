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
        $datetime = new \DateTime;
        $customer = $this->getUser();
        $checkin = new CheckIn();

        // Traitement erreur double checkin meme user meme journee
        $doublons = $this->checkInRepository->findByDoublonsCheckin($customer->getId(),$datetime->format('Y-m-d'));
        if ($doublons) {
            $this->addFlash(
                'error_checkin',
                'On sait que vous êtes là ! Pas la peine de le répéter ... '
            );
            return $this->redirectToRoute('user_home');
        }

        // Checkin a proprement parler si pas de doublons 
        $checkin->setCustomer($this->getUser())
            ->setArrival($datetime)
            ->setArrivalDate($datetime->format('Y-m-d'))
            ->setArrivalMonth($datetime->format('Y-m'));
        $this->manager->persist($checkin);
        $this->manager->flush();

        $this->addFlash(
            'arrival',
            'Bonne journée ! '
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
        $datetime = new \DateTime();
        $customer = $this->getUser();

        $halfday_count = $this->checkInRepository->findByHalfDayCountForCustomer($datetime->format('Y-m-d'),$customer->getId());
        $checkin = $this->checkInRepository->findOneBy(['customer' => $this->getUser(), 'leaving' => null]);
        $promo = $this->promoRepository->findOneBy(['customer' => $this->getUser()]);

        // Si il y a bien un checkin sans checkout (leaving) --> else : erreur double checkout
        if (isset($checkin)) {
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
                $checkin->setFree(0);
            } elseif ($time_hours <= 4 && $time_min <= 30 && $timeDay == 0) {
                /*
                * If the difference (checkout-checkin) <= 4h30 : setHalfDay(1)
                */
                $checkin->setHalfDay(1);
                if ($promo->getCounter() == 1) {
                    $checkin->setFree(1);
                    $promo->setCounter($promo->getCounter()-1);
                } elseif ($promo->getCounter() > 1) {
                    $checkin->setFree(2);
                    $promo->setCounter($promo->getCounter()-2);
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

        // Traitement erreur double checkin meme user meme journee
        $this->addFlash(
            'error_checkout',
            'Vous êtes déjà parti ! Mais revenez vite, on vous attend ... '
        ); 

        return $this->redirectToRoute('user_home');
    }

    /**
     * @Route("/global-checkout", name="global_checkout")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Exception
     */
    public function globalcheckout()
    {
        $yesterday_midnight = new \DateTime("yesterday midnight");
        $all_empty_leaving = $this->checkInRepository->findByPreviousEmptyLeaving($yesterday_midnight->format('Y-m-d'));
        
        foreach ($all_empty_leaving as $checkin) {
            $customer = $checkin->getCustomer();
            
            $halfday_count = $this->checkInRepository->findByHalfDayCountForCustomer($yesterday_midnight->format('Y-m-d'),$customer->getId());
            $promo = $this->promoRepository->findOneBy(['customer' => $this->getUser()]);
            $checkin->setLeaving($yesterday_midnight);

            $duree = $checkin->getArrival()->diff($checkin->getLeaving());
            $checkin->setDiff(new \DateTime($duree->format('%h:%i:%s')));

            if ($halfday_count >= 2) {
                /*
                * Limit the total number of halfdays in the case where a customer forget to checkout 
                */
                $checkin->setHalfDay(0);
                $checkin->setFree(0);
            } elseif ($duree->format('%h') <= 4 && $duree->format('%i') <= 30) {
                /*
                * If the difference (checkout-checkin) <= 4h30 : setHalfDay(1)
                */
                $checkin->setHalfDay(1);
                if ($promo->getCounter() == 1) {
                    $checkin->setFree(1);
                    $promo->setCounter($promo->getCounter()-1);
                } elseif ($promo->getCounter() > 1) {
                    $checkin->setFree(2);
                    $promo->setCounter($promo->getCounter()-2);
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
        }

        return $this->redirectToRoute('user_home');
    }
}
