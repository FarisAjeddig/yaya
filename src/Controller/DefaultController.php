<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Country;
use App\Entity\Prestation;
use App\Entity\TypeDoctor;
use App\Entity\User;
use App\Form\DoctorType;
use App\Form\PrestationFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    /** @Route("/", name="homepage") */
    public function indexAction()
    {
        $typesDoctor = $this->getDoctrine()->getRepository(TypeDoctor::class)->findAll();
        $countrys = $this->getDoctrine()->getRepository(Country::class)->findAll();
        return $this->render('default/index.html.twig', [
            'typesDoctor' => $typesDoctor,
            'countrys' => $countrys
        ]);
    }

    /** @Route("/formRegisterConfirmed", name="FormRegisterConfirmed") */
    public function FormRegisterConfirmedAction(Request $request){
        $user = $this->getUser();

        $form = $this->createFormBuilder($user)
            ->add('phone_number', TelType::class, [
                'label' => "Numéro de téléphone"
            ])
            ->add('Envoyer', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);
        if ($request->isMethod('POST')){
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('homepage');
        }


        return $this -> render('default/register_confirmed_form.html.twig', [
            'form' => $form -> createView()
        ]);
    }

    /** @Route("/profile/edit/phone", name="profile_edit_phone") */
    public function profileEditAction(Request $request){
        $user = $this->getUser();

        $form = $this->createFormBuilder($user)
            ->add('phone_number', TelType::class, [
                'label' => "Nouveau numéro de téléphone"
            ])
            ->add('Envoyer', SubmitType::class, [
                'attr' => ['class' => 'form-control btn-success btn-block']
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($request->isMethod('POST')){
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('fos_user_profile_show');
        }
        return $this->render('bundles/FOSUserBundle/Profile/editPhone.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /** @Route("/register/doctor", name="register_doctor") */
    public function registerDoctorAction(Request $request, \Swift_Mailer $mailer){
        $doc = new User();
        $form = $this->createForm(DoctorType::class, $doc);

        $form->handleRequest($request);

        if ($request->isMethod('POST')){
            $alreadyExistUser = $this->getDoctrine()->getRepository(User::class)->findBy(['emailCanonical' => $doc->getEmailCanonical()]);
            if ($alreadyExistUser != []){
                $this->get('session')->getFlashBag()->add('danger', 'L\'adresse mail est déjà utilisée.');
                return $this->render('default/register/doctor_register.html.twig', [
                    'form' => $form->createView()
                ]);
            }
            $doc->setEnabled(true);
            $doc->setIsDoctor(true);

            $doc->setSalt('Xdk539C0fhDfNo77ndABsFkbosSrZCN63SwtVoN0OtU');

            // Cryptage du password
            $pass = $doc->getPassword();
            $salt = 'Xdk539C0fhDfNo77ndABsFkbosSrZCN63SwtVoN0OtU';
            $iterations = 5000; // Par défaut
            $salted = $pass.'{'.$salt.'}';
            $digest = hash('sha512', $salted, true);
            for ($i = 1; $i < $iterations; $i++) {
                $digest = hash('sha512', $digest.$salted, true);
            }
            $cryptedPass = base64_encode($digest);
            $doc->setPassword($cryptedPass);

            // Envoi d'un mail à l'administrateur
            $message = (new \Swift_Message('Un nouveau docteur s\'est inscrit'))
                ->setFrom('digibinks@gmail.com')
                ->setTo('fajeddig@hotmail.fr')
                ->setBody(
                    $this->renderView(
                        'admin/emails/newUserEmailToAdmin.html.twig',
                        ['doc' => $doc]
                    ),
                    'text/html'
                );

            $mailer->send($message);

            // Persister l'utilisateur dans la base de données
            $em = $this->getDoctrine()->getManager();
            $em->persist($doc);
            $em->flush();

            //TODO : Renvoyer vers la page de connexion instead ou gérer automatiquement la connexion.
            return $this->redirectToRoute('fos_user_security_login');
        }

        return $this->render('default/register/doctor_register.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /** @Route("/profile/info/doctor", name="info_doctor") */
    public function infoDoctorAction(Request $request){
        /** @var User $user */
        $user = $this->getUser();

        $typesDoctor = $this->getDoctrine()->getRepository(TypeDoctor::class)->findAll();

        $types = [];
        foreach ($typesDoctor as $type){
            $types[$type->getName()] = $type->getId();
        }

        $dataTypes = [];
        foreach ($user->getTypeDoctor() as $type){
            $dataTypes[$type->getName()] = $type->getId();
        }

        $citysList = $this->getDoctrine()->getRepository(City::class)->findAll();

        $citys = [];
        foreach ($citysList as $city){
            $citys[$city->getName()] = $city->getId();
        }

        $form = $this->createFormBuilder($user)
            ->add("types", ChoiceType::class, [
                'choices' => $types,
                'placeholder' => "Type de médecin",
                'multiple' => true,
                'label' => 'Type de médecin',
                'attr' => ['class' => 'js-example-basic-multiple'],
                'mapped' => false,
                'data' => $dataTypes
            ])
            ->add("diploma", TextareaType::class, [
                'label' => "Vos qualifications (diplômes, etc.)",
                "attr" => [ "rows" => 4]
            ])
            ->add("bankAccountNumber", TextType::class, [
                'label' => "Votre IBAN Français (Ne sera utilisé que pour vous reverser les paiements de vos consultations)"
            ])
            ->add("matriculeDoctor", TextType::class, [
                'label' => "Numéro de matricule officiel"
            ])
            ->add("adress", TextType::class, [
                'label' => "Adresse des consultation"
            ])
            ->add("city", ChoiceType::class, [
                'choices' => $citys,
                'attr' => ['class' => 'js-example-basic-multiple'],
                'mapped' => false,
                'data' => $user->getCity() !== null ? $user->getCity()->getName() : null
            ])
            ->add("desc", TextareaType::class, [
                "label" => "Informations complémentaires",
                "attr" => [ "rows" => 4]
            ])
            ->add('Modifier', SubmitType::class, ['attr' => ["class" => "btn btn-block btn-info"]])
            ->getForm();

        /** @var Prestation $prestation */
        $prestation = new Prestation();
        $formPrestation = $this->createForm(PrestationFormType::class, $prestation);
        $form->handleRequest($request);
        $formPrestation->handleRequest($request);
        if ($request->isMethod('POST')){
            $em = $this->getDoctrine()->getManager();

            if ($form->isSubmitted()){
                /** @var City $city */
                $city = $this->getDoctrine()->getRepository(City::class)->find($form->get('city')->getData());
                $user->setCity($city);
                $repoTypesDoctors = $this->getDoctrine()->getRepository(TypeDoctor::class);
                $user->clearTypeDoctor();
                foreach($form->get('types')->getData() as $typeChoosed){
                    $user->addTypeDoctor($repoTypesDoctors->find($typeChoosed));
                }
                $url = 'https://maps.googleapis.com/maps/api/geocode/json?';
                $options = array("address"=>$user->getAdress(),"key"=>"AIzaSyAuZviasKN0VON99Nz4I8b_tu6YZDcmrsw");
                $url .= http_build_query($options,'','&');

                if (json_decode(file_get_contents(htmlspecialchars_decode($url)))->results == []) {
                    $this->get('session')->getFlashBag()->add('danger', 'L\'adresse n\'est pas reconnue. Sélectionnez l\'adresse dans la liste en dessous.');

                    return $this->render('bundles/FOSUserBundle/Profile/infoDoctor.html.twig', [
                        'form' => $form->createView(),
                        'formPrestation' => $formPrestation->createView(),
                        'user' => $user
                    ]);
                } else {
                    $coord = json_decode((file_get_contents(htmlspecialchars_decode($url))))->results[0]->geometry->location;
                }
                $user->setLongAdress($coord->lng);
                $user->setLatAdress($coord->lat);
            }
            if ($formPrestation->isSubmitted()){
                if ($prestation->getPrice() < $user->getLowerPrice() || !$user->getLowerPrice()){
                    $user->setLowerPrice($prestation->getPrice());
                }
                $prestation->setDoctor($user);
                $user->addPrestation($prestation);
                $em->persist($prestation);
            }

            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('fos_user_profile_show');
        }

        return $this->render('bundles/FOSUserBundle/Profile/infoDoctor.html.twig', [
            'form' => $form->createView(),
            'formPrestation' => $formPrestation->createView(),
            'user' => $user
            ]);
    }

    /**
     * @Route("/edit/prestation/{id}", name="edit_prestation")
     */
    public function editPrestationAction(Request $request, $id){
        $user = $this->getUser();
        /** @var Prestation $prestation */
        $prestation = $this->getDoctrine()->getRepository(Prestation::class)->find($id);


        if ($prestation->getDoctor() !== $user){
            dd("Vous n'avez pas le droit d'accéder à cette page.");
        } else {
            $form = $this->createForm(PrestationFormType::class, $prestation)
                ->remove('Ajouter')
                ->add('Ajouter', SubmitType::class, ['attr' => ['class' => 'btn btn-block btn-primary'] ]);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $request->isMethod('POST')){
                $em = $this->getDoctrine()->getManager();
                $em->persist($prestation);
                $em->flush();

                return $this->redirectToRoute('fos_user_profile_show');
            }
        }

        return $this->render('bundles/FOSUserBundle/Profile/editPrestation.html.twig', [
            'form' => $form->createView(),
            'prestation' => $prestation
        ]);
    }

    /**
     * @Route("/delete/prestation/{id}", name="delete_prestation")
     */
    public function deletePrestationAction($id){
        $prestation = $this->getDoctrine()->getRepository(Prestation::class)->find($id);

        return $this->render('bundles/FOSUserBundle/Profile/deletePrestation.html.twig', [
            'prestation' => $prestation
        ]);
    }

    /**
     * @Route("delete/prestation/confirm/{id}", name="delete_prestation_confirm")
     */
    public function deletePrestationConfirmAction($id, Request $request){
        /** @var Prestation $prestation */
        $prestation = $this->getDoctrine()->getRepository(Prestation::class)->find($id);
        /** @var User $user */
        $user = $this->getUser();

        if ($prestation->getDoctor() === $user){
            $em = $this->getDoctrine()->getManager();
            if ($prestation->getPrice() == $user->getLowerPrice()){
                $prestations = $user->getPrestations();
                $lowerPrice = 10000;
                foreach ($prestations as $presta){
                    if ($presta != $prestation && $presta->getPrice() < $lowerPrice){
                        $lowerPrice = $presta->getPrice();
                    }
                }
                $user->setLowerPrice($lowerPrice);
            }

            $user->removePrestation($prestation);
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('fos_user_profile_show');
        } else {
            // TODO
            dd("Vous n'avez pas le droit d'être là");
        }
    }

    /**
     * @Route("profile/edit/picture", name="profile_edit_picture")
     */
    public function profileEditPictureAction(Request $request){
        /** @var User $user */
        $user = $this->getUser();

        $datad = ['file' => null];
        $form = $this->createFormBuilder($datad)
            ->add('file', FileType::class, ['label' => "Choisissez une image", 'attr' => ['class' => 'form-control text-center']])
            ->add('Modifier', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->getData()['file'];

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

            $user->setPicture($newFilename);

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('fos_user_profile_show');
        }

        return $this->render("bundles/FOSUserBundle/Profile/editPicture.html.twig", [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }
}
