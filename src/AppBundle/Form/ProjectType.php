<?php

namespace AppBundle\Form;

use AppBundle\Entity\Customer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Doctrine\ORM\EntityRepository;
use function Doctrine\ORM\QueryBuilder;

class ProjectType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, array('label' => 'Nome'))
            ->add('customer', EntityType::class, array(
                    'label' => 'Cliente',
                    'class' => Customer::class,
                    'query_builder' => function(EntityRepository $er) {
                        $qb = $er->createQueryBuilder('c');
                        $qb
                            ->where($qb->expr()->eq('c.activated', ':activated'))
                            ->andWhere($qb->expr()->eq('c.deleted', ':deleted'))
                            ->orderBy('c.name')
                            ->setParameters([
                                ':activated' => true,
                                ':deleted' => false,
                            ]);

                        return $qb;
                    },
                    'choice_label' => 'name',
                    'placeholder' => 'Selecione',
                    'required' => false,
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
            'data_class' => 'AppBundle\Entity\Project'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'appbundle_project';
    }
}
