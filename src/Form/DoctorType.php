<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DoctorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, [ 'label' => "Nom et prénom", 'label_attr' => [ 'style' => 'font-size: 18px'] ])
            ->add('email', TextType::class, [ 'label' => "Adresse mail", 'label_attr' => [ 'style' => 'font-size: 18px'] ])
            ->add('phone_number', TelType::class, [ 'label' => "Numéro de téléphone", 'label_attr' => [ 'style' => 'font-size: 18px'] ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'options' => array(
                    'translation_domain' => 'FOSUserBundle',
                    'attr' => array(
                        'autocomplete' => 'new-password',
                    ),
                ),
                'first_options' => array('label' => 'form.password', 'label_attr' => [ 'style' => 'font-size: 18px']),
                'second_options' => array('label' => 'form.password_confirmation', 'label_attr' => [ 'style' => 'font-size: 18px']),
                'invalid_message' => 'fos_user.password.mismatch',
            ])
            ->add("Envoyer", SubmitType::class, ['attr' => ['class' => 'btn btn-success btn-block']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
