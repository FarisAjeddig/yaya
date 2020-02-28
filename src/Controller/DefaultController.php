<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\DoctorType;
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
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        return $this->render('default/index.html.twig', [
            'controller_name' => 'DefaultController',
        ]);
    }

    /**
     * @Route("/formRegisterConfirmed", name="FormRegisterConfirmed")
     */
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

    /**
     * @Route("/profile/edit/phone", name="profile_edit_phone")
     */
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

    /**
     * @Route("/register/doctor", name="register_doctor")
     */
    public function registerDoctorAction(Request $request){
        $doc = new User();
        $form = $this->createForm(DoctorType::class, $doc);

        $form->handleRequest($request);

        if ($request->isMethod('POST')){
            $alreadyExistUser = $this->getDoctrine()->getRepository(User::class)->findBy(['emailCanonical' => $doc->getEmailCanonical()]);
            if ($alreadyExistUser != []){
                // L'adresse mail est déjà utilisée
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
        $user = $this->getUser();

        $form = $this->createFormBuilder($user)
            ->add("type_doctor", ChoiceType::class, [
                'choices' => [
                    'Médecin généraliste' => 'Médecin généraliste',
                    'Kinésitérapeute' => 'Kinésitérapeute'
                ],
                'label' => 'Type de médecin'
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
            ->add("price", NumberType::class, [
                "label" => "Prix d'une consultation"
            ])
            ->add("desc", TextareaType::class, [
                "label" => "Informations complémentaires",
                "attr" => [ "rows" => 4]
            ])
            ->add('Modifier', SubmitType::class, ['attr' => ["class" => "btn btn-block btn-info"]])
            ->getForm();


        $form->handleRequest($request);
        if ($request->isMethod('POST')){
//            dd($user);;
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

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('fos_user_profile_show');
        }

        return $this->render('bundles/FOSUserBundle/Profile/infoDoctor.html.twig', [
            'form' => $form->createView()
            ]);
    }
}
