<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Prestation;
use App\Entity\User;
use App\Form\AppointmentType;
use Mailjet\Resources;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotBlank;
use Twilio\Rest\Client;

class AppointmentController extends AbstractController
{
    private $mj;

    public function __construct(){
        $this->mj = new \Mailjet\Client($_ENV['MJ_APIKEY_PUBLIC'],$_ENV['MJ_APIKEY_PRIVATE'],true,['version' => 'v3.1']);
    }

    /**
     * @Route("/reserver/{idDoctor}/{idPrestation}", name="reserver_rendez_vous")
     */
    public function reserverRendezVousAction(Request $request, $idDoctor, $idPrestation)
    {
        $repositoryUser = $this->getDoctrine()->getRepository(User::class);
        /** @var User $doctor */
        $doctor = $repositoryUser->find($idDoctor);

        if (!$doctor->getEnabledByAdmin()){
            $this->get('session')->getFlashBag()->add('danger', 'Ce médecin n\'est pas visible pour le moment.');
            return $this->redirectToRoute('search_doctor');
        }

        /** @var Prestation $prestation */
        $prestation = $this->getDoctrine()->getRepository(Prestation::class)->find($idPrestation);

        /** @var User $currentUser */
        $currentUser = $this->getUser();
        /** @var Appointment $appointment */
        $appointment = new Appointment();

        $form = $this->createFormBuilder($appointment)
            ->add('phoneNumberPatient', TelType::class, ['label' => "Numéro de téléphone du patient"])
            ->add('emailPatient', TextType::class, ['label' => "Adresse e-mail du patient"])
            ->add('namePatient', TextType::class, ['label' => "Nom et prénom du patient"])
            ->add('Reserver', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);


        if ($request->isMethod('POST')){
            $em = $this->getDoctrine()->getManager();

            $appointment->setState(Appointment::STATUS_UNPAID);
            $appointment->setPrestation($prestation);
            $appointment->setDoctor($doctor);
            $appointment->setBuyer($currentUser);

            $doctor->addAppointmentAsDoctor($appointment);
            $currentUser->addAppointmentAsBuyer($appointment);

            $em->persist($appointment);
            $em->flush();

            return $this->redirectToRoute('reserver_payer', ['idAppointment' => $appointment->getId()]);
        }

        return $this->render('appointment/reserver_rendez_vous.html.twig', [
            'doctor' => $doctor,
            'prestation' => $prestation,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/payer/{idAppointment}", name="reserver_payer")
     */
    public function reserverPayerAction(Request $request, $idAppointment)
    {
        /** @var Appointment $appointment */
        $appointment = $this->getDoctrine()->getRepository(Appointment::class)->find($idAppointment);

        $prestation = $this->getDoctrine()->getRepository(Prestation::class)->find($appointment->getPrestation()->getId());

        $form = $this->get('form.factory')
            ->createNamedBuilder('payment-form')
            ->add('token', HiddenType::class, [
                'constraints' => [new NotBlank()],
            ])
            ->add('submit', SubmitType::class, ['label' => "Payer", 'attr' => ['class' => "genric-btn info circle arrow text-center"]])
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isValid()) {

                $token = $form->getData()['token'];
                $stripe = new \Stripe\StripeClient(
                    $_ENV['STRIPE_SECRET_KEY']
                );
                $intent = $stripe->paymentIntents->create([
                    'amount' => $appointment->getPrestation()->getPrice() * 100 + 300,
                    'currency' => 'eur',
                    'payment_method_types' => ['card'],
                    'capture_method' => 'manual'
                ]);
                $appointment->setPaymentIntentId($intent['id']);
                $appointment->setState(Appointment::STATUS_PAID);

                $body = [
                    'Messages' => [ // Email pour le patient
                        ['From' => [ 'Email' => "contact@sante-universelle.org", 'Name' => "Santé universelle" ],
                            'To' => [['Email' => $appointment->getEmailPatient(), 'Name' => $appointment->getNamePatient()]],
                            'TemplateID' => 2042511,
                            'TemplateLanguage' => true,
                            'Variables' => [
                                'appointment' => $appointment,
                                'buyerUsername' => $this->getUser()->getUsername(),
                                'doctorUsername' => $appointment->getDoctor()->getUsername(),
                                'doctorEmail' => $appointment->getDoctor()->getEmailCanonical(),
                                'doctorPhoneNumber' => $appointment->getDoctor()->getPhoneNumber()
                            ]
                        ], // Email pour le médecin
                        ['From' => [ 'Email' => "contact@sante-universelle.org", 'Name' => "Santé universelle" ],
                            'To' => [['Email' => $appointment->getDoctor()->getEmailCanonical(), 'Name' => $appointment->getDoctor()->getUsername()]],
                            'TemplateID' => 2042515,
                            'TemplateLanguage' => true,
                            'Variables' => [
                                'phoneNumberPatient' => $appointment->getPhoneNumberPatient(),
                                'namePatient' => $appointment->getNamePatient(),
                                'emailPatient' => $appointment->getEmailPatient(),
                                'buyerUsername' => $this->getUser()->getUsername()
                            ]
                        ]
                    ]
                ];
                $response = $this->mj->post(Resources::$Email, ['body' => $body]);

                $this->sendSMS($appointment->getPhoneNumberPatient(),
                    "Santé universelle : " . $appointment->getBuyer()->getUsername() . ' vous offre un rendez-vous chez le médecin ' . $appointment->getDoctor()->getUsername() .
                    ' situé à cette adresse : ' . $appointment->getDoctor()->getAdress() . '. Coordonnées du médecin : ' . $appointment->getDoctor()->getEmailCanonical() . " " . $appointment->getDoctor()->getPhoneNumber()
            );
                $this->sendSMS($appointment->getDoctor()->getPhoneNumber(),
                    "Santé universelle : Nouvelle réservation pour \"" . $appointment->getPrestation()->getName() .
                    "\". Coordonnées du patient : " . $appointment->getEmailPatient() . ' ' . $appointment->getPhoneNumberPatient());

                $this->get('session')->getFlashBag()->add('success', 'Le rendez-vous a bien été payé. Le patient a reçu un SMS et un email lui indiquant la marche à suivre.');
                $em = $this->getDoctrine()->getManager();
                $em->persist($appointment);
                $em->flush();

                return $this->redirectToRoute('mes_rendez_vous');
            }
        }

        return $this->render('appointment/reserver_payer.html.twig', [
            'appointment' => $appointment,
            'prestation' => $prestation,
            'stripe_public_key' => $_ENV['STRIPE_PUBLIC_KEY'],
            'form' => $form->createView()
        ]);
    }

    /** @Route("/mes-rendez-vous", name="mes_rendez_vous") */
    public function mesRendezVousAction()
    {
        $user = $this->getUser();
        $appointmentsAsBuyer = $this->getDoctrine()->getRepository(Appointment::class)->findBy(['buyer' => $user]);
        $appointmentsAsDoctor = $this->getDoctrine()->getRepository(Appointment::class)->findBy(['doctor' => $user]);

        return $this->render('appointment/mes_rendez_vous.html.twig', [
            'appointmentsAsBuyer' => array_reverse($appointmentsAsBuyer),
            'appointmentsAsDoctor' => array_reverse($appointmentsAsDoctor)
        ]);
    }

    /** @Route("/mes-rendez-vous/{id}", name="rendez_vous_individuel") */
    public function rendezVousIndividuelAction(Request $request, $id)
    {
        /** @var Appointment $appointment */
        $appointment = $this->getDoctrine()->getRepository(Appointment::class)->find($id);

        $user = $this->getUser();

        if ($appointment->getDoctor() == $user){
            return $this->render('appointment/rendez_vous_individuel_as_docteur.html.twig', [
                'appointment' => $appointment
            ]);
        } elseif ($appointment->getBuyer() == $user){
            return $this->render('appointment/rendez_vous_individuel.html.twig', [
                'appointment' => $appointment
            ]);
        } else {
            $this->get('session')->getFlashBag()->add('danger', 'Vous n\'êtes ni médecin, ni acheteur de ce rendez-vous.');
            return $this->redirectToRoute("mes_rendez_vous");
        }
    }

    /** @Route("confirmer-rendez-vous-done/{id}", name="confirmer_rendez_vous_done") */
    public function confirmerRendezVousDoneAction($id){
        /** @var Appointment $appointment */
        $appointment = $this->getDoctrine()->getRepository(Appointment::class)->find($id);
        $appointment->setState(Appointment::STATUS_DONE);

        $em = $this->getDoctrine()->getManager();

        $em->persist($appointment);
        $em->flush();

        return $this->redirectToRoute("mes_rendez_vous");
    }

    public function captureFunds($id, $price){
        $stripe = new \Stripe\StripeClient($_ENV['STRIPE_SECRET_KEY']);

        $stripe->paymentIntents->confirm(
            $id,
            ['payment_method' => 'pm_card_visa']
        );

        $stripe->paymentIntents->capture($id, []);
//        $intent->capture(['amount_to_capture' => $price*100]);
    }

    public function sendSMS($to, $message){
        $account_sid = $_ENV["TWILIO_SID"];
        $auth_token = $_ENV["TWILIO_TOKEN"];

        $twilio_number = $_ENV["TWILIO_NUMBER"];

        $twilio = new Client($account_sid, $auth_token);
        $message = $twilio->messages
            ->create($to, // to
                ["body" => $message, "from" => $twilio_number]
            );
    }
}
