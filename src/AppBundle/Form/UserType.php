<?php

namespace AppBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
            ->add('name', TextType::class, array('label' => 'Nome'))
            ->add('email', EmailType::class, array('label' => 'Email'))
            ->add('role', ChoiceType::class, array(
                'label' => 'Nível de acesso',
                'choices' => array(
                    'Selecione' => '',
                    'Atendente' => 'ROLE_ADMIN',
                    'Cliente' => 'ROLE_DEFAULT',
                ),
            ))
            ->add('customer', EntityType::class, array(
                    'label' => 'Cliente',
                    'class' => 'AppBundle:Customer',
                    'query_builder' => function(EntityRepository $er) {
                        return $er->createQueryBuilder('c')
                            ->where("c.activated = 1");
                        },
                    'choice_label' => 'name',
                    'placeholder' => 'Selecione',
                    'required' => false,
                )
            )
            ->add('enabled', CheckboxType::class, array(
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
