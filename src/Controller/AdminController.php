<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\DoctorEditByAdminType;
use App\Form\DoctorType;
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
        return $this->render('admin/rendez_vous.html.twig');
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

        $form = $this->createForm(DoctorEditByAdminType::class, $user);

        $form->handleRequest($request);

        if ($request->isMethod('POST')){
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $message = (new \Swift_Message('Vos informations ont été modifiées'))
                ->setFrom('digibinks@gmail.com')
                ->setTo($user->getEmailCanonical())
                ->setBody(
                    $this->renderView(
                    // templates/emails/registration.html.twig
                        'admin/emails/editDoctor.html.twig',
                        ['user' => $user]
                    ),
                    'text/html'
                );

            $mailer->send($message);

            return $this->redirectToRoute('admin_doctors');
        }

        return $this->render('admin/editDoctor.html.twig', [
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

        return $this->redirectToRoute('admin_users');
    }
}
