<?php

namespace App\Controller;

use App\Form\CustomerSettingsAccountType;
use App\Form\CustomerSettingsPasswordType;
use App\Form\CustomerSettingsProfileType;
use App\Repository\CheckInRepository;
use App\Repository\OptionsRepository;
use App\Repository\SubscriptionRepository;
use App\Repository\HalfDayAdjustmentRepository;
use App\Service\Services;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

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
    private $manager;
    private $halfDayAdjustmentRepository;

    public function __construct(
        SubscriptionRepository $subscriptionRepository,
        CheckInRepository $checkInRepository,
        HalfDayAdjustmentRepository $halfDayAdjustmentRepository,
        EntityManagerInterface $manager
    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->checkInRepository = $checkInRepository;
        $this->halfDayAdjustmentRepository = $halfDayAdjustmentRepository;
        $this->manager = $manager;
    }

    /**
     * @Route("/", name="user_home")
     * @Security("is_fully_authenticated()")
     */
    public function home(Services $services)
    {
        ($this->getUser())->setLastActivityAt(new \DateTime());
        $this->manager->persist($this->getUser());
        $this->manager->flush();

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
     * @param AuthenticationUtils          $utils
     * @param UserPasswordEncoderInterface $encoder
     * @return Response
     */
    public function accountSettings(
        Request $request,
        AuthenticationUtils $utils,
        UserPasswordEncoderInterface $encoder
    ):Response {
        $formProfile = $this->createForm(CustomerSettingsProfileType::class, $this->getUser());
        $formProfile->handleRequest($request);

        if ($formProfile->isSubmitted() && $formProfile->isValid()) {
            $this->manager->flush();
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
                $this->manager->flush();
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
            $this->manager->flush();
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
        $customer = $this->getUser();

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

        $jours = [
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

        $user_checkins = $this->checkInRepository->findBy(
            ['customer' => $customer],
            ['id' => 'DESC']
        );

        $all_checkins = [];
        foreach ($user_checkins as $key => $checkin) {
            ## Customer's attendance card header [Mois Annee - Facture€] ##
            $prix = 0;
            $arrivee = $checkin->getArrival();
            $annee = $arrivee->format('Y');
            $mois = $arrivee->format('m');
            foreach ($this->checkInRepository->findLikeDate($annee.'-'.$mois, $customer->getId()) as $result) {
                $prix += ($result->getHalfDay() - $result->getFree()) * $halfDayPrice;
            }
            if ($prix>$monthPrice) {
                $prix = $monthPrice;
            }
            $line = $month[$mois] . ' ' . $annee;

            ## Customer's attendance card body [Arrivee (jj-mm-aaaa hh:mm:ss) | Depart (jj-mm-aaaa hh:mm:ss) | Demi-Journées (int)] ##
            $Arrivee = $checkin->getArrival();
            $Jour_arrivee_str = $jours[$Arrivee->format('D')];
            $Jour_arrivee_num = $Arrivee->format('d');
            $Mois_arrivee_num = $Arrivee->format('m');
            $Annee_arrivee_num = $Arrivee->format('Y');
            $Heure_arrivee = $Arrivee->format('H:i:s');

            $Depart = $checkin->getLeaving();
            if($Depart != null)
            {
                $Jour_depart_str = $jours[$Depart->format('D')];
                $Jour_depart_num = $Depart->format('d');
                $Mois_depart_num = $Depart->format('m');
                $Annee_depart_num = $Depart->format('Y');
                $Heure_depart = $Depart->format('H:i:s');
            }
            else
            {
                $Jour_depart_str = null;
                $Jour_depart_num = null;
                $Mois_depart_num = null;
                $Annee_depart_num = null;
                $Heure_depart = null;
            }

            $Demijournees = $checkin->getHalfDay();
            $Demijournees_offertes = $checkin->getFree();

            $Difference_depart_arrivee = $checkin->getDiff();
            $all_checkins[$line][] = [
                'jour_arrivee_str' => $Jour_arrivee_str,
                'jour_arrivee_num' => $Jour_arrivee_num,
                'mois_arrivee_num' => $Mois_arrivee_num,
                'annee_arrivee_num' => $Annee_arrivee_num,
                'heure_arrivee' => $Heure_arrivee,
                'jour_depart_str' => $Jour_depart_str,
                'jour_depart_num' => $Jour_depart_num,
                'mois_depart_num' => $Mois_depart_num,
                'annee_depart_num' => $Annee_depart_num,
                'heure_depart' => $Heure_depart,
                'demi_journees' => $Demijournees,
                'demi_journees_free' => $Demijournees_offertes,
                'diff_depart_arrivee' => $Difference_depart_arrivee
            ];
        }


        $all_days = [];

        foreach($all_checkins as $key => $days)
        {
            foreach($days as $day)
            {
                if(isset($all_days[$key]))
                {
                    $all_days[$key] += $day['demi_journees'] - $day['demi_journees_free'];
                }
                else
                {
                    $all_days[$key] = $day['demi_journees'] - $day['demi_journees_free'];
                }
            }
        }


        $all_adjustments = [];

        $adjustments = $this->halfDayAdjustmentRepository->findBy(
            ['customer_id' => $customer->getId()],
            ['id' => 'ASC']
        );

        foreach($adjustments as $adjustment)
        {
            $mois_exp = explode('-', $adjustment->getArrivalMonth());
            $array_key = $month[$mois_exp[1].''] . ' ' . $mois_exp[0];
            $all_adjustments[$array_key] = $adjustment;
        }

        return $this->render(
            'user/listing.html.twig',
            [
                'all_days' => $all_days,
                'all_checkins' => $all_checkins,
                'all_adjustments' => $all_adjustments,
                'halfDayPrice' => $halfDayPrice,
                'monthPrice' => $monthPrice
            ]
        );
    }
}
