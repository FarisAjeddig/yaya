<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\City;
use App\Entity\Country;
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
     * @Route("/search/{idTypeDoctor}/{idCity}/{price}", name="search_doctor")
     */
    public function searchDoctorAction(Request $request, $idTypeDoctor=0, $idCity=0, $price=100)
    {
        $typesDoctor = $this->getDoctrine()->getRepository(TypeDoctor::class)->findAll();
        $typeDoctor = $this->getDoctrine()->getRepository(TypeDoctor::class)->find($idTypeDoctor);
        $countrys = $this->getDoctrine()->getRepository(Country::class)->findAll();
        $city = $this->getDoctrine()->getRepository(City::class)->find($idCity);
        $doctors = $this->getDoctrine()->getRepository(User::class)->findBy(['is_doctor' => true]);

        $filterDoctors = [];
        /** @var User $doc */
        foreach ($doctors as $doc){
            $sameType = false;
            foreach ($doc->getTypeDoctor() as $type){
                if ($type->getId() == $idTypeDoctor && $doc->getEnabledByAdmin()){
                    $sameType = true;
                }
            }
            if ($sameType && ($city == $doc->getCity()) && $price > $doc->getLowerPrice() && count($doc->getPrestations())>0){
                $filterDoctors[] = $doc;
            }
        }

        return $this->render('search_doctor/index.html.twig', [
            'doctors' => $filterDoctors,
            'typeDoctor' => $typeDoctor,
            'typesDoctor' => $typesDoctor,
            'countrys' => $countrys,
            'city' => $city
        ]);
    }



    /**
     * @Route("/doctor/{id}", name="doctor_profile")
     */
    public function doctorProfileAction($id){
        /** @var User $doctor */
        $doctor = $this->getDoctrine()->getRepository(User::class)->find($id);
//        dd($this->getUser());

        if ($doctor->getIsDoctor() && ($doctor->getEnabledByAdmin() || ($this->getUser() && in_array('ROLE_ADMIN',$this->getUser()->getRoles())))){
            return $this->render('search_doctor/profile_doctor.html.twig', [
                'doctor' => $doctor
            ]);
        } else {
            $this->get('session')->getFlashBag()->add('danger', 'Ce docteur n\'est pas visible pour le moment');
            return $this->redirectToRoute('homepage');
        }
    }
}
