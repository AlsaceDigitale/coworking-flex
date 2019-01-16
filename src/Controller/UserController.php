<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Form\CustomerSettingsAccountType;
use App\Form\CustomerSettingsPasswordType;
use App\Form\CustomerSettingsProfileType;
use App\Repository\CheckInRepository;
use App\Repository\OptionsRepository;
use App\Repository\SubscriptionRepository;
use App\Service\Services;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class UserController
 *
 * @package        App\Controller
 * @Route("/user")
 */
class UserController extends AbstractController
{
    private $subscriptionRepository;
    private $checkInRepository;

    public function __construct(
        SubscriptionRepository $subscriptionRepository,
        CheckInRepository $checkInRepository
    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->checkInRepository = $checkInRepository;
    }

    /**
     * @Route("/", name="user_home")
     * @Security("is_fully_authenticated()")
     */
    public function home(Services $services)
    {
        $subscription = $this->subscriptionRepository->findOneBy(['customer' => $this->getUser()]);
        $checkin = $this->checkInRepository->findOneBy(['customer' => $this->getUser(), 'leaving' => null]);

        if ($checkin) {
            $arrival = $checkin->getArrival();
            $interval = $arrival->diff(new \DateTime());
            $count = $interval->format('%h:%i');

            return $this->render(
                'user/home.html.twig',
                [
                    'subscription' => $subscription,
                    'checkin' => $checkin,
                    'place' => $services->countPlaces(),
                    'count' => $count
                ]
            );
        }

        return $this->render(
            'user/home.html.twig',
            [
                'subscription' => $subscription,
                'checkin' => $checkin,
                'place' => $services->countPlaces()
            ]
        );
    }

    /**
     * @Route("/account", name="user_account")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function account()
    {
        return $this->render('user/account.html.twig');
    }

    /**
     * @Route("/account/settings", name="user_account_settings", methods="GET|POST")
     * @param Request                      $request
     * @param ObjectManager                $manager
     * @param AuthenticationUtils          $utils
     * @param UserPasswordEncoderInterface $encoder
     * @return Response
     */
    public function accountSettings(
        Request $request,
        ObjectManager $manager,
        AuthenticationUtils $utils,
        UserPasswordEncoderInterface $encoder
    ):Response {
        $formProfile = $this->createForm(CustomerSettingsProfileType::class, $this->getUser());
        $formProfile->handleRequest($request);

        if ($formProfile->isSubmitted() && $formProfile->isValid()) {
            $manager->flush();
            $this->addFlash(
                'CustomerSettingsValid',
                'Modifications enregistrées avec succès.'
            );

            return $this->redirectToRoute('user_account_settings');
        } elseif ($formProfile->isSubmitted()) {
            $this->addFlash(
                'CustomerSettingsError',
                'Information(s) fournie(s) incorrecte(s).'
            );
        }

        $formPassword = $this->createForm(CustomerSettingsPasswordType::class);
        $formPassword->handleRequest($request);

        if ($formPassword->isSubmitted() && $formPassword->isValid()) {
            if ($encoder->isPasswordValid($this->getUser(), $formPassword->get('old_password')->getData())) {
                $this->getUser()->setPassword($encoder->encodePassword($this->getUser(), $formPassword
                    ->get('password')->getData()));
                $manager->flush();
                $this->addFlash(
                    'CustomerSettingsValid',
                    'Modifications enregistrées avec succès.'
                );
                return $this->redirectToRoute('user_account_settings');
            }
        } elseif ($formPassword->isSubmitted()) {
            $this->addFlash(
                'CustomerSettingsError',
                'Information(s) fournie(s) incorrecte(s).'
            );
            return $this->redirectToRoute('user_account_settings');
        }

        $formAccount = $this->createForm(CustomerSettingsAccountType::class, $this->getUser());
        $formAccount->handleRequest($request);

        if ($formAccount->isSubmitted() && $formAccount->isValid()) {
            $manager->flush();
            $this->addFlash(
                'CustomerSettingsValid',
                'Modifications enregistrées avec succès.'
            );

            return $this->redirectToRoute('user_account_settings');
        } elseif ($formAccount->isSubmitted()) {
            $this->addFlash(
                'CustomerSettingsError',
                'Information(s) fournie(s) incorrecte(s).'
            );
        }

        return $this->render(
            'user/accountSettings.html.twig',
            [
                'formProfile' => $formProfile->createView(),
                'formPassword' => $formPassword->createView(),
                'formAccount' => $formAccount->createView(),
                'error' => $utils->getLastAuthenticationError()
            ]
        );
    }

    /**
     * @Route("/listing", name="user_listing")
     * @param OptionsRepository $optionsRepository
     * @return Response
     */
    public function listing(OptionsRepository $optionsRepository)
    {
        $month = [
            '01' => 'Janvier',
            '02' => 'Février',
            '03' => 'Mars',
            '04' => 'Avril',
            '05' => 'Mai',
            '06' => 'Juin',
            '07' => 'Juillet',
            '08' => 'Août',
            '09' => 'Septembre',
            '10' => 'Octobre',
            '11' => 'Novembre',
            '12' => 'Décembre',
        ];

        $days = [
            'Mon' => 'Lundi',
            'Tue' => 'Mardi',
            'Wed' => 'Mercredi',
            'Thu' => 'Jeudi',
            'Fri' => 'Vendredi',
            'Sat' => 'Samedi',
            'Sun' => 'Dimanche'
        ];

        $halfDayPrice = $optionsRepository->findOneBy([
            'label' => 'HalfDay'
        ])->getContent();

        $monthPrice = $optionsRepository->findOneBy([
            'label' => 'Month'
        ])->getContent();


        $checkins = $this->checkInRepository->findBy(
            ['customer' => $this->getUser()],
            ['id' => 'DESC']
        );

        $tab = [];
        foreach ($checkins as $key => $checkin) {
            $price = 0;
            $arrival = $checkin->getArrival();
            $year = $arrival->format('Y');
            $mois = $arrival->format('m');
            foreach ($this->checkInRepository->findLikeDate($year.'-'.$mois, $this->getUser()->getId()) as $result) {
                $price += ($result->getHalfDay() - $result->getFree()) * $halfDayPrice;
            }
            if ($price>$monthPrice) {
                $price =$monthPrice;
            }
            $day = $days[$arrival->format('D')];
            $leaving = $checkin->getLeaving();
            $diff = $checkin->getDiff();
            $halfDay = $checkin->getHalfDay();
            $free = $checkin->getFree();
            $date = $arrival->format('Y-m-d');
            $line = $month[$mois] . ' ' . $year.' - '.$price.'€';

            $tab[$line][] = [$date, $free, $arrival, $leaving, $diff, $halfDay, $day];
        }




        return $this->render(
            'user/listing.html.twig',
            [
                'table' => $tab
            ]
        );
    }
}
