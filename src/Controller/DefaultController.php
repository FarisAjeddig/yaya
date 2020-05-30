<?php

namespace App\Controller;

use App\Entity\Country;
use App\Entity\Prestation;
use App\Entity\TypeDoctor;
use App\Entity\User;
use App\Form\DoctorType;
use App\Form\PrestationFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
                // L'adresse mail est déjà utilisée TODO
                die;
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

            return $this->render('bundles/FOSUserBundle/Profile/show.html.twig', [
                'user' => $doc
            ]);
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

        $countrys = $this->getDoctrine()->getRepository(Country::class)->findAll();

        $form = $this->createFormBuilder($user)
            ->add("types", ChoiceType::class, [
                'choices' => $types,
                'placeholder' => "Type de médecin",
                'multiple' => true,
                'label' => 'Type de médecin',
                'attr' => ['class' => 'js-example-basic-multiple'],
                'mapped' => false
            ])
            ->add("diploma", TextareaType::class, [
                'label' => "Vos qualifications (diplômes, etc.)",
                "attr" => [ "rows" => 4]
            ])
            ->add("bankAccountNumber", TextType::class, [
                'label' => "Votre IBAN Français (Ne sera utilisé que pour vous reverser les paiements de vos consultations)"
            ])
            ->add("adress", TextType::class, [
                'label' => "Adresse des consultation"
            ])
            ->add("city", ChoiceType::class, [
                'choices' => $countrys,
                'attr' => ['class' => 'js-example-basic-multiple']
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
                dd($form->getData());
                $repoTypesDoctors = $this->getDoctrine()->getRepository(TypeDoctor::class);
                foreach($form->get('types')->getData() as $typeChoosed){
                    $user->addTypeDoctor($repoTypesDoctors->find($typeChoosed));
                }
                $url = 'https://maps.googleapis.com/maps/api/geocode/json?';
                $options = array("address"=>$user->getAdress(),"key"=>"AIzaSyAuZviasKN0VON99Nz4I8b_tu6YZDcmrsw");
                $url .= http_build_query($options,'','&');

                if (json_decode(file_get_contents(htmlspecialchars_decode($url)))->results == []) {
                    dd('L\'adresse est mauvaise'); // TODO : Handle exception
                } else {
                    $coord = json_decode((file_get_contents(htmlspecialchars_decode($url))))->results[0]->geometry->location;
                }
                $user->setLongAdress($coord->lng);
                $user->setLatAdress($coord->lat);
            }
            if ($formPrestation->isSubmitted()){
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
            'user' => $user,
            'countrys' => $countrys
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
        $prestation = $this->getDoctrine()->getRepository(Prestation::class)->find($id);
        $user = $this->getUser();

        if ($prestation->getDoctor() === $user){
            $em = $this->getDoctrine()->getManager();
            $em->remove($prestation);
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('fos_user_profile_show');
        } else {
            dd("Vous n'avez pas le droit d'être là");
        }
    }
}
