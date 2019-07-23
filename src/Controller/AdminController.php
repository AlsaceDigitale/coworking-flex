<?php

namespace App\Controller;

use App\Form\HalfDayType;
use App\Form\MonthType;
use App\Form\PlaceType;
use App\Form\PromoType;
use App\Form\CustomerType;
use App\Form\CustomerSettingStatusType;
use App\Form\TextHomeType;
use App\Repository\CheckInRepository;
use App\Repository\CustomerRepository;
use App\Repository\OptionsRepository;
use App\Repository\PromoRepository;
use App\Repository\SubscriptionRepository;
use Doctrine\Common\Persistence\ObjectManager;
use phpDocumentor\Reflection\Types\Null_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{

    private $checkInRepository;
    private $customerRepository;
    private $subscriptionRepository;
    private $optionsRepository;
    private $promoRepository;
    private $om;

    public function __construct(
        CheckInRepository $checkInRepository,
        CustomerRepository $customerRepository,
        SubscriptionRepository $subscriptionRepository,
        OptionsRepository $optionsRepository,
        PromoRepository $promoRepository,
        ObjectManager $om
    ) {
        $this->checkInRepository=$checkInRepository;
        $this->customerRepository=$customerRepository;
        $this->subscriptionRepository=$subscriptionRepository;
        $this->optionsRepository=$optionsRepository;
        $this->promoRepository=$promoRepository;
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
            $promo->setCounter($promo->getCounter() + 4);
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
     */
    public function price()
    {

        $data = 0;
        if (!empty($_POST)) {
            $data = $_POST['searchMonth'];
        }

        $halfdays = $this->checkInRepository->findBy([
            'arrival_month' => $data
        ]);

        $days = [];
        $free= [];
        $customers = [];
        foreach ($halfdays as $key => $halfday) {
            $customer = $this->customerRepository->findOneBy([
                'role' => 'ROLE_USER',
                'id' => $halfday->getCustomer()
            ]);
            $id = $customer->getId();

            if ($halfday->getHalfDay() == 1) {
                if (isset($days[$id])) {
                    $days[$id] += 1;
                } else {
                    $days[$id] = 1;
                }
            } else {
                if (isset($days[$id])) {
                    $days[$id] += 2;
                } else {
                    $days[$id] = 2;
                }
            }
            $days[$id] -= $halfday->getFree();
            if (isset($free[$id])) {
                $free[$id] += $halfday->getFree();
            } else {
                $free[$id] = $halfday->getFree();
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
                'free' => $free
            ]
        );
    }
}
