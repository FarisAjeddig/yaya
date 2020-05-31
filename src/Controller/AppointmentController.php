<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Prestation;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotBlank;

class AppointmentController extends AbstractController
{

    /**
     * @Route("/reserver/{idDoctor}/{idPrestation}", name="reserver_rendez_vous")
     */
    public function reserverRendezVousAction(Request $request, $idDoctor, $idPrestation){
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
            ->add('schedulePatient', TextareaType::class, ['label' => "Quelles sont les disponibilités du patient ?"])
            ->add('Reserver', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);


        if ($request->isMethod('POST')){
            $em = $this->getDoctrine()->getManager();

            $appointment->setState(Appointment::STATUS_UNPAID);
            $appointment->setPrestation($prestation);
            $appointment->setDoctor($doctor);
            $appointment->setBuyer($currentUser);


            $patient = $repositoryUser->findBy(['emailCanonical' => 'strtolower($appointment->getEmailPatient())']);

            if ($patient !== []){
                /** @var User $patient */
                $patient = $patient[0];
                $appointment->setPatient($patient);
                $patient->addAppointmentAsPatient($appointment);
            }

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
    public function reserverPayerAction(Request $request, $idAppointment){
        $appointment = $this->getDoctrine()->getRepository(Appointment::class)->find($idAppointment);

        $prestation = $this->getDoctrine()->getRepository(Prestation::class)->find($appointment->getPrestation()->getId());

        $form = $this->get('form.factory')
            ->createNamedBuilder('payment-form')
            ->add('token', HiddenType::class, [
                'constraints' => [new NotBlank()],
            ])
            ->add('submit', SubmitType::class, ['label' => "Pré-autoriser le prélévement", 'attr' => ['class' => "genric-btn info circle arrow text-center"]])
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                // TODO: charge the card
            }
        }

        return $this->render('appointment/reserver_payer.html.twig', [
            'appointment' => $appointment,
            'prestation' => $prestation,
            'stripe_public_key' => $_ENV['STRIPE_PUBLIC_KEY'],
            'form' => $form->createView()
        ]);
    }
}
