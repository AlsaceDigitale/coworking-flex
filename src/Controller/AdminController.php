<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\HalfDayAdjustment;
use App\Form\HalfDayAdjustmentType;
use App\Repository\HalfDayAdjustmentRepository;
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
use App\Form\HalfDayType;
use App\Form\MonthType;
use App\Form\PlaceType;
use App\Form\PromoType;
use App\Form\CustomerType;
use App\Form\CustomerSettingStatusType;
use App\Form\TextHomeType;
use App\Repository\CustomerRepository;
use App\Repository\PromoRepository;
use phpDocumentor\Reflection\Types\Null_;

class AdminController extends AbstractController
{

    private $checkInRepository;
    private $customerRepository;
    private $subscriptionRepository;
    private $optionsRepository;
    private $promoRepository;
    private $halfDayAdjustmentRepository;
    private $om;

    public function __construct(
        CheckInRepository $checkInRepository,
        CustomerRepository $customerRepository,
        SubscriptionRepository $subscriptionRepository,
        OptionsRepository $optionsRepository,
        PromoRepository $promoRepository,
        HalfDayAdjustmentRepository $halfDayAdjustmentRepository,
        ObjectManager $om
    ) {
        $this->checkInRepository=$checkInRepository;
        $this->customerRepository=$customerRepository;
        $this->subscriptionRepository=$subscriptionRepository;
        $this->optionsRepository=$optionsRepository;
        $this->promoRepository=$promoRepository;
        $this->halfDayAdjustmentRepository=$halfDayAdjustmentRepository;
        $this->om=$om;
    }

    /**
     * @Route("/admin/home", name="admin_home")
     */
    public function adminHome()
    {
        return $this->render('admin/home.html.twig');
    }


    /**
     * @Route("/admin/list", name="admin_list")
     */
    public function list()
    {
        return $this->render(
            'admin/list.html.twig',
            [
                'customers' => $this->customerRepository->findAll()
            ]
        );
    }

    /**
     * @Route("/admin/present", name="admin_present")
     */
    public function present()
    {
        return $this->render(
            'admin/present.html.twig',
            [
                'checkins' => $this->checkInRepository->findBy(['leaving' => null])
            ]
        );
    }

    /**
     * @Route("/admin/activation", name="admin_activation")
     */
    public function activation()
    {
        return $this->render(
            'admin/activation.html.twig',
            [
                'subscriptions' => $this->subscriptionRepository->findBy(['active' => 0])
            ]
        );
    }

    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("/admin/activate/{id}", name="admin_activate")
     */
    public function activate($id)
    {
        $customer = $this->subscriptionRepository->findOneBy(
            [
                'customer' => $id
            ]
        );
        $promo = $this->promoRepository->findOneBy(
            [
                'customer' => $id
            ]
        );
        if ($customer->getActive() == 0) {
            $customer->setActive(1);
            $promo->setCounter($promo->getCounter() + 0);
        } else {
            $customer->setActive(0);
        }
        $this->om->persist($customer);
        $this->om->persist($promo);
        $this->om->flush();

        return $this->redirect($_SERVER['HTTP_REFERER']);
    }

    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/admin/profile/{id}", name="admin_profile")
     */
    public function profile($id, Request $request)
    {
        $customer = $this->customerRepository->findOneBy(
            [
                'id' => $id
            ]
        );

        $subscription = $this->subscriptionRepository->findOneBy(
            [
                'customer' => $id
            ]
        );

        $promo = $this->promoRepository->findOneBy(
            [
                'customer' => $id
            ]
        );

        $counter = $this->createForm(PromoType::class, $promo);
        $counter->handleRequest($request);

        if ($counter->isSubmitted() && $counter->isValid()) {
            $this->om->persist($promo);
            $this->om->flush();
        };

        $status = $this->createForm(CustomerSettingStatusType::class, $customer);
        $status->handleRequest($request);

        if ($status->isSubmitted() && $status->isValid()) {
            $this->om->persist($customer);
            $this->om->flush();
        }

        return $this->render(
            'admin/profile.html.twig',
            [
                'customer' => $customer,
                'subscription' => $subscription,
                'formPromo' => $counter->createView(),
                'formStatus' => $status->createView()
            ]
        );
    }

    /**
     * @Route("/admin/raz", name="admin_raz")
     */
    public function raz()
    {

        return $this->render('admin/raz.html.twig');
    }

    /**
     * @Route("/admin/boom", name="admin_boom")
     */
    public function boom()
    {

        $customers = $this->customerRepository->findBy(
            [
                'role' => 'ROLE_USER'
            ]
        );


        foreach ($customers as $customer) {
            $subscriptions = $this->subscriptionRepository->findOneBy(
                [
                    'customer' => $customer->getId()
                ]
            );
            $raz = $subscriptions->setActive(0);
            $this->om->persist($raz);
            $this->om->flush();
        }

        return $this->render('admin/raz.html.twig');
    }

    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/admin/switchrole/{id}", name="admin_switchrole")
     */
    public function switch($id)
    {
        $customer = $this->customerRepository->find($id);
        if ($customer->getRole() == 'ROLE_USER') {
            $switch = $customer->setRole('ROLE_ADMIN');
        } else {
            $switch = $customer->setRole('ROLE_USER');
        }
        $this->om->persist($switch);
        $this->om->flush();

        return $this->redirect($_SERVER['HTTP_REFERER']);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/admin/text", name="admin_text")
     */
    public function text(Request $request)
    {
        $text = $this->optionsRepository->findOneBy(
            [
                'label' => 'Text'
            ]
        );
        $place = $this->optionsRepository->findOneBy(
            [
                'label' => 'Place'
            ]
        );
        $halfday = $this->optionsRepository->findOneBy(
            [
                'label' => 'HalfDay'
            ]
        );
        $month = $this->optionsRepository->findOneBy(
            [
                'label' => 'Month'
            ]
        );

        $formtext = $this->createForm(TextHomeType::class, $text);
        $formtext->handleRequest($request);

        if ($formtext->isSubmitted() && $formtext->isValid()) {
            $this->om->persist($text);
            $this->om->flush();

            $this->addFlash(
                'option',
                'Texte d\'accueil modifié avec succés'
            );
        }

        $formplace = $this->createForm(PlaceType::class, $place);
        $formplace->handleRequest($request);

        if ($formplace->isSubmitted() && $formplace->isValid()) {
            $this->om->persist($place);
            $this->om->flush();

            $this->addFlash(
                'option',
                'Nombre de place modifié avec succés'
            );
        }

        $formhalfday = $this->createForm(HalfDayType::class, $halfday);
        $formhalfday->handleRequest($request);

        if ($formhalfday->isSubmitted() && $formhalfday->isValid()) {
            $this->om->persist($halfday);
            $this->om->flush();

            $this->addFlash(
                'option',
                'Prix à la demie-journée modifié avec succés'
            );
        }

        $formmonth = $this->createForm(MonthType::class, $month);
        $formmonth->handleRequest($request);

        if ($formmonth->isSubmitted() && $formmonth->isValid()) {
            $this->om->persist($month);
            $this->om->flush();

            $this->addFlash(
                'option',
                'Prix au mois modifié avec succés'
            );
        }


        return $this->render(
            'admin/text.html.twig',
            [
                'text' => $text,
                'form' => $formtext->createView(),
                'formplace' => $formplace->createView(),
                'formhalfday' => $formhalfday->createView(),
                'formmonth' => $formmonth->createView(),
            ]
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("/admin/textactive", name="admin_textactive")
     */
    public function textActive()
    {
        $option = $this->optionsRepository->findOneBy(
            [
                'label' => 'Text'
            ]
        );
        if ($option->getActive()) {
            $option->setActive(0);
        } else {
            $option->setActive(1);
        }
        $this->om->persist($option);
        $this->om->flush();

        return $this->redirect($_SERVER['HTTP_REFERER']);
    }

    /**
     * @Route("/admin/price", name="admin_price")
     * @param OptionsRepository $optionsRepository
     * @param Request $request
     */
    public function price(OptionsRepository $optionsRepository, Request $request)
    {

        $data = 0;
        if (!empty($_POST)) {
            $data = $_POST['searchMonth'];
        }

        $checkins = $this->checkInRepository->findBy([
            'arrival_month' => $data
        ]);

        $days = [];
        $free= [];
        $customers = [];
        $count_attendance = [];
        foreach ($checkins as $key => $checkin) {
            $customer = $this->customerRepository->findOneBy([
                'role' => 'ROLE_USER',
                'id' => $checkin->getCustomer()
            ]);
            $customer_id = $customer->getId();

            if ($checkin->getHalfDay() == 1) {
                if (isset($days[$customer_id])) {
                    $days[$customer_id] += 1;
                } else {
                    $days[$customer_id] = 1;
                }
            } elseif ($checkin->getHalfDay() == 2) {
                if (isset($days[$customer_id])) {
                    $days[$customer_id] += 2;
                } else {
                    $days[$customer_id] = 2;
                }
            } elseif ($checkin->getHalfDay() == 0) {
                if (!isset($days[$customer_id])) {
                    $days[$customer_id] = 0; 
                }
            };
            
            $days[$customer_id] -= $checkin->getFree();
            if (isset($free[$customer_id])) {
                $free[$customer_id] += $checkin->getFree();
            } else {
                $free[$customer_id] = $checkin->getFree();
            }


            if (!in_array($customer, $customers)) {
                $customers[] = $customer;
            }
        }
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


        // !!!!!!!!!!!!!!!!!!!!   recherche par mois


        $checkins = $this->checkInRepository->findAll();
        $dates = [];

        foreach ($checkins as $key => $checkin) {
            $date = $checkin->getArrival()->format('Y-m');
            if (!in_array($date, $dates)) {
                $dates[] = $date;
            }
        }

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

        // For the detailed display of the customer's checkins in the facturation table  
        $all_checkins = [];
        foreach ($customers as $key => $customer) {
            $user_checkins = $this->checkInRepository->findBy(
                ['customer' => $customer, 'arrival_month' => $data],
                ['id' => 'DESC']
            );

            $tab = [];
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
                $Jour_arrivee_num = $Arrivee->format('D');
                $Mois_arrivee_num = $Arrivee->format('M');
                $Annee_arrivee_num = $Arrivee->format('Y');
                $Heure_arrivee = $Arrivee->format('H:i:s');
                
                $Depart = $checkin->getLeaving();
                $Jour_depart_str = $jours[$Depart->format('D')];
                $Jour_depart_num = $Depart->format('D');
                $Mois_depart_num = $Depart->format('M');
                $Annee_depart_num = $Depart->format('Y');
                $Heure_depart = $Depart->format('H:i:s');

                $Demijournees = $checkin->getHalfDay();
                $Demijournees_offertes = $checkin->getFree();
                
                $Difference_depart_arrivee = $checkin->getDiff();
                $tab[$line][] = [
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
            $all_checkins[$customer->getId()] = $tab;
        }


        // Sophie's wink ;)
        $song_sophie = [
            1 => 'Du temps de votre vie,',
            2 => 'Vous vous appeliez Sophie',
            3 => 'Et vous étiez jolie',
            4 => 'Mademoiselle Sophie',
            5 => 'Oh, Sophie, Sophie'
        ];

        $song_explain = [
            'title' => "Sophie",
            'author' => "Edith Piaf",
            'film' => "Neuf Garçons, un coeur",
            'date' => 1947
        ];

        // In order to adjust the total amount of halfdays of a customer during the selected month
        $ajustement = new HalfDayAdjustment();

        $formHalfDayAdjustment = $this->createForm(HalfDayAdjustmentType::class, $ajustement);
        $formHalfDayAdjustment->handleRequest($request);

        if ($formHalfDayAdjustment->isSubmitted() && $formHalfDayAdjustment->isValid()) {
            $this->om->persist($ajustement);
            $this->om->flush();
        }

        $all_adjustments = [];

        foreach ($customers as $key => $customer) {
            $all_adjustments[$customer->getId()] = $this->halfDayAdjustmentRepository->findOneBy(
                ['customer_id' => $customer->getId(), 'arrival_month' => $data],
                ['id' => 'DESC']
            );
        };
        
        return $this->render(
            'admin/facturation.html.twig',
            [
                'customers' => $customers,
                'days' => $days,
                'price' => $this->optionsRepository->findOneBy([
                    'label' => 'HalfDay'
                ]),
                'month' => $this->optionsRepository->findOneBy([
                    'label' => 'Month'
                ]),
                'dates' => $dates,
                'change' => $month,
                'data' => $data,
                'free' => $free,
                'all_checkins' => $all_checkins,
                'song_sophie' => $song_sophie,
                'song_explain' => $song_explain,
                'formHalfDayAdjustment' => $formHalfDayAdjustment->createView(),
                'all_adjustments' => $all_adjustments
            ]
        );
    }
}
