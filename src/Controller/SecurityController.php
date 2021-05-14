<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\Promo;
use App\Entity\Subscription;
use App\Form\CustomerType;
use App\Form\ForgotPasswordType;
use App\Form\ForgotPasswordTypeMail;
use App\Form\LoginFormType;
use App\Repository\CustomerRepository;
use App\Repository\OptionsRepository;
use App\Service\Services;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Swift_Mailer;
use Swift_Message;
use Symfony\Component\HttpFoundation\RedirectResponse;
use function Sodium\add;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @var AuthenticationUtils
     */
    private $utils;
    private $adminMail = "coworking-flex@alsacedigitale.org";
    private $options;
    private $manager;

    /**
     * SecurityController constructor.
     *
     * @param AuthenticationUtils $utils
     */
    public function __construct(
        AuthenticationUtils $utils,
        OptionsRepository $options,
        EntityManagerInterface $manager
    ) {
        $this->utils = $utils;
        $this->options = $options;
        $this->manager = $manager;
    }

    /**
     * @Route("/", name="security_home")
     * @param Request      $request
     * @param Services     $services
     * @param Swift_Mailer $mailer
     * @return Response
     */
    public function index(
        Request $request,
        Services $services,
        Swift_Mailer $mailer
    ): Response {

        //!!!!!!!!!!!!!!!!!!!!   forgot password   !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

        $passForm = $this->createForm(ForgotPasswordTypeMail::class);
        $forgotForm = $request->request->get('forgot_password_type_mail');
        $customer = $this->getDoctrine()->getRepository(Customer::class)->findOneBy([
            'mail' => $forgotForm['mail']
        ]);
        $passForm->handleRequest($request);

        if ($passForm->isSubmitted() && $passForm->isValid()) {
            if ($customer) {
                $customer->setToken(md5($customer->getPassword()));
                $token = $customer->getToken();
                $this->getDoctrine()->getManager()->flush();

                $message = (new Swift_Message('Modification de votre mot de passe'))
                    ->setFrom($this->adminMail)
                    ->setTo($forgotForm['mail'])
                    ->setBody(
                        $this->renderView(
                            'security/forgotPasswordMail.html.twig', [
                                'name' => $customer->getFirstname(),
                                'pass' => $token
                            ]),
                        'text/html'
                    );
                $mailer->send($message);

                $this->addFlash(
                    'forgot_password',
                    'Mail envoyé'
                );
            } else {
                $this->addFlash(
                    'forgot_password_invalid',
                    'Cette adresse mail n\'existe pas'
                );
            }
        }
        //!!!!!!!!!!!!!!!!!!!!   récupérer le texte d'acceuil   !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        $options = $this->options->findBy(
            [
                'label' => 'Text',
                'active' => true
            ]
        );

        if ($this->getUser()) {
            ($this->getUser())->setLastActivityAt(new DateTime());
            $this->manager->persist($this->getUser());
            $this->manager->flush();

            if ($this->getUser()->getRole() == 'ROLE_USER') {
                return $this->redirectToRoute('user_home');
            } elseif ($this->getUser()->getRole() == 'ROLE_ADMIN') {
                return $this->redirectToRoute('admin_home');
            }
        }

        return $this->render(
            'security/index.html.twig',
            [
                'passForm' => $passForm->createView(),
                'last_username' => $this->utils->getLastUsername(),
                'error' => $this->utils->getLastAuthenticationError(),
                'place' => $services->countPlaces(),
                'texts' => $options
            ]
        );
    }

    /**
     * @Route("/rgpd", name="security_rgpd")
     */
    public function rgpd(): Response {
        $rgpd = $this->options->findOneBy(['label' => 'rgpd']);

        return $this->render('security/showOption.html.twig', [
            'option' => $rgpd,
            'title' => 'Les règles RGPD'
        ]);
    }

    /**
     * @Route("/terms-of-use", name="security_terms_of_use")
     */
    public function termsOfUse(): Response {
        $termsOfUse = $this->options->findOneBy(['label' => 'TermsOfUse']);

        return $this->render('security/showOption.html.twig', [
            'option' => $termsOfUse,
            'title' => 'Les conditions d\'utilisation'
        ]);
    }

    /**
     * @Route("/newPass/{pass}", name="new_pass")
     * @param Request $request
     * @param $user
     * @return Response
     */
    public function newPass(
        Request $request,
        $pass,
        UserPasswordEncoderInterface $encoder,
        CustomerRepository $customerRepository
    ) {
        $customer = $customerRepository->findOneBy(
            [
                'token' => $pass
            ]
        );

        if ($customer) {
            $form = $this->createForm(ForgotPasswordType::class, $customer);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $hash = $encoder->encodePassword($customer, $customer->getPassword());
                $customer->setPassword($hash)
                    ->setToken(null);

                $manager = $this->getDoctrine()->getManager();
                $manager->flush();

                $this->addFlash(
                    'forgot_password',
                    'Votre mot de passe a été modifié avec succès.'
                );

                return $this->redirectToRoute('security_home');
            }
            return $this->render(
                'security/forgotPassword.html.twig',
                [
                    'form' => $form->createView(),
                    'pass' => $pass
                ]
            );
        } else {
            $this->addFlash(
                'forgot_password_invalid',
                'Vous n\'êtes pas habilités à voguer sur cette mer.'
            );

            return $this->redirectToRoute('security_home');
        }
    }
    /**
     * @Route("/create", name="user_create", condition="request.isXmlHttpRequest()")
     * @param Request                      $request
     * @param UserPasswordEncoderInterface $encoder
     * @param Swift_Mailer                 $mailer
     * @param CustomerRepository           $customerRepository
     * @return RedirectResponse|Response
     */
    public function create(
        Request $request,
        UserPasswordEncoderInterface $encoder,
        Swift_Mailer $mailer,
        CustomerRepository $customerRepository
    ) {

        $customer = new Customer();
        $form = $this->createForm(
            CustomerType::class,
            $customer,
            ['action' => $this->generateUrl($request->get('_route'))]
        );
        $registration_man = $request->request->get('customer');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hash = $encoder->encodePassword($customer, $customer->getPassword());
            if ($customerRepository->findAll()) {
                $customer->setPassword($hash)
                    ->setCreatedAt(new DateTime())
                    ->setRole('ROLE_USER');
            } else {
                $customer->setPassword($hash)
                    ->setCreatedAt(new DateTime())
                    ->setRole('ROLE_ADMIN');
            }

            $subscription = new Subscription();
            $subscription->setActive(0)
                ->setCustomer($customer);

            $promo = new Promo();
            $promo->setCustomer($customer)
                ->setCounter(0);

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($customer);
            $manager->persist($subscription);
            $manager->persist($promo);
            $manager->flush();

            //  !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!  Mail pour prevenir le user lors d'une inscription   !!!!!!!!!

            $message = (new Swift_Message('Bienvenue sur l\'espace flex'))
                ->setFrom($this->adminMail)
                ->setTo($registration_man['mail'])
                ->setBody(
                    $this->renderView(
                        'security/registrationMail.html.twig', ['registration_man' => $registration_man]
                    ),
                    'text/html'
                );
            $mailer->send($message);

            //  !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!  Mail pour prevenir l'admin lors d'une inscription   !!!!!!!!!!

            $message2 = (new Swift_Message('Nouvel utilisateur sur l\'espace flex'))
                ->setFrom($this->adminMail)
                ->setTo(getenv('ADMIN_MAIL') ?: $this->adminMail)
                ->setBody($this->renderView('security/registrationMailAdmin.html.twig', [
                        'registration_man' => $registration_man
                    ]),
                    'text/html'
                );

            $mailer->send($message2);

            $this->addFlash(
                'registration_valid',
                'Inscription validée, un mail de confirmation vous a été envoyé.'
            );

            return new Response('success');
        }
        return $this->render('security/_create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/formlogin", name="form_login")
     * @return Response
     */
    public function formLogin(Request $request)
    {
        return $this->render('security/_login.html.twig');
    }

    /**
     * @Route("/login", name="security_login")
     * @return RedirectResponse
     */
    public function login()
    {
        return $this->redirectToRoute(
            'security_home',
            [
                'last_username' => $this->utils->getLastUsername(),
                'error' => $this->utils->getLastAuthenticationError()
            ]
        );
    }

    /**
     * @Route("/deconnect", name="security_logout")
     */
    public function logout()
    {
    }

    /**
     * @Route("/role", name="security_role")
     * @return RedirectResponse
     */
    public function roles()
    {
        $customer = $this->getUser();

        if ($customer->getRole() == "ROLE_USER") {
            return $this->redirectToRoute("user_home");
        } elseif ($customer->getRole() == "ROLE_ADMIN") {
            return $this->redirectToRoute("admin_home");
        }
    }

    // !!!!!!!!!!!!!!!!!!!!!!!!!!!!   Forget password   !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

    /**
     * @Route("/forgotPassword", name="forgot_password")
     * @return string
     */
    public function forgotPassword()
    {
        $form = $this->createForm(ForgotPasswordType::class);


        return $this->render('security/forgotPassword.html.twig');
    }
}
