<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class EntryType extends AbstractType
{
    public function __construct($user)
    {
      $this->user = $user;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('files', 'hidden')
        ;

        if ($this->user->isAdmin()) {
            $builder
                ->add('text', TextareaType::class, array('label' => 'Digite uma mensagem ao cliente', 'required' => false));
        } else {
            $builder
                ->add('text', TextareaType::class, array('label' => 'Descreva sua solicitação ou problema', 'required' => false));
        }
        
        $builder->add('uploads', FileType::class, array(
            'multiple' => true,
            'required' => false,
            'mapped' => false,
        ));
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Entry'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'appbundle_entry';
    }
}
