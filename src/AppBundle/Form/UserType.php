<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Doctrine\ORM\EntityRepository;

class UserType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', array('label' => 'Nome'))
            ->add('email', 'email', array('label' => 'Email'))
            ->add('role', 'choice', array(
                'label' => 'Nível de acesso',
                'choices' => array(
                    ''             => 'Selecione',
                    'ROLE_ADMIN'   => 'Atendente',
                    'ROLE_DEFAULT' => 'Cliente',
                ),
            ))
            ->add('customer', 'entity', array(
                    'label' => 'Cliente',
                    'class' => 'AppBundle:Customer',
                    'query_builder' => function(EntityRepository $er) {
                        return $er->createQueryBuilder('c')
                            ->where("c.activated = 1");
                        },
                    'property' => 'name',
                    'empty_value' => 'Selecione',
                    'required' => false,
                )
            )
            ->add('enabled', 'checkbox', array(
                    'label' => 'Ativado',
                    'required'=> false,
                )
            )
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\User'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'appbundle_user';
    }
}
