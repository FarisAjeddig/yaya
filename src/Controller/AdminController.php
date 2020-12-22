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
use App\Form\DoctorEdittedByAdminType;
use App\Form\DoctorType;
use App\Form\PrestationFormType;
use App\Form\TypeDoctorType;
use App\Form\UserEdittedByAdminType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
     * @Route("/edit/user/{id}", name="user_edit")
     */
    public function userEdit($id, Request $request){
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);

        $form = $this->createForm(UserEdittedByAdminType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Le compte de l\'utilisateur a bien été modifiée.');
        }

        return $this->render('admin/users/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user
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

    /**
     * @Route("/doctor/{id}/create/prestation", name="create_prestation")
     */
    public function createPrestation(Request $request, $id){
        /** @var User $doc */
        $doc = $this->getDoctrine()->getRepository(User::class)->find($id);

        $prestation = new Prestation();
        $form = $this->createForm(PrestationFormType::class, $prestation);
        $form->handleRequest($request);
        if ($request->isMethod('POST') && $form->isSubmitted()) {
            $em = $this->getDoctrine()->getManager();
            if ($prestation->getPrice() < $doc->getLowerPrice() || !$doc->getLowerPrice()){
                $doc->setLowerPrice($prestation->getPrice());
            }
            $prestation->setDoctor($doc);
            $doc->addPrestation($prestation);
            $em->persist($prestation);
            $em->persist($doc);
            $em->flush();

            $this->addFlash('success', 'La prestation a bien été ajoutée.');

            return $this->redirectToRoute('admin_doctor_prestations', ['id' => $id]);
        }

        return $this->render('admin/doctors/editPrestation.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/edit/doctor/{id}", name="doctor_edit")
     */
    public function doctorEdit(Request $request, $id){
        /** @var User $doc */
        $doc = $this->getDoctrine()->getRepository(User::class)->find($id);

        $typesDoctor = $this->getDoctrine()->getRepository(TypeDoctor::class)->findAll();

        $types = [];
        foreach ($typesDoctor as $type){
            $types[$type->getName()] = $type->getId();
        }

        $dataTypes = [];
        foreach ($doc->getTypeDoctor() as $type){
            $dataTypes[$type->getName()] = $type->getId();
        }

        $citysList = $this->getDoctrine()->getRepository(City::class)->findAll();

        $citys = [];
        foreach ($citysList as $city){
            $citys[$city->getName()] = $city->getId();
        }

        $form = $this->createForm(DoctorEdittedByAdminType::class, $doc)
            ->add("types", ChoiceType::class, [
                'choices' => $types,
                'placeholder' => "Type de médecin",
                'multiple' => true,
                'label' => 'Type de médecin',
                'attr' => ['class' => 'js-example-basic-multiple'],
                'mapped' => false,
                'data' => $dataTypes
            ])
            ->add("city", ChoiceType::class, [
                'label' => "Ville",
                'choices' => $citys,
                'attr' => ['class' => 'js-example-basic-multiple'],
                'mapped' => false,
                'data' => $doc->getCity() !== null ? $doc->getCity()->getName() : null
            ])
            ->add('Modifier', SubmitType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted()
//            && $form->isValid()
        ){
            $doc->setUsernameCanonical(strtolower($doc->getUsername()));
            $doc->setEmailCanonical(strtolower($doc->getEmail()));
            /** @var City $city */
            $city = $this->getDoctrine()->getRepository(City::class)->find($form->get('city')->getData());
            $doc->setCity($city);
            $repoTypesDoctors = $this->getDoctrine()->getRepository(TypeDoctor::class);
            $doc->clearTypeDoctor();
            foreach($form->get('types')->getData() as $typeChoosed){
                $doc->addTypeDoctor($repoTypesDoctors->find($typeChoosed));
            }
            $url = 'https://maps.googleapis.com/maps/api/geocode/json?';
            $options = array("address"=>$doc->getAdress(),"key"=>"AIzaSyAuZviasKN0VON99Nz4I8b_tu6YZDcmrsw");
            $url .= http_build_query($options,'','&');
            $coord = json_decode((file_get_contents(htmlspecialchars_decode($url))))->results[0]->geometry->location;

            $doc->setLongAdress($coord->lng);
            $doc->setLatAdress($coord->lat);

            $em = $this->getDoctrine()->getManager();
            $em->persist($doc);
            $em->flush();

            $this->addFlash('success', 'Le compte du médecin a bien été modifiée.');
        }

        return $this->render('admin/doctors/edit.html.twig', [
            'form' => $form->createView(),
            'doctor' => $doc
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

    /** @Route("/type/doctor/edit/{id}", name="type_doctor_edit") */
    public function typeDoctorDelete(Request $request, $id){
        $type = $this->getDoctrine()->getRepository(TypeDoctor::class)->find($id);

        $form = $this->createForm(TypeDoctorType::class, $type)
            ->remove('Ajouter')
            ->add('Modifier', SubmitType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $request->isMethod('POST')){
            $em = $this->getDoctrine()->getManager();
            $em->persist($type);
            $em->flush();

            return $this->redirectToRoute('admin_type_doctor');
        }

        return $this->render('admin/doctorType/create.html.twig', [
            'form' => $form->createView()
        ]);
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

            $this->addFlash('success', 'Le pays a bien été créé, cliquez dessus pour y ajouter des villes');

            return $this->redirectToRoute('admin_countrys');
        }

        return $this->render('admin/country/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/countrys/edit/{id}", name="country_edit")
     */
    public function countryDelete(Request $request, $id){
        $country = $this->getDoctrine()->getRepository(Country::class)->find($id);
        $form = $this->createForm(CountryType::class, $country)
            ->remove('Ajouter')
            ->add('Modifier', SubmitType::class)
        ;

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $request->isMethod('POST')){

//            dd($newCountry);

            $em = $this->getDoctrine()->getManager();
            $em->persist($country);
            $em->flush();

            $this->addFlash('success', 'Le pays a bien été modifié.');

            return $this->redirectToRoute('admin_countrys');
        }

        return $this->render('admin/country/create.html.twig', [
            'form' => $form->createView()
        ]);


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
     * @Route("/countrys/editCity/{id}", name="country_edit_city")
     */
    public function countryEditCity(Request $request, $id){
        $city = $this->getDoctrine()->getRepository(City::class)->find($id);
        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(CityType::class, $city)
            ->remove('Ajouter')
            ->add('Modifier', SubmitType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $request->isMethod('POST')){

            $em = $this->getDoctrine()->getManager();
            $em->persist($city);
            $em->flush();

            $this->addFlash('success', 'La ville a bien été modifiée.');

            return $this->redirectToRoute('admin_country', ['id' => $id]);
        }

        return $this->render('admin/country/addCityToCountry.html.twig', [
            'country' => $city->getCountry(),
            'form' => $form->createView()
        ]);
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
