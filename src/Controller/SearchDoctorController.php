<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class SearchDoctorController extends AbstractController
{
    /**
     * @Route("/search/{idTypeDoctor}/{idCity}", name="search_doctor")
     */
    public function index()
    {
        return $this->render('search_doctor/index.html.twig', [
            'controller_name' => 'SearchDoctorController',
        ]);
    }
}
