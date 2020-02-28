<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DoctorEditByAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type_doctor', ChoiceType::class, [ 'choices' => [
                'Médecin généraliste' => 'Médecin généraliste',
                'Kinésitérapeute' => 'Kinésitérapeute'
            ],
                'label_attr' => [ 'style' => 'font-size: 18px' ],
                'label'=> 'Type de médecin'])
            ->add('diploma', TextareaType::class, [ 'label_attr' => [ 'style' => 'font-size: 18px' ]  ,'label'=> 'Qualifications (diplômes, etc.)'])
            ->add('adress', TextType::class, [ 'label_attr' => [ 'style' => 'font-size: 18px' ]  ,'label'=> 'Adresse'])
            ->add('price', NumberType::class, [ 'label_attr' => [ 'style' => 'font-size: 18px' ]  ,'label'=> 'Prix de la consultation'])
            ->add('desc', TextareaType::class, [ 'label_attr' => [ 'style' => 'font-size: 18px' ]  ,'label'=> 'Informations complémentaires'])
            ->add("Modifier", SubmitType::class, ['attr' => ['class' => 'btn btn-info btn-block']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
