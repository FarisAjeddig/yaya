<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Country;
use App\Entity\DonationRequest;
use App\Entity\Prestation;
use App\Entity\TypeDoctor;
use App\Entity\User;
use App\Form\DonationRequestType;
use Mailjet\Resources;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Twilio\Rest\Client;

class DonationRequestController extends AbstractController
{
    private $mj;

    public function __construct(){
        $this->mj = new \Mailjet\Client($_ENV['MJ_APIKEY_PUBLIC'],$_ENV['MJ_APIKEY_PRIVATE'],true,['version' => 'v3.1']);
    }

    /**
     * @Route("/demander-un-don", name="demander_un_don")
     */
    public function demanderUnDonAction(Request $request)
    {
        $newDonationRequest = new DonationRequest();

        $form = $this->createFormBuilder($newDonationRequest)->add('name', TextType::class)
            ->add('address', TextType::class)
            ->add('birthday', DateType::class, [
                'widget' => 'single_text'
            ])
            ->add('phoneNumber', TextType::class)
            ->add('pictureFile', FileType::class, ['mapped' => false, 'attr' => ['accept' => 'image/*']])
            ->add("Envoyer", SubmitType::class)
            ->getForm()
        ;

        $form->handleRequest($request);

        if ($request->isMethod('POST')) {
            $file = $form['pictureFile']->getData();

            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            // this is needed to safely include the file name as part of the URL
            $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

            // Move the file to the directory where brochures are stored
            try {
                $file->move(
                    $this->getParameter('pictures_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
                // ... handle exception if something happens during file upload
            }

            $newDonationRequest->setPicture($newFilename);
            $newDonationRequest->setState(DonationRequest::STATE_CREATED);
            $newDonationRequest->setBirthday($form['birthday']->getData());

            $em = $this->getDoctrine()->getManager();
            $em->persist($newDonationRequest);
            $em->flush();

            return $this->redirectToRoute('demander_don_choisir_medecin', [
                'idDonationRequest' => $newDonationRequest->getId()
            ]);
        }

        return $this->render('donation_request/index.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/demander-un-don/{idDonationRequest}/choisir-medecin/{idTypeDoctor}/{idCity}", name="demander_don_choisir_medecin")
     */
    public function demanderDonChoisirMedecinAction($idDonationRequest, $idCity=0, $idTypeDoctor=0){
        $typesDoctor = $this->getDoctrine()->getRepository(TypeDoctor::class)->findAll();
        $countrys = $this->getDoctrine()->getRepository(Country::class)->findAll();

        $donationRequest = $this->getDoctrine()->getRepository(DonationRequest::class)->find($idDonationRequest);
        $typeDoctor = $this->getDoctrine()->getRepository(TypeDoctor::class)->find($idTypeDoctor);
        $city = $this->getDoctrine()->getRepository(City::class)->find($idCity);

        $doctors = $this->getDoctrine()->getRepository(User::class)->findBy(['is_doctor' => true]);

        $filterDoctors = [];
        /** @var User $doc */
        foreach ($doctors as $doc){
            $sameType = false;
            foreach ($doc->getTypeDoctor() as $type){
                if ($type->getId() == $idTypeDoctor){
                    $sameType = true;
                }
            }
            if ($sameType && ($city == $doc->getCity()) && count($doc->getPrestations())>0){
                $filterDoctors[] = $doc;
            }
        }

        return $this->render('donation_request/choisir-medecin.html.twig', [
            'doctors' => $filterDoctors,
            'donationRequest' => $donationRequest,
            'typeDoctor' => $typeDoctor,
            'city' => $city,
            'typesDoctor' => $typesDoctor,
            'countrys' => $countrys
        ]);
    }

    /**
     * @Route("/demander-un-don/{idDonationRequest}/choix-prestation/{idPrestation}/{idDoctor}", name="demander_don_choix_prestation")
     */
    public function demanderDonChoixPrestationAction($idDonationRequest, $idPrestation, $idDoctor){
        /** @var DonationRequest $donationRequest */
        $donationRequest = $this->getDoctrine()->getRepository(DonationRequest::class)->find($idDonationRequest);
        /** @var Prestation $prestation */
        $prestation = $this->getDoctrine()->getRepository(Prestation::class)->find($idPrestation);
        /** @var User $doctor */
        $doctor = $this->getDoctrine()->getRepository(User::class)->find($idDoctor);

        $donationRequest->setPrestation($prestation);
        $donationRequest->setDoctor($doctor);
        $donationRequest->setState(DonationRequest::STATE_COMPLETE);

        // Envoi d'un mail à l'administrateur
        $body = [
            'Messages' => [
                [ 'From' => [ 'Email' => "contact@sante-universelle.org", 'Name' => "Santé universelle" ],
                    'To' => [[ 'Email' => $_ENV['EMAIL_ADMIN'], 'Name' => $_ENV['NAME_ADMIN']]],
                    'TemplateID' => 2042788,
                    'TemplateLanguage' => true,
                    'Variables' => ['link' => $this->generateUrl('admin_demande_de_don', [], UrlGeneratorInterface::ABSOLUTE_URL)]
                ]
            ]
        ];
        $response = $this->mj->post(Resources::$Email, ['body' => $body]);

        $em = $this->getDoctrine()->getManager();
        $em->persist($donationRequest);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Le choix de la consultation a bien été enregistré.');

        return $this->redirectToRoute('demander_un_don_recapitulatif', [
            'idDonationRequest' => $idDonationRequest
        ]);
    }

    /** @Route("/demander-un-don/recapitulatif/{idDonationRequest}", name="demander_un_don_recapitulatif") */
    public function demanderUnDonRecapitulatifAction($idDonationRequest){
        /** @var DonationRequest $donationRequest */
        $donationRequest = $this->getDoctrine()->getRepository(DonationRequest::class)->find($idDonationRequest);

        if ($donationRequest->getState() == DonationRequest::STATE_CREATED){
            return $this->redirectToRoute('demander_don_choisir_medecin', [
                'idDonationRequest' => $idDonationRequest
            ]);
        } else {
            return $this->render('donation_request/recapitulatif.html.twig', [
                'donationRequest' => $donationRequest
            ]);
        }
    }

    /** @Route("/offrir/soin/{id}", name="offrir_soin") */
    public function offrirSoinAction(Request $request, $id=0)
    {
        if ($id == 0) {
            $donationRequests = $this->getDoctrine()->getRepository(DonationRequest::class)->findBy(['state' => DonationRequest::STATE_VALID]);

            return $this->render('donation_request/offrir-soin.html.twig', [
                'donationRequests' => $donationRequests
            ]);
        } else {
            /** @var DonationRequest $donationRequest */
            $donationRequest = $this->getDoctrine()->getRepository(DonationRequest::class)->find($id);
//            if ($donationRequest->getState() === DonationRequest::STATE_END){
//
//            }

            $form = $this->get('form.factory')
                ->createNamedBuilder('payment-form')
                ->add('token', HiddenType::class, [
                    'constraints' => [new NotBlank()],
                ])
                ->add('submit', SubmitType::class, ['label' => "Offrir le soin", 'attr' => ['class' => "genric-btn info circle arrow text-center"]])
                ->getForm();

            if ($request->isMethod('POST')) {
                $form->handleRequest($request);
                if ($form->isValid()) {
                    $token = $form->getData()['token'];

                    $stripe = new \Stripe\StripeClient(
                        $_ENV['STRIPE_SECRET_KEY']
                    );


                    $intent = $stripe->paymentIntents->create([
                        'amount' => $donationRequest->getPrestation()->getPrice() * 100 + 300,
                        'currency' => 'eur',
                        'payment_method_types' => ['card'],
                        'capture_method' => 'manual'
                    ]);

                    $stripe->paymentIntents->confirm(
                        $intent['id'],
                        ['payment_method' => 'pm_card_visa']
                    );

                    $stripe->paymentIntents->capture($intent['id'], []);

                    $donationRequest->setState(DonationRequest::STATE_END);
                    $donationRequest->setBuyer($this->getUser());
                    $body = [
                        'Messages' => [// Email pour le médecin
                            ['From' => [ 'Email' => "contact@sante-universelle.org", 'Name' => "Santé universelle" ],
                                'To' => [['Email' => $donationRequest->getDoctor()->getEmailCanonical(), 'Name' => $donationRequest->getDoctor()->getUsername()]],
                                'TemplateID' => 2042515,
                                'TemplateLanguage' => true,
                                'Variables' => [
                                    'phoneNumberPatient' => $donationRequest->getPhoneNumber(),
                                    'namePatient' => $donationRequest->getName(),
                                    'emailPatient' => $donationRequest->getAddress(),
                                    'buyerUsername' => $this->getUser()->getUsername()
                                ]
                            ]
                        ]
                    ];
                    $response = $this->mj->post(Resources::$Email, ['body' => $body]);

                    $this->sendSMS($donationRequest->getPhoneNumber(),
                        "Santé universelle : " . $this->getUser()->getUsername() . ' vous offre un rendez-vous chez le médecin ' . $donationRequest->getDoctor()->getUsername() .
                        ' situé à cette adresse : ' . $donationRequest->getDoctor()->getAdress() . '. Coordonnées du médecin : ' . $donationRequest->getDoctor()->getEmailCanonical() . " " . $donationRequest->getDoctor()->getPhoneNumber()
                    );
                    $this->sendSMS($donationRequest->getDoctor()->getPhoneNumber(),
                        "Santé universelle : Nouvelle réservation pour \"" . $donationRequest->getPrestation()->getName() .
                        "\". Coordonnées du patient : " . $donationRequest->getPhoneNumber());


                    $this->get('session')->getFlashBag()->add('success', 'Le soin a bien été offert.');

                    $em = $this->getDoctrine()->getManager();
                    $em->persist($donationRequest);
                    $em->flush();


                    return $this->redirectToRoute('offrir_soin');
                }
            }

            return $this->render('donation_request/offrir-soin-payment.html.twig', [
                'donationRequest' => $donationRequest,
                'stripe_public_key' => $_ENV['STRIPE_PUBLIC_KEY'],
                'form' => $form->createView()
            ]);
        }
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

