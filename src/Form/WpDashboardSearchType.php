<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WpDashboardSearchType extends AbstractType {

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {


        $builder
                ->add('data_inicio', \Symfony\Component\Form\Extension\Core\Type\DateType::class, [
                    'required' => false, ''
                    . 'empty_data' => '',
                ])
                ->add('data_fim', \Symfony\Component\Form\Extension\Core\Type\DateType::class, [
                    'required' => false,
                    'empty_data' => '',
                ]);
    }

    /**
     * {@inheritdoc}
     */
//    public function configureOptions(OptionsResolver $resolver)
//    {
//        $resolver->setDefaults([
//            'data_class' => null,
//        ]);
//    }
//    
//    
//     public function configureOptions(OptionsResolver $resolver)
//    {
//        $resolver->setDefaults(array(
//            'data_class' => 'OC\PlatformBundle\Entity\Image',
//        ));
//    }


    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
            'validation_groups' => false,
            'attr' => array('novalidate' => 'novalidate')
        ));
    }
}
