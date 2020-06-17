<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Prestation;
use App\Entity\User;
use App\Form\AppointmentType;
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

    /**
     * @Route("/reserver/{idDoctor}/{idPrestation}", name="reserver_rendez_vous")
     */
    public function reserverRendezVousAction(Request $request, $idDoctor, $idPrestation)
    {
        $repositoryUser = $this->getDoctrine()->getRepository(User::class);
        /** @var User $doctor */
        $doctor = $repositoryUser->find($idDoctor);
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
            ->add('submit', SubmitType::class, ['label' => "Pré-autoriser le prélévement", 'attr' => ['class' => "genric-btn info circle arrow text-center"]])
            ->getForm();

        // TODO AJOUTER LES FRAIS DE GESTION

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $token = $form->getData()['token'];

                $stripe = new \Stripe\StripeClient(
                    $_ENV['STRIPE_SECRET_KEY']
                );

                $intent = $stripe->paymentIntents->create([
                    'amount' => $appointment->getPrestation()->getPrice() * 100,
                    'currency' => 'eur',
                    'payment_method_types' => ['card'],
                    'capture_method' => 'manual'
                ]);

                $appointment->setPaymentIntentId($intent['id']);
                $appointment->setState(Appointment::STATUS_PAID);

                // TODO : Envoyer email et SMS au patient

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
            'appointmentsAsBuyer' => $appointmentsAsBuyer,
            'appointmentsAsDoctor' => $appointmentsAsDoctor
        ]);
    }

    /** @Route("/mes-rendez-vous/{id}", name="rendez_vous_individuel") */
    public function rendezVousIndividuelAction(Request $request, $id)
    {
        // TODO : Bien différencier les textes selon qu'on soit médecin, patient ou juste acheteur. Il y a des erreurs pour l'instant
        // TODO : Vérifier qu'on est bien dans le rendez-vous
        /** @var Appointment $appointment */
        $appointment = $this->getDoctrine()->getRepository(Appointment::class)->find($id);
        $em = $this->getDoctrine()->getManager();

        return $this->render('appointment/rendez_vous_individuel.html.twig', [
            'appointment' => $appointment
        ]);
    }


    /** @Route("/doctor/mes-rendez-vous/{id}", name="doctor_rendez_vous_individuel") */
    public function doctorRendezVousIndividuelAction(Request $request, $id)
    {
        // TODO : Vérifier qu'on est bien dans le rendez-vous
        /** @var Appointment $appointment */
        $appointment = $this->getDoctrine()->getRepository(Appointment::class)->find($id);
        $em = $this->getDoctrine()->getManager();

        if ($appointment->getState() == Appointment::STATUS_PAID || $appointment->getState() == Appointment::STATUS_REFUSED_BY_PATIENT) {
            // Formulaire pour proposer une date : scheduleByDoctor
            $form = $this->createForm(AppointmentType::class, $appointment)
                ->remove('schedulePatient')
                ->remove('scheduleByPatient')
                ->add('Proposer', SubmitType::class);

            $em = $this->getDoctrine()->getManager();

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $appointment->setState(Appointment::STATUS_WAITING_FOR_DOCTOR);
                // TODO : Envoyer un e-mail / sms au médecin
                $em->persist($appointment);
                $em->flush();
            }

            return $this->render('appointment/doctor_rendez_vous_individuel.html.twig', [
                'appointment' => $appointment,
                'form' => $form->createView()
            ]);
        } elseif ($appointment->getState() == Appointment::STATUS_WAITING_FOR_DOCTOR) {
            // Formulaire pour accepter ou refuser la date. S'il refuse, il doit proposer une nouvelle date
            $form = $this->createFormBuilder(['Accepter' => false, 'Refuser' => false])
                ->add('Accepter', SubmitType::class)
                ->add('scheduleByDoctor', DateTimeType::class, ['label' => "Si vous refusez la proposition, proposez un nouveau créneau.", "required" =>     false])
                ->add('Refuser', SubmitType::class)
                ->getForm();

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                if ($form->get('Accepter')->isClicked()){
                    $appointment->setState(Appointment::STATUS_ACCEPTED_BY_PATIENT);
                    $appointment->setFinalSchedule($appointment->getScheduleByPatientDate());
                    $this->captureFunds($appointment->getPaymentIntentId(), $appointment->getPrestation()->getPrice());
                } else {
                    $appointment->setState(Appointment::STATUS_WAITING_FOR_PATIENT);
                    $appointment->setScheduleByDoctor($form->get('scheduleByDoctor')->getData());
                    // TODO : Check la date proposée par le médecin.
                }
                $em->persist($appointment);
                $em->flush();
                // TODO : Envoyer un e-mail / sms au patient

            }

            return $this->render('appointment/doctor_rendez_vous_individuel.html.twig', [
                'appointment' => $appointment,
                'form' => $form->createView()
            ]);
        }

        return $this->render('appointment/doctor_rendez_vous_individuel.html.twig', [
            'appointment' => $appointment
        ]);
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
