<?php 

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WpUserSearchType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        
                $builder
                        //->add('todos')
                ->add('data_inicio')
                ->add('data_fim')
                ->add('pais')->add('search', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Pesquisar']);
        
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
 
  
public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'validation_groups' => false,
            'attr' => array('novalidate' => 'novalidate')
        ));
    }


}
