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


        $regions = [
            'Todas as Regiões' => '', 
            'Lisboa' => 'Lisboa',
            'Faro' => 'Faro',
            'Porto' => 'Porto',
            'Estrangeiro' => 'Estrangeiro',
            'Évora' => 'Évora',
            'Coimbra' => 'Coimbra',
            'Setúbal' => 'Setúbal',
            'Beja' => 'Beja',
            'Região Autónoma da Madeira' => 'Região Autónoma da Madeira',
            'Leiria' => 'Leiria',
            'Braga' => 'Braga',
            'Aveiro' => 'Aveiro',
            'Santarém' => 'Santarém',
            'Portalegre' => 'Portalegre',
            'Região Autónoma dos Açores' => 'Região Autónoma dos Açores',
            'Viana do Castelo' => 'Viana do Castelo',
            'Guarda' => 'Guarda',
            'Castelo Branco' => 'Castelo Branco'];

        $builder
                ->add('data_inicio', \Symfony\Component\Form\Extension\Core\Type\DateType::class, [
                    'required' => false, ''
                    . 'empty_data' => '',
                ])
                ->add('data_fim', \Symfony\Component\Form\Extension\Core\Type\DateType::class, [
                    'required' => false,
                    'empty_data' => '',
                ])->add('location', ChoiceType::class, array(
            'choices' => $regions,
            'required' => true,
              
            'multiple' => false,
            'expanded' => false));
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
