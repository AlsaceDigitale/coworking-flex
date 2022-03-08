<?php

namespace App\Controller;

use App\Entity\CheckIn;
use App\Entity\Customer;
use App\Entity\ExportPeriod;
use App\Entity\HalfDayAdjustment;
use App\Form\EditCustomerType;
use App\Entity\HomeTexts;
use App\Entity\Options;
use App\Form\ExportPeriodType;
use App\Form\HalfDayAdjustmentType;
use App\Form\TermsOfUseType;
use App\Form\TextRGPDType;
use App\Repository\HalfDayAdjustmentRepository;
use App\Repository\CheckInRepository;
use App\Repository\OptionsRepository;
use App\Repository\SubscriptionRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Swift_Mailer;
use Swift_Message;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\HalfDayType;
use App\Form\MonthType;
use App\Form\PlaceType;
use App\Form\PromoType;
use App\Form\CustomerSettingStatusType;
use App\Form\TextHomeType;
use App\Repository\CustomerRepository;
use App\Repository\PromoRepository;

class AdminController extends AbstractController
{

    private $adminMail = "coworking-flex@alsacedigitale.org";
    private $checkInRepository;
    private $customerRepository;
    private $subscriptionRepository;
    private $optionsRepository;
    private $promoRepository;
    private $halfDayAdjustmentRepository;
    private $manager;

    public function __construct(
        CheckInRepository $checkInRepository,
        CustomerRepository $customerRepository,
        SubscriptionRepository $subscriptionRepository,
        OptionsRepository $optionsRepository,
        PromoRepository $promoRepository,
        HalfDayAdjustmentRepository $halfDayAdjustmentRepository,
        EntityManagerInterface $manager
    )
    {
        $this->checkInRepository = $checkInRepository;
        $this->customerRepository = $customerRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->optionsRepository = $optionsRepository;
        $this->promoRepository = $promoRepository;
        $this->halfDayAdjustmentRepository = $halfDayAdjustmentRepository;
        $this->manager=$manager;
    }

    /**
     * @Route("/admin/home", name="admin_home")
     * @return Response
     */
    public function adminHome(): Response
    {
        ($this->getUser())->setLastActivityAt(new DateTime());
        $this->manager->persist($this->getUser());
        $this->manager->flush();

        return $this->render('admin/home.html.twig');
    }


    /**
     * @Route("/admin/list", name="admin_list")
     * @param Request $request
     * @return Response
     */
    public function list(Request $request): Response
    {
        $exportPeriod = new ExportPeriod();
        $form = $this->createForm(ExportPeriodType::class, $exportPeriod);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($exportPeriod->getBeginDate() >= $exportPeriod->getEndDate()) {
                $this->addFlash('error', 'La date de fin de la période ne peut être supérieur à la date de début');
            }
            $users = $this->customerRepository->findAllWithCheckinWithinPeriod($exportPeriod->getBeginDate(), $exportPeriod->getEndDate());
            $rows = [];
            $rows[] = "Id;E-mail;Pseudo;Prénom;Nom;Numéro de téléphone;Adresse;Ville;Code Postal;Pays;Rôle;Statut;Somme des checkins";

            /** @var Customer $user */
            foreach ($users as $user) {
                $diffSum = 0;
                /** @var CheckIn $checkIn */
                foreach ($user->getCheckIns()->getValues() as $checkIn) {
                    $time = explode(':', $checkIn->getDiff()->format('H:i:s'));
                    $diffSum += ($time[0] * 60) + ($time[1]) + ($time[2] / 60);
                }
                $row = [
                    $user->getId(),
                    $user->getMail(),
                    $user->getUsername(),
                    $user->getFirstname(),
                    $user->getLastname(),
                    $user->getPhone(),
                    $user->getAddress(),
                    $user->getCity(),
                    $user->getZip(),
                    $user->getCountry(),
                    $user->getRole(),
                    $user->getStatus(),
                    (new DateTime())->setTime(floor($diffSum / 60), $diffSum % 60)->format('H:i')
                ];
                $rows[] = implode(';', $row);
            }
            $fileName = 'users_factures_' .
                $exportPeriod->getBeginDate()->format('Y-m-d') . '_' .
                $exportPeriod->getBeginDate()->format('Y-m-d') . '.csv';

            return new Response(implode(PHP_EOL, $rows), 200, [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename=' . $fileName,
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);
        }

        return $this->render('admin/list.html.twig', [
            'customers' => $this->customerRepository->findAll(),
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/admin/present", name="admin_present")
     * @return Response
     */
    public function present(): Response
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
     * @return Response
     */
    public function activation(): Response
    {
        return $this->render('admin/activation.html.twig', [
            'subscriptions' => $this->subscriptionRepository->findBy(['active' => 0])
        ]);
    }

    /**
     * @param Customer $customer
     * @param Swift_Mailer $mailer
     * @return RedirectResponse
     * @Route("/admin/activate/{id}", name="admin_activate")
     * @return Response
     */
    public function activate(Customer $customer, Swift_Mailer $mailer): Response
    {
        $subscription = $this->subscriptionRepository->findOneBy(['customer' => $customer->getId()
            ]);
        $promo = $this->promoRepository->findOneBy(['customer' => $customer->getId()
            ]);
        if ($subscription->getActive() == 0) {
            $subscription->setActive(1);
            $promo->setCounter($promo->getCounter() + 0);

            $message = (new Swift_Message('Modification de votre mot de passe'))
                ->setFrom($this->adminMail)
                ->setTo($customer->getMail())
                ->setBody(
                    $this->renderView(
                        'admin/mail-activation.html.twig',
                        [
                            'name' => $customer->getFirstname(),
                        ]
                    ),
                    'text/html'
                );
            $mailer->send($message);
        } else {
            $subscription->setActive(0);
        }
        $this->manager->persist($subscription);
        $this->manager->persist($promo);
        $this->manager->flush();

        return $this->redirect($_SERVER['HTTP_REFERER']);
    }

    /**
     * @param $id
     * @param Request $request
     * @return Response
     * @Route("/admin/profile/{id}", name="admin_profile")
     */
    public function profile($id, Request $request): Response
    {
        $customer = $this->customerRepository->findOneBy(['id' => $id]);
        $subscription = $this->subscriptionRepository->findOneBy(['customer' => $id]);
        $promo = $this->promoRepository->findOneBy(['customer' => $id]);

        $counter = $this->createForm(PromoType::class, $promo);
        $counter->handleRequest($request);
        if ($counter->isSubmitted() && $counter->isValid()) {
            $this->manager->persist($promo);
            $this->manager->flush();
        }

        $customerForm = $this->createForm(EditCustomerType::class, $customer);
        $customerForm->handleRequest($request);

        if ($counter->isSubmitted() && $counter->isValid()) {
            $this->manager->persist($customer);
            $this->manager->flush();
        }

        $status = $this->createForm(CustomerSettingStatusType::class, $customer);
        $status->handleRequest($request);

        if ($status->isSubmitted() && $status->isValid()) {
            $this->manager->persist($customer);
            $this->manager->flush();
        }

        return $this->render(
            'admin/profile.html.twig',
            [
                'customer' => $customer,
                'customerForm' => $customerForm->createView(),
                'subscription' => $subscription,
                'formPromo' => $counter->createView(),
            ]
        );
    }

    /**
     * @Route("/admin/raz", name="admin_raz")
     * @return Response
     */
    public function raz(): Response
    {
        return $this->render('admin/raz.html.twig');
    }

    /**
     * @Route("/admin/boom", name="admin_boom")
     * @return Response
     */
    public function boom(): Response
    {
        $customers = $this->customerRepository->findBy(['role' => 'ROLE_USER']);

        foreach ($customers as $customer) {
            $subscriptions = $this->subscriptionRepository->findOneBy(['customer' => $customer->getId()]);
            $raz = $subscriptions->setActive(0);
            $this->manager->persist($raz);
            $this->manager->flush();
        }

        return $this->render('admin/raz.html.twig');
    }

    /**
     * @param $id
     * @return Response
     * @Route("/admin/switchrole/{id}", name="admin_switchrole")
     */
    public function switch($id): Response
    {
        $customer = $this->customerRepository->find($id);
        if ($customer->getRole() == 'ROLE_USER') {
            $switch = $customer->setRole('ROLE_ADMIN');
        } else {
            $switch = $customer->setRole('ROLE_USER');
        }
        $this->manager->persist($switch);
        $this->manager->flush();

        return $this->redirect($_SERVER['HTTP_REFERER']);
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/admin/text", name="admin_text")
     */
    public function text(Request $request)
    {
        $texts = $this->optionsRepository->findBy(['label' => 'Text']);
        while (count($texts) < 3) {
            $newText = new Options();
            $newText->setLabel('Text')
                ->setActive(false)
                ->setContent('Entrez votre texte ici.');
            $this->manager->persist($newText);
            $this->manager->flush();
            $texts[] = $newText;
        }

        $rgpd = $this->optionsRepository->findOneBy(['label' => 'rgpd']);
        $place = $this->optionsRepository->findOneBy(['label' => 'Place']);
        $halfday = $this->optionsRepository->findOneBy(['label' => 'HalfDay']);
        $month = $this->optionsRepository->findOneBy(['label' => 'Month']);
        $termsOfUse = $this->optionsRepository->findOneBy(['label' => 'TermsOfUse']);

        if (!$rgpd) {
            dump($rgpd);
            $rgpd = new Options();
            $rgpd->setLabel('rgpd')
                ->setContent('Entrez votre texte ici');
            $this->manager->persist($rgpd);
            $this->manager->flush();
        }

        if (!$termsOfUse) {
            $termsOfUse = new Options();
            $termsOfUse->setLabel('TermsOfUse')
                ->setContent('Terms of Use draft');
            $this->manager->persist($termsOfUse);
            $this->manager->flush();
        }

        $formTermsOfUse = $this->createForm(TermsOfUseType::class, $termsOfUse);
        $formTermsOfUse->handleRequest($request);

        if ($formTermsOfUse->isSubmitted() && $formTermsOfUse->isValid()) {
            $this->manager->persist($termsOfUse);
            $this->manager->flush();

            $this->addFlash(
                'option',
                'Conditions d\'utilisation modifiées avec succés'
            );
        }

        $homeTexts = new HomeTexts($texts[0], $texts[1], $texts[2]);
        $formText = $this->createForm(TextHomeType::class, $homeTexts);
        $formText->handleRequest($request);
        if ($formText->isSubmitted() && $formText->isValid()) {
            foreach ($homeTexts->getData() as $k => $data) {
                if ($data['file']) {
                    $texts[$k]->setPictureFile($data['file']);
                }
                if ($texts[$k]->getContent() === $data['text']) {
                    // https://github.com/dustin10/VichUploaderBundle/issues/8 >
                    // Un fichier ne peut-être persistée si aucun autre champ n'a été modifié dans l'entité.
                    // On modifie ici le texte en ajoutant un espace insécable (si le texte est inchangé),
                    // permettant de forcer la persistance du fichier
                    $texts[$k]->setContent($data['text'] . "&nbsp");
                } else {
                    $texts[$k]->setContent($data['text']);
                }
                $texts[$k]->setActive($data['active']);
            }

            $this->manager->flush();
            $this->addFlash(
                'option',
                'Texte d\'accueil modifié avec succès'
            );
        }

        $formRgpd = $this->createForm(TextRGPDType::class, $rgpd);
        $formRgpd->handleRequest($request);
        if ($formRgpd->isSubmitted() && $formRgpd->isValid()) {
            $this->manager->persist($rgpd);
            $this->manager->flush();

            $this->addFlash(
                'option',
                'Texte RGPD modifié avec succès'
            );
        }

        $formplace = $this->createForm(PlaceType::class, $place);
        $formplace->handleRequest($request);

        if ($formplace->isSubmitted() && $formplace->isValid()) {
            $this->manager->persist($place);
            $this->manager->flush();

            $this->addFlash(
                'option',
                'Nombre de place modifié avec succés'
            );
        }

        $formhalfday = $this->createForm(HalfDayType::class, $halfday);
        $formhalfday->handleRequest($request);

        if ($formhalfday->isSubmitted() && $formhalfday->isValid()) {
            $this->manager->persist($halfday);
            $this->manager->flush();

            $this->addFlash(
                'option',
                'Prix à la demie-journée modifié avec succés'
            );
        }

        $formmonth = $this->createForm(MonthType::class, $month);
        $formmonth->handleRequest($request);

        if ($formmonth->isSubmitted() && $formmonth->isValid()) {
            $this->manager->persist($month);
            $this->manager->flush();

            $this->addFlash(
                'option',
                'Prix au mois modifié avec succès'
            );
        }

        return $this->render(
            'admin/text.html.twig',
            [
                'text1' => $texts[0],
                'text2' => $texts[1],
                'text3' => $texts[2],
                'formRgpd' => $formRgpd->createView(),
                'form' => $formText->createView(),
                'formplace' => $formplace->createView(),
                'formhalfday' => $formhalfday->createView(),
                'formmonth' => $formmonth->createView(),
                'formTermsOfUse' => $formTermsOfUse->createView()
            ]
        );
    }

    /**
     * @return RedirectResponse
     * @Route("/admin/textactive", name="admin_textactive")
     */
    public function textActive(): RedirectResponse
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
        $this->manager->persist($option);
        $this->manager->flush();

        return $this->redirect($_SERVER['HTTP_REFERER']);
    }

    /**
     * @Route("/admin/price", name="admin_price")
     * @param OptionsRepository $optionsRepository
     * @param Request $request
     * @return Response
     */
    public function price(OptionsRepository $optionsRepository, Request $request): Response
    {
        $checkins = $this->checkInRepository->findAll();
        $dates = [];

        foreach ($checkins as $key => $checkin) {
            $date = $checkin->getArrival()->format('Y-m');
            if (!in_array($date, $dates)) {
                $dates[] = $date;
            }
        }

        $data = 0;
        if (!empty($_POST)) {
            $data = $_POST['searchMonth'];
        } elseif (count($dates) > 0) {
            $data = end($dates);
        }

        $checkins = $this->checkInRepository->findBy([
            'arrival_month' => $data
        ]);

        $days = [];
        $free = [];
        $customers = [];
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
            }

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
                foreach ($this->checkInRepository->findLikeDate($annee . '-' . $mois, $customer->getId()) as $result) {
                    $prix += ($result->getHalfDay() - $result->getFree()) * $halfDayPrice;
                }
                if ($prix > $monthPrice) {
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
                if ($Depart != null) {
                    $Jour_depart_str = $jours[$Depart->format('D')];
                    $Jour_depart_num = $Depart->format('d');
                    $Mois_depart_num = $Depart->format('m');
                    $Annee_depart_num = $Depart->format('Y');
                    $Heure_depart = $Depart->format('H:i:s');
                } else {
                    $Jour_depart_str = null;
                    $Jour_depart_num = null;
                    $Mois_depart_num = null;
                    $Annee_depart_num = null;
                    $Heure_depart = null;
                }

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
            $this->manager->persist($ajustement);
            $this->manager->flush();
        }

        $all_adjustments = [];

        foreach ($customers as $key => $customer) {
            $all_adjustments[$customer->getId()] = $this->halfDayAdjustmentRepository->findOneBy(
                ['customer_id' => $customer->getId(), 'arrival_month' => $data],
                ['id' => 'DESC']
            );
        }

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
