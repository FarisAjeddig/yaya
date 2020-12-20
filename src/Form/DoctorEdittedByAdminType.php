<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DoctorEdittedByAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, ['label' => "Nom et prénom"])
            ->add('email', TextType::class, ['label' => "Adresse e-mail"])
            ->add("diploma", TextareaType::class, [
                'label' => "Vos qualifications (diplômes, etc.)",
                "attr" => [ "rows" => 4]
            ])
            ->add("bankAccountNumber", TextType::class, [
                'label' => "Votre IBAN (Ne sera utilisé que pour vous reverser les paiements de vos consultations)"
            ])
            ->add('phone_number', TelType::class, [
                'label' => "Numéro de téléphone"
            ])
            ->add("adress", TextType::class, [
                'label' => "Adresse des consultation"
            ])
            ->add("desc", TextareaType::class, [
                "label" => "Informations complémentaires",
                "attr" => [ "rows" => 4]
            ])
            ->add("matriculeDoctor", TextType::class, [
                'label' => "Numéro de matricule officiel"
            ])
            ->add('picture', FileType::class, ['mapped' => false, 'required' => false, 'label' => "Photo", 'attr' => ['accept' => 'image/*']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
