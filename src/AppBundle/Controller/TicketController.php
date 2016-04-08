<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\Ticket;
use AppBundle\Entity\Entry;
use AppBundle\Form\TicketType;

use Symfony\Component\Form\Extension\Core\TypeTextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Doctrine\ORM\EntityRepository;

/**
 * Ticket controller.
 *
 * @Route("/ticket")
 */
class TicketController extends Controller
{
    /**
     * Lists all Ticket entities.
     *
     * @Route("/", name="ticket", options={"expose": true})
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        // $em = $this->getDoctrine()->getManager();

        // $entities = $em->getRepository('AppBundle:Ticket')->findAll();

        // return array(
        //     'entities' => $entities,
        // );

        $user = $this->get('security.context')->getToken()->getUser();
        $repository = $this->getDoctrine()
            ->getRepository('AppBundle:Ticket');
        
        // Query
        $qb = $repository->createQueryBuilder('t');
        $query = $qb
            ->select(array(
                    't.number',
                    'cm.name AS customer',
                    't.status',
                    't.priority',
                    'ct.name AS category',
                    'p.name AS project',
                    'u.name AS createdBy',
                    'att.name AS attendant',
                    't.createdAt',
                    't.modifiedAt',
                )
            )
            ->join('AppBundle:Customer', 'cm', 'WITH', 'cm.id = t.customer')
            ->join('AppBundle:Category', 'ct', 'WITH', 'ct.id = t.category')
            ->join('AppBundle:User', 'u', 'WITH', 'u.id = t.createdBy')
            ->leftJoin('AppBundle:Project', 'p', 'WITH', 'p.id = t.project')
            ->leftJoin('AppBundle:User', 'att', 'WITH', 'att.id = t.attendant')
            ->addOrderBy('t.id', 'DESC');

        if ($user->hasRole('ROLE_DEFAULT')) {
            $query = $qb
                ->andWhere($qb->expr()->eq('cm.id', ':customer'))
                ->setParameter('customer', $user->getCustomer()->getId());
        }

        $search = $this->get('infinity.search')
            ->addButton(array(
                'label' => 'Novo',
                'icon' => 'add',
                'type' => 'fab',
                'action_type' => 'route',
                'action' => 'ticket_new',
                )
            )
            ->addColumn(array('name' => 'number', 'label' => '#', 'type' => 'number', 'width' => '7%'))
            ->addColumn(array('name' => 'customer', 'label' => 'Cliente', 'type' => 'string', 'width' => '20%', 'non_numeric' => true))
            ->addColumn(array('name' => 'status', 'label' => 'Status', 'type' => 'string', 'width' => '10%', 'non_numeric' => true, 'translated' => true))
            ->addColumn(array('name' => 'priority', 'label' => 'Prioridade', 'type' => 'string', 'width' => '10%', 'non_numeric' => true, 'translated' => true))
            // ->addColumn(array('name' => 'project', 'label' => 'Projeto', 'type' => 'string', 'width' => '10%', 'non_numeric' => true))
            // ->addColumn(array('name' => 'createdBy', 'label' => 'Usuário', 'type' => 'string', 'width' => '10%', 'non_numeric' => true))
            ->addColumn(array('name' => 'attendant', 'label' => 'Atendente', 'type' => 'string', 'width' => '10%', 'non_numeric' => true))
            ->addColumn(array('name' => 'createdAt', 'label' => 'Criado em', 'type' => 'datetime', 'width' => '10%', 'non_numeric' => true))
            // ->addColumn(array('name' => 'modifiedAt', 'label' => 'Modificação', 'type' => 'datetime', 'width' => '10%', 'non_numeric' => true))
            ->addColumn(array('name' => 'actions', 'label' => 'Ações', 'type' => 'actions', 'width' => '3%', 'actions' => array(
                    array('icon' => 'visibility', 'label' => 'Visualizar', 'type' => 'route', 'route_name' => 'ticket_edit', 'arguments' => array('number' => ':number')),
                )
            ))
            ->setTranslatePrefix('ticket');

        // Form
        $form = $this->createFormBuilder(null, array('csrf_protection' => false))
            ->setMethod('GET')
            ->add('number', 'text', array('label' => 'Número', 'required' => false, 'attr' => array('data-col' => 'mdl-cell--12-col-desktop mdl-cell--12-col-phone') ));

        if ($user->isAdmin()) {
            $form
                ->add('attendant', EntityType::class, array(
                    'label' => 'Atendente',
                    'class' => 'AppBundle:User',
                    'query_builder' => function(EntityRepository $er) {
                        return $er->createQueryBuilder('u')
                            ->where("u.roles LIKE '%ROLE_ADMIN%'")
                            ->orderBy('u.name', 'ASC');
                        },
                    'property' => 'name',
                    'placeholder' => 'Selecione uma opção',
                    'required' => false,
                    'attr' => array('data-col' => 'mdl-cell--12-col-desktop mdl-cell--12-col-phone')
                    )
                );
        }

        $form
            ->add('status', ChoiceType::class, array(
                'label' => 'Status',
                'choices'  => array('' => 'Selecione', 'created' => 'Aguardando atendimento', 'running' => 'Em atendimento', 'finished' => 'Finalizado'),
                'required' => false,
                'attr' => array('data-col' => 'mdl-cell--6-col-desktop mdl-cell--12-col-phone')
                )
            )
            ->add('project', EntityType::class, array(
                'label' => 'Projeto',
                'class' => 'AppBundle:Project',
                'query_builder' => function(EntityRepository $er) use ($user) {
                        // var_dump($user); die;
                        $return = $er->createQueryBuilder('p')
                            // ->where("p.deleted = 0")
                            ->orderBy('p.name', 'ASC');

                        return $return;
                    },
                'property' => 'name',
                'placeholder' => 'Selecione uma opção',
                'required' => false,
                'attr' => array('data-col' => 'mdl-cell--6-col-desktop mdl-cell--12-col-phone')
                )
            )
            ->add('date_initial', 'date', array(
                'label' => 'Data inicial', 
                'widget' => 'single_text', 
                'required' => false,
                'attr' => array('data-col' => 'mdl-cell--6-col-desktop mdl-cell--12-col-phone')
                )
            )
            ->add('date_final', 'date', array(
                'label' => 'Data final', 
                'widget' => 'single_text', 
                'required' => false,
                'attr' => array('data-col' => 'mdl-cell--6-col-desktop mdl-cell--12-col-phone')
                )
            )
            ->add('submit', 'submit', array('label' => 'Pesquisar'));

        $form = $form
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $search->setFormData($data);

            // Number
            if(isset($data['number'])){
                $query = $qb
                    ->andWhere($qb->expr()->like('T.number', $qb->expr()->literal('%'. $data['number'] .'%')));
            }

            // Attendant
            if(isset($data['attendant'])){
                $query = $qb
                    ->andWhere(
                        $qb->expr()->eq('t.attendant', ':attendant')
                    )
                ->setParameter('attendant', $data['attendant']->getId());
            }

            // Initial date
            if(isset($data['date_initial'])){
                $query = $qb->andWhere(
                    $qb->expr()->gte('t.createdAt', ':date_initial')
                )
                ->setParameter('date_initial', $data['date_initial']->format('Y-m-d') .' 00:00:00');
            }

            // Final date
            if(isset($data['date_final'])){
                $query = $qb->andWhere(
                    $qb->expr()->lte('t.createdAt', ':date_final')
                )
                ->setParameter('date_final', $data['date_final']->format('Y-m-d') .' 23:59:59');
            }

            $result = $query->getQuery()->getResult();
            // echo $query->getQuery()->getDql(); die;

            if(count($result) == 1) {
                return $this->redirect($this->generateUrl('ticket_edit', array('number' => $result[0]['number'])));
            }
            else {
                $search->totalizer($result);

                $this->get('session')->getFlashBag()
                    ->add(
                        'success',
                        count($result) .' resultados encontrados.'
                    );
            }
        }
        else {
            $query = $qb
                ->andWhere("t.status != 'finished'")
                ->orWhere("t.finishedAt BETWEEN :date AND CURRENT_DATE()")
                ->setParameter(':date', new \DateTime('-1 days'));

            $result = $query->getQuery()->getResult();
        }

        $result = $query->getQuery()->getResult();

        return $this->render('AppBundle:Core:search.html.twig', array(
            'title' => 'Chamados',
            'search' => $search,
            'result' => $result,
            'form' => $form->createView(),
        ));  
    }
    /**
     * Creates a new Ticket entity.
     *
     * @Route("/", name="ticket_create")
     * @Method("POST")
     * @Template("AppBundle:Ticket:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Ticket();
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();

        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            // Set number
            $query = $em->createQuery("SELECT t.number FROM AppBundle:Ticket t ORDER BY t.number DESC")
                ->setMaxResults(1);
            $result = $query->getOneOrNullResult();

            $last_number = ($result == null) ? null : $result['number'];
            $number = (date('Y') . '00000000');
            
            if ($last_number == null || date('Y') != substr($last_number, 0, 4))
                $number++;
            else
                $number = substr($number, 0, 4) . str_pad((substr($last_number, 4) + 1), 8, 0, STR_PAD_LEFT);

            $entries = new \Doctrine\Common\Collections\ArrayCollection();

            foreach ($entity->getEntries() as $entry) {
                $entry = $this->fillEntry($entry, $entity);

                $entries->add($entry);
            }

            $entity
                ->setNumber($number)
                ->setCreatedBy($user)
                ->setCreatedAt(new \DateTime('now'))
                ->setStatus('created')
                ->setEntries($entries)
                ;

            if (!$user->isAdmin())
                $entity->setCustomer($user->getCustomer());

            // var_dump($entity); die;

            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('ticket_edit', array('number' => $entity->getNumber())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a Ticket entity.
     *
     * @param Ticket $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Ticket $entity)
    {
        $user = $this->get('security.context')->getToken()->getUser();

        $entry = new Entry();
        $entity->getEntries()->add($entry);

        $form = $this->createForm(new TicketType($user), $entity, array(
            'action' => $this->generateUrl('ticket_create'),
            'method' => 'POST',
        ));

        $form
            ->add('subject', 'text', array('label' => 'Assunto'))
            ->add('priority', 'choice', array(
                'label' => 'Prioridade',
                'choices' => array(
                    'low'=> 'Baixa',
                    'medium' => 'Média',
                    'high' => 'Alta',
                ),
                'empty_value' => 'Selecione',
            ))
            ->add('category', 'entity', array(
                'label' => 'Categoria',
                'class' => 'AppBundle:Category',
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy("c.name", 'ASC');
                    },
                'property' => 'name',
                'empty_value' => 'Selecione',
            ))
            
            ->add('submit', 'submit', array('label' => 'Create'));

            if ($user->isAdmin()) 
            {
                $form->add('customer', 'entity', array(
                        'label' => 'Cliente',
                        'class' => 'AppBundle:Customer',
                        'query_builder' => function(EntityRepository $er) {
                            return $er->createQueryBuilder('c')
                                ->where("c.activated = 1");
                            },
                        'property' => 'name',
                        'empty_value' => 'Selecione',
                    ))
                    ->add('project', 'entity', array(
                        'label' => 'Projeto',
                        'class' => 'AppBundle:Project',
                        'property' => 'name',
                        'empty_value' => 'Selecione',
                        'required' => false,
                    ));
            }
            else
            {
                $projectRepository = $this->getDoctrine()
                    ->getRepository('AppBundle:Project');
                $projectQuery = $projectRepository->createQueryBuilder('p')
                                ->where("p.customer = ". $user->getCustomer()->getId())
                                ->orderBy("p.name", 'ASC');

                $form->add('project', 'entity', array(
                    'label' => 'Projeto',
                    'class' => 'AppBundle:Project',
                    'query_builder' => $projectQuery,
                    'property' => 'name',
                    'empty_value' => 'Selecione',
                    'required' => false,
                ));
            }

        return $form;
    }

    /**
     * Displays a form to create a new Ticket entity.
     *
     * @Route("/new", name="ticket_new")
     * @Method("GET")
     * @Template("")
     */
    public function newAction()
    {
        $user = $this->get('security.context')->getToken()->getUser();
        
        // if ($user->isAdmin())
        //     return $this->redirect($this->generateUrl('ticket'));

        $entity = new Ticket();
        $form   = $this->createCreateForm($entity);

        return array(
            'title' => 'Abertura de chamado',
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Ticket entity.
     *
     * @Route("/{id}/show", name="ticket_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Ticket')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Ticket entity.');
        }

        if (!$this->isAllowed($entity))
            return $this->redirect($this->generateUrl('ticket'));

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Ticket entity.
     *
     * @Route("/{number}", name="ticket_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($number)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();
        
        $entity = $em->getRepository('AppBundle:Ticket')->findOneBy(array('number' => $number));
        
        $entry = new Entry();
        $entity->getEntries()->add($entry);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Ticket entity.');
        }

        if (!$this->isAllowed($entity))
            return $this->redirect($this->generateUrl('ticket'));

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($entity->getId());

        return array(
            'title'       => '#'. $entity->getNumber(),
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    private function isAllowed($entity)
    {
        $user = $this->get('security.context')->getToken()->getUser();

        if (!$user->isAdmin() && $user->getCustomer()->getId() != $entity->getCustomer()->getId())
            return false;
        else
            return true;
    }

    /**
    * Creates a form to edit a Ticket entity.
    *
    * @param Ticket $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Ticket $entity)
    {
        $user = $this->get('security.context')->getToken()->getUser();

        $form = $this->createForm(new TicketType($user), $entity, array(
            'action' => $this->generateUrl('ticket_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        if ($user->isAdmin() && $entity->getStatus() != 'finished') {
            $projectRepository = $this->getDoctrine()
                ->getRepository('AppBundle:Project');
            $projectQuery = $projectRepository->createQueryBuilder('p')
                            ->where("p.customer = ". $entity->getCustomer()->getId())
                            ->orderBy("p.name", 'ASC');

            $form
                ->add('subject', 'text', array('label' => 'Assunto'))
                ->add('priority', 'choice', array(
                    'label' => 'Prioridade',
                    'choices' => array(
                        'low'=> 'Baixa',
                        'medium' => 'Média',
                        'high' => 'Alta',
                    ),
                    'empty_value' => 'Selecione',
                ))
                ->add('project', 'entity', array(
                    'label' => 'Projeto',
                    'class' => 'AppBundle:Project',
                    'query_builder' => $projectQuery,
                    'property' => 'name',
                    'empty_value' => 'Selecione',
                    'required' => false,
                ))
                ->add('category', 'entity', array(
                    'label' => 'Categoria',
                    'class' => 'AppBundle:Category',
                    'query_builder' => function(EntityRepository $er) {
                        return $er->createQueryBuilder('c')
                            ->orderBy("c.name", 'ASC');
                        },
                    'property' => 'name',
                    'empty_value' => 'Selecione',
                ));
        }

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }

    /**
     * Edits an existing Ticket entity.
     *
     * @Route("/{id}", name="ticket_update")
     * @Method("PUT")
     * @Template("AppBundle:Ticket:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();

        $entity = $em->getRepository('AppBundle:Ticket')->find($id);
        
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Ticket entity.');
        }

        if (!$this->isAllowed($entity))
            return $this->redirect($this->generateUrl('ticket'));

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        $entries = new \Doctrine\Common\Collections\ArrayCollection();
        $length = count($entity->getEntries());
        $counter = 1;

        if ($editForm->isValid()) {
            foreach ($entity->getEntries() as $entry) {
                if ($counter == $length)
                {
                    if ($entry->getText() != null) {
                        $entry = $this->fillEntry($entry, $entity);
                        $entries->add($entry);
                    }
                }
                else
                {
                    $entries->add($entry);
                }

                $counter++;
            }

            $entity
                ->setModifiedAt(new \DateTime('now'))
                ->setEntries($entries);

            if ($user->isAdmin() && $entity->getAttendant() == null)
            {
                $entity
                    ->setAttendant($user)
                    ->setStatus('running');
            }

            // die;
            $em->flush();

            return $this->redirect($this->generateUrl('ticket_edit', array('number' => $entity->getNumber())));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    private function fillEntry($entry, $ticket)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $orign = ($user->isAdmin()) ? 'admin' : 'customer';

        $entry
            ->setUser($user)
            ->setTicket($ticket)
            ->setCreatedAt(new \DateTime('now'))
            ->setOrigin($orign);

        return $entry;
    }

    /**
     * Finish an existing Ticket entity.
     *
     * @Route("/{number}/finish", name="ticket_finish", options={"expose":true})
     * @Method("GET")
     * @Template("AppBundle:Ticket:edit.html.twig")
     */
    public function finishAction(Request $request, $number)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();

        $entity = $em->getRepository('AppBundle:Ticket')->findOneBy(array('number' => $number));
        
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Ticket entity.');
        }

        if (!$this->isAllowed($entity))
            return $this->redirect($this->generateUrl('ticket'));

        $entity
            ->setFinishedBy($user)
            ->setFinishedAt(new \DateTime('now'))
            ->setStatus('finished')
            ;

        $em->persist($entity);
        $em->flush();

        return $this->redirect($this->generateUrl('ticket_edit', array('number' => $entity->getNumber())));
    }

    /**
     * Reopen an existing Ticket entity.
     *
     * @Route("/{number}/reopen", name="ticket_reopen", options={"expose":true})
     * @Method("GET")
     * @Template("AppBundle:Ticket:edit.html.twig")
     */
    public function reopenAction(Request $request, $number)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();

        $entity = $em->getRepository('AppBundle:Ticket')->findOneBy(array('number' => $number));
        
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Ticket entity.');
        }

        if (!$user->isAdmin())
            return $this->redirect($this->generateUrl('ticket'));

        $entity
            ->setFinishedBy(null)
            ->setFinishedAt(null)
            ->setStatus('running')
            ;

        $em->persist($entity);
        $em->flush();

        return $this->redirect($this->generateUrl('ticket_edit', array('number' => $entity->getNumber())));
    }

    /**
     * Deletes a Ticket entity.
     *
     * @Route("/{id}", name="ticket_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('AppBundle:Ticket')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Ticket entity.');
            }

            return $this->redirect($this->generateUrl('ticket_edit', array('number' => $entity->getNumber())));

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('ticket'));
    }

    /**
     * Creates a form to delete a Ticket entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('ticket_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }
}
