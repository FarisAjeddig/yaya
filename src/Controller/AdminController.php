<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\City;
use App\Entity\Country;
use App\Entity\DonationRequest;
use App\Entity\Prestation;
use App\Entity\TypeDoctor;
use App\Entity\User;
use App\Form\CityType;
use App\Form\CountryType;
use App\Form\DoctorType;
use App\Form\PrestationFormType;
use App\Form\TypeDoctorType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
        return $this->render('admin/doctors/doctors.html.twig',[
            'docs' => $docs
        ]);
    }

    /** @Route("/rendez-vous", name="rendez_vous") */
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
     * @Route("/enable-disable/doctor/{id}", name="enable_disable_doctor")
     */
    public function enableDisableDoctor($id){
        /** @var User $user */
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);
        $user->setEnabledByAdmin(!($user->getEnabledByAdmin()));

        $this->addFlash('success', 'Vous avez bien activé ou désactivé le dr. ' . $user->getUsername());

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        return $this->redirectToRoute('admin_doctors');
    }

    /** @Route("/doctor/{id}/prestations", name="doctor_prestations") */
    public function doctorPrestationsAction($id){
        $doctor = $this->getDoctrine()->getRepository(User::class)->find($id);

        return $this->render('admin/doctors/prestations.html.twig', [
            'doctor' => $doctor
        ]);
    }

    /** @Route("/doctor/prestation/edit/{idPrestation}", name="doctor_prestation_edit") */
    public function doctorPrestationEditAction(Request $request, $idPrestation){
        $prestation = $this->getDoctrine()->getRepository(Prestation::class)->find($idPrestation);

        $form = $this->createForm(PrestationFormType::class, $prestation)
            ->remove('Ajouter')
            ->add('Modifier', SubmitType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $em = $this->getDoctrine()->getManager();
            $em->persist($prestation);
            $em->flush();

            $this->addFlash('success', 'La prestation a bien été modifiée.');

            return $this->redirectToRoute('admin_doctor_prestations', [
                'id' => $prestation->getDoctor()->getId()
            ]);
        }

        return $this->render('admin/doctors/editPrestation.html.twig', [
            'form' => $form->createView()
        ]);
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

        if ($user->getIsDoctor() == true){
            return $this->redirectToRoute('admin_doctors');
        }

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


    // Gestion des demandes de don
    /** @Route("/demandes-de-don", name="demande_de_don") */
    public function demandeDeDonAction(){
        $donationRequests = $this->getDoctrine()->getRepository(DonationRequest::class)->findAll();

        return $this->render('admin/donationRequest/index.html.twig', [
            'donationRequests' => $donationRequests
        ]);
    }

    /** @Route("/demandes-de-don/delete/{id}", name="demande_de_don_delete") */
    public function DemandeDeDonDeleteAction($id){
        $donationRequest = $this->getDoctrine()->getRepository(DonationRequest::class)->find($id);

        $em = $this->getDoctrine()->getManager();
        $em->remove($donationRequest);
        $em->flush();

        $this->addFlash('success', 'La demande a bien été supprimée.');

        return $this->redirectToRoute('admin_demande_de_don');
    }

    /** @Route("/demandes-de-don/valid/{id}", name="demande_de_don_valid") */
    public function demandeDeDonValidAction($id){
        /** @var DonationRequest $donationRequest */
        $donationRequest = $this->getDoctrine()->getRepository(DonationRequest::class)->find($id);

        $donationRequest->setState(DonationRequest::STATE_VALID);

        $em = $this->getDoctrine()->getManager();
        $em->persist($donationRequest);
        $em->flush();

        $this->addFlash('success', 'La demande a bien été validée.');

        return $this->redirectToRoute('admin_demande_de_don');
    }
}
