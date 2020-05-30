<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Prestation;
use App\Entity\TypeDoctor;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SearchDoctorController extends AbstractController
{
    /**
     * @Route("/search/{idTypeDoctor}/{idCity}", name="search_doctor")
     */
    public function searchDoctorAction(Request $request, $idTypeDoctor, $idCity)
    {
        $doctors = $this->getDoctrine()->getRepository(User::class)->findBy([]);
        $typeDoctor = $this->getDoctrine()->getRepository(TypeDoctor::class)->find($idTypeDoctor);
        $city = $this->getDoctrine()->getRepository(City::class)->find($idCity);

        return $this->render('search_doctor/index.html.twig', [
            'doctors' => $doctors,
            'typeDoctor' => $typeDoctor,
            'city' => $city
        ]);
    }

    /**
     * @Route("/doctor/{id}", name="doctor_profile")
     */
    public function doctorProfileAction($id){
        $doctor = $this->getDoctrine()->getRepository(User::class)->find($id);

        return $this->render('search_doctor/profile_doctor.html.twig', [
            'doctor' => $doctor
        ]);
    }

    /**
     * @Route("/reserver/{idDoctor}/{idPrestation}", name="reserver_rendez_vous")
     */
    public function reserverRendezVousAction($idDoctor, $idPrestation){
        $doctor = $this->getDoctrine()->getRepository(User::class)->find($idDoctor);
        $prestation = $this->getDoctrine()->getRepository(Prestation::class)->find($idPrestation);




        return $this->render('search_doctor/reserver_rendez_vous.html.twig', [
            'doctor' => $doctor,
            'prestation' => $prestation
        ]);
    }
}
