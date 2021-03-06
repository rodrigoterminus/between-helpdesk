<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Doctrine\ORM\EntityRepository;

class CategoryType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, array('label' => 'Nome'))
            // ->add('category', 'entity', array(
            //         'label' => 'Categoria',
            //         'class' => 'AppBundle:Category',
            //         'query_builder' => function(EntityRepository $er) {
            //             return $er->createQueryBuilder('c')
            //                 ->addOrderBy("c.name", 'ASC');
            //             },
            //         'choice_label' => 'name',
            //         'placeholder' => 'Selecione',
            //         'required' => false,
            //     )
            // )
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Category'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'appbundle_category';
    }
}
