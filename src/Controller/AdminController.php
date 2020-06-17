<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\City;
use App\Entity\Country;
use App\Entity\TypeDoctor;
use App\Entity\User;
use App\Form\CityType;
use App\Form\CountryType;
use App\Form\DoctorEditByAdminType;
use App\Form\DoctorType;
use App\Form\TypeDoctorType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/** @Route("/admin", name="admin_") */
class AdminController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function index()
    {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    /**
     * @Route("/users", name="users")
     */
    public function users(){
        $users = $this->getDoctrine()->getRepository(User::class)->findBy(['is_doctor' => false]);
        return $this->render('admin/users.html.twig',[
            'users' => $users
        ]);
    }

    /**
     * @Route("/doctors", name="doctors")
     */
    public function doctors(){
        $docs = $this->getDoctrine()->getRepository(User::class)->findBy(['is_doctor' => true]);
        return $this->render('admin/doctors.html.twig',[
            'docs' => $docs
        ]);
    }

    /**
     * @Route("/rendez-vous", name="rendez_vous")
     */
    public function rendezVous(){
        $appointments = $this->getDoctrine()->getRepository(Appointment::class)->findAll();
        return $this->render('admin/rendez_vous.html.twig', [
            'appointments' => $appointments
        ]);
    }

    /**
     * @Route("/reserver-rendez-vous", name="reserver_rendez_vous")
     */
    public function reserverRendezVous(){
        return $this->render('admin/reserver_rendez_vous.html.twig');
    }

    /**
     * @Route("/edit/doctor/{id}", name="edit_doctor")
     */
    public function editDoctor($id, Request $request, \Swift_Mailer $mailer){
        /** @var User $user */
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);


//        $form->handleRequest($request);
//
//        if ($request->isMethod('POST')){
//            $em = $this->getDoctrine()->getManager();
//            $em->persist($user);
//            $em->flush();
//
//            $message = (new \Swift_Message('Vos informations ont été modifiées'))
//                ->setFrom('digibinks@gmail.com')
//                ->setTo($user->getEmailCanonical())
//                ->setBody(
//                    $this->renderView(
//                    // templates/emails/registration.html.twig
//                        'admin/emails/editDoctor.html.twig',
//                        ['user' => $user]
//                    ),
//                    'text/html'
//                );
//
//            $mailer->send($message);

            return $this->redirectToRoute('admin_doctors');
//        }

//        return $this->render('admin/editDoctor.html.twig', [
//            'form' => $form->createView()
//        ]);
    }

    /**
     * @Route("/delete/user/{id}", name="delete_user")
     */
    public function deleteUser($id){
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);

        return $this->render('admin/deleteUser.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * @Route("/confirm/delete/user/{id}", name="confirm_delete_user")
     */
    public function confirmDeleteUser($id){
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);

        $em = $this->getDoctrine()->getManager();
        $em->remove($user);
        $em->flush();

        return $this->redirectToRoute('admin_users');
    }

    // Gestion des types de docteurs
    /** @Route("/type/doctor", name="type_doctor") */
    public function typeDoctor(){
        $types = $this->getDoctrine()->getRepository(TypeDoctor::class)->findAll();

        return $this->render('admin/doctorType/index.html.twig', [
            'types' => $types
        ]);
    }

    /** @Route("/type/doctor/create", name="type_doctor_create") */
    public function typeDoctorCreate(Request $request){
        $newType = new TypeDoctor();

        $form = $this->createForm(TypeDoctorType::class, $newType);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $request->isMethod('POST')){
            $em = $this->getDoctrine()->getManager();
            $em->persist($newType);
            $em->flush();

            return $this->redirectToRoute('admin_type_doctor');
        }

        return $this->render('admin/doctorType/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /** @Route("/type/doctor/delete/{id}", name="type_doctor_delete") */
    public function typeDoctorDelete($id){
        $type = $this->getDoctrine()->getRepository(TypeDoctor::class)->find($id);
        $em = $this->getDoctrine()->getManager();

        $em->remove($type);
        $em->flush();

        return $this->redirectToRoute('admin_type_doctor');
    }

    // Fin gestion des types de docteurs

    // Gestion des pays et des villes //
    /**
     * @Route("/countrys", name="countrys")
     */
    public function countrys(){
        $countrys = $this->getDoctrine()->getRepository(Country::class)->findAll();

        return $this->render('admin/country/index.html.twig', [
                'countrys' => $countrys
        ]);
    }

    /**
     * @Route("/countrys/create", name="country_create")
     */
    public function countrysCreate(Request $request){
        $newCountry = new Country();

        $form = $this->createForm(CountryType::class, $newCountry);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $request->isMethod('POST')){

//            dd($newCountry);

            $em = $this->getDoctrine()->getManager();
            $em->persist($newCountry);
            $em->flush();

            $this->addFlash('success', 'Le pays a bien été créé, cliquez ici pour y ajouter des villes');

            return $this->redirectToRoute('admin_countrys');
        }

        return $this->render('admin/country/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/countrys/delete/{id}", name="country_delete")
     */
    public function countryDelete($id){
        $country = $this->getDoctrine()->getRepository(Country::class)->find($id);
        $em = $this->getDoctrine()->getManager();

        $em->remove($country);
        $em->flush();

        return $this->redirectToRoute('admin_countrys');
    }

    /**
     * @Route("/countrys/{id}", name="country")
     */
    public function country($id){
        $country = $this->getDoctrine()->getRepository(Country::class)->find($id);

        return $this->render('admin/country/country.html.twig', [
            'country' => $country
        ]);
    }

    /**
     * @Route("/countrys/{id}/addCity", name="country_add_city")
     */
    public function countryAddCity($id, Request $request){
        $country = $this->getDoctrine()->getRepository(Country::class)->find($id);

        $city = new City();

        $form = $this->createForm(CityType::class, $city);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $request->isMethod('POST')){
            $city->setCountry($country);

            $em = $this->getDoctrine()->getManager();
            $em->persist($city);
            $em->flush();

            $this->addFlash('success', 'La ville a bien été ajoutée.');

            return $this->redirectToRoute('admin_country', ['id' => $id]);
        }

        return $this->render('admin/country/addCityToCountry.html.twig', [
            'country' => $country,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/countrys/deleteCity/{id}", name="country_delete_city")
     */
    public function countryDeleteCity($id){
        $city = $this->getDoctrine()->getRepository(City::class)->find($id);
        $em = $this->getDoctrine()->getManager();

        $idCountry = $city->getCountry()->getId();

        $em->remove($city);
        $em->flush();

        return $this->redirectToRoute('admin_country', ['id' => $idCountry]);
    }

    // Fin gestion des pays et des villes


}
