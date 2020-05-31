<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\City;
use App\Entity\Prestation;
use App\Entity\TypeDoctor;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
}
