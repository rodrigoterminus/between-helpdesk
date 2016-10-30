<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\Ticket;
use AppBundle\Entity\Entry;
use AppBundle\Entity\Rating;
use AppBundle\Form\TicketType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;

/**
 * Ticket controller.
 *
 * @Route("/ticket")
 */
class TicketController extends Controller {

    /**
     * Lists all Ticket entities.
     *
     * @Route("/", name="ticket", options={"expose": true})
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request) {
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
                't.id',
                't.number',
                'cm.name AS customer',
                't.subject',
                't.status',
                't.priority',
                'ct.name AS category',
                'p.name AS project',
                'u.name AS createdBy',
                'att.name AS attendant',
                't.createdAt',
                't.modifiedAt',
                'r.solved',
                'r.rate',
                'r.comment',
                )
            )
            ->join('AppBundle:Customer', 'cm', 'WITH', 'cm.id = t.customer')
            ->join('AppBundle:Category', 'ct', 'WITH', 'ct.id = t.category')
            ->join('AppBundle:User', 'u', 'WITH', 'u.id = t.createdBy')
            ->leftJoin('AppBundle:Project', 'p', 'WITH', 'p.id = t.project')
            ->leftJoin('AppBundle:User', 'att', 'WITH', 'att.id = t.attendant')
            ->leftJoin('AppBundle:Rating', 'r', 'WITH', 'r.ticket = t.id')
            ->addOrderBy('t.id', 'DESC');
        
        if ($user->isAdmin() === false) {
            $query = $qb
                ->where($qb->expr()->eq('cm.id', ':customer'))
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
            ->addColumn(array('name' => 'number', 'label' => 'Número', 'type' => 'number', 'width' => '7%'))
            ->addColumn(array('name' => 'customer', 'label' => 'Cliente', 'type' => 'string', 'width' => '20%', 'non_numeric' => true))
            ->addColumn(array('name' => 'subject', 'label' => 'Assunto', 'type' => 'string', 'width' => '20%', 'non_numeric' => true))
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
            ->add('number', TextType::class, array('label' => 'Número', 'required' => false, 'attr' => array('data-col' => 'mdl-cell--6-col-desktop mdl-cell--6-col-phone')))
            ->add('subject', TextType::class, array('label' => 'Assunto', 'required' => false, 'attr' => array('data-col' => 'mdl-cell--6-col-desktop mdl-cell--6-col-phone')));

        if ($user->isAdmin()) {
            $form
                ->add('customer', EntityType::class, array(
                    'label' => 'Cliente',
                    'class' => 'AppBundle:Customer',
                    'query_builder' => function(EntityRepository $er) {
                        $return = $er->createQueryBuilder('p')
                            // ->where("p.deleted = 0")
                            ->orderBy('p.name', 'ASC');

                        return $return;
                    },
                    'property' => 'name',
                    'placeholder' => 'Selecione uma opção',
                    'required' => false,
                    'attr' => array('data-col' => 'mdl-cell--6-col-desktop mdl-cell--6-col-phone')
                    )
                )
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
                    'attr' => array('data-col' => 'mdl-cell--6-col-desktop mdl-cell--6-col-phone')
                    )
            );
        }

        $form
            ->add('status', ChoiceType::class, array(
                'label' => 'Status',
                'choices' => array('' => 'Selecione', 'created' => 'Aguardando atendimento', 'running' => 'Em atendimento', 'finished' => 'Finalizado'),
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
            ->add('date_initial', DateType::class, array(
                'label' => 'Data inicial',
                'widget' => 'single_text',
                'required' => false,
                'attr' => array('data-col' => 'mdl-cell--6-col-desktop mdl-cell--12-col-phone')
                )
            )
            ->add('date_final', DateType::class, array(
                'label' => 'Data final',
                'widget' => 'single_text',
                'required' => false,
                'attr' => array('data-col' => 'mdl-cell--6-col-desktop mdl-cell--12-col-phone')
                )
            );
        
        if ($user->isAdmin()) {
            $rating = [
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
                '5' => '5',
            ];
            
            $form
                ->add('rate_min', ChoiceType::class, [
                    'label' => 'Avaliação mínima',
                    'choices' => $rating,
                    'placeholder' => 'Selecione uma opção',
                    'required' => false,
                    'attr' => array('data-col' => 'mdl-cell--3-col-desktop mdl-cell--6-col-phone'),
                ])
                ->add('rate_max', ChoiceType::class, [
                    'label' => 'Avaliação máxima',
                    'choices' => $rating,
                    'placeholder' => 'Selecione uma opção',
                    'required' => false,
                    'attr' => array('data-col' => 'mdl-cell--3-col-desktop mdl-cell--6-col-phone'),
                ])
                ->add('solved', ChoiceType::class, [
                    'label' => 'Solucionado?',
                    'choices' => ['Não', 'Sim'],
                    'placeholder' => 'Selecione uma opção',
                    'required' => false,
                ]);
        }
                
        $form        
            ->add('submit', SubmitType::class, array('label' => 'Pesquisar'));

        $form = $form
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $search->setFormData($data);
            
            // Number
            if (!empty($data['number'])) {
                $query = $qb
                    ->andWhere($qb->expr()->like('t.number', $qb->expr()->literal('%' . $data['number'] . '%')));
            }
            
            // Number
            if (!empty($data['subject'])) {
                $query = $qb
                    ->andWhere($qb->expr()->like('t.subject', $qb->expr()->literal('%' . $data['subject'] . '%')));
            }

            // Customer
            if (!empty($data['customer'])) {
                $query = $qb
                    ->andWhere(
                        $qb->expr()->eq('t.customer', ':customer')
                    )
                    ->setParameter('customer', $data['customer']->getId());
            }
            
            // Status
            if (!empty($data['status'])) {
                $query = $qb
                    ->andWhere(
                        $qb->expr()->eq('t.status', ':status')
                    )
                    ->setParameter('status', $data['status']);
            }

            // Attendant
            if (!empty($data['attendant'])) {
                $query = $qb
                    ->andWhere(
                        $qb->expr()->eq('t.attendant', ':attendant')
                    )
                    ->setParameter('attendant', $data['attendant']->getId());
            }

            // Initial date
            if (!empty($data['date_initial'])) {
                $query = $qb->andWhere(
                        $qb->expr()->gte('t.createdAt', ':date_initial')
                    )
                    ->setParameter('date_initial', $data['date_initial']->format('Y-m-d') . ' 00:00:00');
            }

            // Final date
            if (!empty($data['date_final'])) {
                $query = $qb->andWhere(
                        $qb->expr()->lte('t.createdAt', ':date_final')
                    )
                    ->setParameter('date_final', $data['date_final']->format('Y-m-d') . ' 23:59:59');
            }
            
            // Solved
            if (isset($data['solved'])) {
                $query = $qb
                    ->andWhere(
                        $qb->expr()->eq('r.solved', ':solved')
                    )
                    ->setParameter('solved', $data['solved']);
            }
            
            // Rate min
            if (!empty($data['rate_min'])) {
                $query = $qb
                    ->andWhere(
                        $qb->expr()->gte('r.rate', ':rate_min')
                    )
                    ->setParameter('rate_min', $data['rate_min']);
            }
            
            // Rate min
            if (!empty($data['rate_max'])) {
                $query = $qb
                    ->andWhere(
                        $qb->expr()->lte('r.rate', ':rate_max')
                    )
                    ->setParameter('rate_max', $data['rate_max']);
            }

            $result = $query->getQuery()->getResult();            
            $search->totalizer($result);

            $this->get('session')->getFlashBag()
                ->add('success', count($result) . ' resultados encontrados.');
        } else {
            $query = $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->neq('t.status', ':status'),
                        $qb->expr()->between('t.finishedAt', ':from', ':to')
                ))
                ->setParameter(':from', new \Datetime('-1 day'))
                ->setParameter(':to', new \Datetime('now'))
                ->setParameter(':status', 'finished');
//            dump($query->getQuery()->getDql()); die;
            $result = $query->getQuery()->getResult();
        }

//        $result = $query->getQuery()->getResult();        
        $search->setResult($result);

        return $this->render('AppBundle:Core:search.html.twig', array(
                'title' => 'Tickets',
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
    public function createAction(Request $request) {
        $ticket = new Ticket();
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();

        $form = $this->createCreateForm($ticket);
        $form->handleRequest($request);

        if ($form->isValid()) {
            // Set number
            $query = $em->createQuery("SELECT t.number FROM AppBundle:Ticket t ORDER BY t.number DESC")
                ->setMaxResults(1);
            $result = $query->getOneOrNullResult();
                        
            $last_number = ($result == null) ? null : $result['number'];
            $number = (date('Y') . '00000000');

            if ($last_number == null || date('Y') != substr($last_number, 0, 4)) {
                $number++;
            } else {
                $number = substr($number, 0, 4) . str_pad((substr($last_number, 4) + 1), 8, 0, STR_PAD_LEFT);
            }

            $entries = new \Doctrine\Common\Collections\ArrayCollection();

            foreach ($ticket->getEntries() as $entry) {
                $entry = $this->fillEntry($entry, $ticket);

                $entries->add($entry);
            }

            $ticket
                ->setNumber($number)
                ->setCreatedBy($user)
                ->setCreatedAt(new \DateTime('now'))
                ->setStatus('created')
                ->setEntries($entries)
            ;

            if (!$user->isAdmin()) {
                $ticket->setCustomer($user->getCustomer());
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($ticket);
            $em->flush();
            $em->refresh($ticket);
            
            // Move files
            $entry = $ticket->getEntries()->get(0);
            $this->moveFiles($em, $entry, $form['entries'][0]['uploads']->getData());
            $em->flush();

            return $this->redirect($this->generateUrl('ticket_edit', array('number' => $ticket->getNumber())));
        }

        return array(
            'entity' => $ticket,
            'form' => $form->createView(),
        );
    }
    
    /**
     * Move uploaded files
     * 
     * @param $em
     * @param $entry
     * @param $files
     * 
     * @return boolean
     */
    private function moveFiles(&$em, $entry, $files)
    {
        $dir = $this->get('kernel')->getRootDir() .'/../web/attachments/'. $entry->getTicket()->getId() .'/';
        $counter = 0;
        $filesJson = [];
        
        foreach ($files as $file) {
            if ($file !== null && strstr($file->getClientMimeType(), 'image') !== false) {
                $fileNameArray = explode('.', $file->getClientOriginalName());
                $extension = end($fileNameArray);
                $name = md5($file->getClientOriginalName() . time()) .'.'. $extension;

                $filesJson[] = [
                    'extension' => $extension,
                    'originalName' => $file->getClientOriginalName(),
                    'name' => $name,
                ];

                $file->move($dir, $name);
                $counter++;
            }
        }
        
        if ($counter > 0) {
            $entry->setFiles(json_encode($filesJson));
            $em->persist($entry);
        }
    }
    
    /**
     * Creates a form to create a Ticket entity.
     *
     * @param Ticket $ticket The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Ticket $ticket) {
        $user = $this->get('security.context')->getToken()->getUser();

        $entry = new Entry();
        $ticket->getEntries()->add($entry);

        $form = $this->createForm(new TicketType($user), $ticket, array(
            'action' => $this->generateUrl('ticket_create'),
            'method' => 'POST',
        ));

        $form
            ->add('subject', TextType::class, array('label' => 'Assunto'))
            ->add('priority', ChoiceType::class, array(
                'label' => 'Prioridade',
                'choices' => array(
                    'low' => 'Baixa',
                    'medium' => 'Média',
                    'high' => 'Alta',
                ),
                'empty_value' => 'Selecione',
            ))
            ->add('category', EntityType::class, array(
                'label' => 'Categoria',
                'class' => 'AppBundle:Category',
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy("c.name", 'ASC');
                },
                'property' => 'name',
                'empty_value' => 'Selecione',
            ))
            ->add('submit', SubmitType::class, array('label' => 'Create'));

        if ($user->isAdmin()) {
            $form->add('customer', EntityType::class, array(
                    'label' => 'Cliente',
                    'class' => 'AppBundle:Customer',
                    'query_builder' => function(EntityRepository $er) {
                        return $er->createQueryBuilder('c')
                            ->where("c.activated = 1");
                    },
                    'property' => 'name',
                    'empty_value' => 'Selecione',
                ))
                ->add('project', EntityType::class, array(
                    'label' => 'Projeto',
                    'class' => 'AppBundle:Project',
                    'property' => 'name',
                    'empty_value' => 'Selecione',
                    'required' => false,
            ));
        } else {
            $projectRepository = $this->getDoctrine()
                ->getRepository('AppBundle:Project');
            $projectQuery = $projectRepository->createQueryBuilder('p')
                ->where("p.customer = " . $user->getCustomer()->getId())
                ->orderBy("p.name", 'ASC');

            $form->add('project', EntityType::class, array(
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
     * @Template()
     */
    public function newAction() {
        $ticket = new Ticket();
        $form = $this->createCreateForm($ticket);

        return array(
            'title' => 'Abertura de ticket',
            'entity' => $ticket,
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a Ticket entity.
     *
     * @Route("/{id}/show", name="ticket_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id) {
        $em = $this->getDoctrine()->getManager();

        $ticket = $em->getRepository('AppBundle:Ticket')->find($id);

        if (!$ticket) {
            throw $this->createNotFoundException('Unable to find Ticket entity.');
        }

        if (!$this->isAllowed($ticket)) {
            return $this->redirect($this->generateUrl('ticket'));
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $ticket,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Ticket entity.
     *
     * @Route("/{number}", name="ticket_edit", options={"expose": true})
     * @Method("GET")
     * @Template()
     */
    public function editAction($number) {
        $em = $this->getDoctrine()->getManager();
        $ticket = $em->getRepository('AppBundle:Ticket')->findOneBy(array('number' => $number));
        
        if (!$ticket) {
            throw $this->createNotFoundException('Unable to find Ticket entity.');
        } elseif (!$this->isAllowed($ticket)) {
            return $this->redirect($this->generateUrl('ticket'));
        }
        
        $entry = new Entry();
        $ticket->getEntries()->add($entry);

        $editForm = $this->createEditForm($ticket);
        $deleteForm = $this->createDeleteForm($ticket->getId());

        $qb = $em->getRepository('AppBundle:User')->createQueryBuilder('user');
        $queryUsers = $qb->select()
//            ->where("user.id != " . $this->get('security.context')->getToken()->getUser()->getId())
            ->andWhere("user.roles LIKE '%_ADMIN%'")
            ->andWhere("user.enabled = 1")
            ->orderBy('user.name', 'ASC');

        if ($ticket->getAttendant() !== null) {
            $qb->andWhere("user.id != " . $ticket->getAttendant()->getId());
        }

        $users = $queryUsers->getQuery()->getResult();
        
        // Statistics
        $between = $this->get('between');
        $statistics = [];
        
        
        if ($ticket->getStatus() === 'waiting') {
            
        } else {
            $takeEntry = $ticket->getEntries()->matching(
                Criteria::create()
                    ->where(Criteria::expr()->eq('action', 'take'))
                    ->orWhere(Criteria::expr()->eq('action', 'transfer'))
                )
                ->first();
            
            if ($takeEntry) {
                $statistics['wait'] = $between->formatDateDiff($ticket->getCreatedAt(), $takeEntry->getCreatedAt());

                if ($ticket->getStatus() === 'finished') {
                    $finishEntry = $ticket->getEntries()->matching(
                        Criteria::create()
                            ->where(Criteria::expr()->eq('action', 'finish'))
                        )
                        ->last();
                    
                    if ($finishEntry) {
                        $statistics['service'] = $between->formatDateDiff($takeEntry->getCreatedAt(), $finishEntry->getCreatedAt());
                    }
                } else {
                    $statistics['service'] = $between->formatDateDiff($takeEntry->getCreatedAt(), new \Datetime('now'));
                }
            }
            
        }

        return array(
            'title' => '#' . $ticket->getNumber(),
            'entity' => $ticket,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'users' => $users,
            'statistics' => $statistics,
        );
    }

    private function isAllowed($ticket) {
        $user = $this->get('security.context')->getToken()->getUser();

        if (!$user->isAdmin() && $user->getCustomer()->getId() != $ticket->getCustomer()->getId()) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Creates a form to edit a Ticket entity.
     *
     * @param Ticket $ticket The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(Ticket $ticket) {
        $user = $this->get('security.context')->getToken()->getUser();

        $form = $this->createForm(new TicketType($user), $ticket, array(
            'action' => $this->generateUrl('ticket_update', array('id' => $ticket->getId())),
            'method' => 'PUT',
        ));

        if ($user->isAdmin() && $ticket->getStatus() != 'finished') {
            $projectRepository = $this->getDoctrine()
                ->getRepository('AppBundle:Project');
            $projectQuery = $projectRepository->createQueryBuilder('p')
                ->where("p.customer = " . $ticket->getCustomer()->getId())
                ->orderBy("p.name", 'ASC');

            $form
                ->add('subject', TextType::class, array('label' => 'Assunto'))
                ->add('priority', ChoiceType::class, array(
                    'label' => 'Prioridade',
                    'choices' => array(
                        'low' => 'Baixa',
                        'medium' => 'Média',
                        'high' => 'Alta',
                    ),
                    'empty_value' => 'Selecione',
                ))
                ->add('project', EntityType::class, array(
                    'label' => 'Projeto',
                    'class' => 'AppBundle:Project',
                    'query_builder' => $projectQuery,
                    'property' => 'name',
                    'empty_value' => 'Selecione',
                    'required' => false,
                ))
                ->add('category', EntityType::class, array(
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

        $form->add('submit', SubmitType::class, array('label' => 'Update'));

        return $form;
    }

    /**
     * Edits an existing Ticket entity.
     *
     * @Route("/{id}", name="ticket_update")
     * @Method("PUT")
     * @Template("AppBundle:Ticket:edit.html.twig")
     */
    public function updateAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();

        $ticket = $em->getRepository('AppBundle:Ticket')->find($id);

        if (!$ticket) {
            throw $this->createNotFoundException('Unable to find Ticket entity.');
        }

        if (!$this->isAllowed($ticket)) {
            return $this->redirect($this->generateUrl('ticket'));
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($ticket);
        $editForm->handleRequest($request);

        $entries = new \Doctrine\Common\Collections\ArrayCollection();
        $length = count($ticket->getEntries());
        $counter = 1;
        $newPost = false;

        if ($editForm->isValid()) {
            if ($user->isAdmin() && $ticket->getAttendant() == null) {
                $ticket
                    ->setAttendant($user)
                    ->setStatus('running');
                
                $entry = $this->fillEntry(new Entry(), $ticket)
                    ->setAction('take');
                $entries->add($entry);
            }
            
            foreach ($ticket->getEntries() as $entry) {
                if ($counter === $length) {
                    if ($entry->getText() !== null) {
                        $entry = $this->fillEntry($entry, $ticket);
                        $entries->add($entry);
                        
                        $this->moveFiles($em, $entry, $editForm['entries'][$counter - 1]['uploads']->getData());
                        $newPost = true;
                    }
                } else {
                    $entries->add($entry);
                }

                $counter++;
            }

            $ticket
                ->setModifiedAt(new \DateTime('now'))
                ->setEntries($entries);
            
            // Add user as watcher if it has posted and it is not related to the ticket
            if ($newPost === true) {
                $setWatcher = false;
                
                // Customer and not the creator
                if (!$ticket->getCreatedBy()->isAdmin() && $ticket->getCreatedBy() !== $user) {
                    $setWatcher = true;
                } 
                // Attendant but not the responsible for the ticket
                elseif ($ticket->getCreatedBy()->isAdmin() && $ticket->getAttendant() !== $user) {
                    $setWatcher = true;
                }
                
                if ($setWatcher === true && $ticket->getWatchers()->contains($user) === false) {
                    $ticket->addWatcher($user);
                }
            }
            
            $em->persist($ticket);
            $em->flush();

            if ($newPost === true) {
                $this->get('app.notifier')
                    ->setEvent('post')
                    ->setTicket($ticket)
                    ->notify();
            }

            return $this->redirect($this->generateUrl('ticket_edit', array('number' => $ticket->getNumber())));
        }

        return array(
            'entity' => $ticket,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    private function fillEntry($entry, $ticket) {
        $user = $this->get('security.context')->getToken()->getUser();
        $orign = ($user->isAdmin()) ? 'admin' : 'customer';

        $entry
            ->setCreatedBy($user)
            ->setTicket($ticket)
            ->setCreatedAt(new \DateTime('now'))
            ->setOrigin($orign);

        return $entry;
    }
    
    /**
     * Associate a user to a ticket
     *
     * @Route("/{number}/watcher", name="ticket_watcher", options={"expose"=true})
     * @Method("GET")
     * @Template()
     */
    public function watcherAction(Request $request, $number) {
        $response = [];
        $em = $this->getDoctrine()->getManager();
        $ticket = $em->getRepository('AppBundle:Ticket')->findOneBy(array('number' => $number));
        $user = $this->get('security.context')->getToken()->getUser();

        if (!$ticket) {
            throw $this->createNotFoundException('Unable to find Ticket entity.');
        } else {
            $subscribed = null;

            foreach ($ticket->getWatchers() as $watcher) {
                if ($user->getId() === $watcher->getId()) {
                    $ticket->removeWatcher($user);
                    $subscribed = false;
                    break;
                }
            }

            if ($subscribed === null) {
                $ticket->addWatcher($user);
                $subscribed = true;
            }

            $em->persist($ticket);
            $em->flush();
        }

        return new JsonResponse([
            'subscribed' => $subscribed,
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
            ]
        ]);
    }

    /**
     * Finish an existing Ticket entity.
     *
     * @Route("/{number}/finish", name="ticket_finish", options={"expose":true})
     * @Method("GET")
     * @Template("AppBundle:Ticket:edit.html.twig")
     */
    public function finishAction($number) {
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();

        $ticket = $em->getRepository('AppBundle:Ticket')->findOneBy(array('number' => $number));

        if (!$ticket) {
            throw $this->createNotFoundException('Unable to find Ticket entity.');
        }

        if (!$this->isAllowed($ticket)) {
            return $this->redirect($this->generateUrl('ticket'));
        }
        
        $entry = $this->fillEntry(new Entry(), $ticket)
            ->setAction('finish');

        $ticket
            ->setFinishedBy($user)
            ->setFinishedAt($entry->getCreatedAt())
            ->setStatus('finished')
            ->addEntry($entry);

        $em->persist($ticket);
        $em->flush();
        
        $this->get('app.notifier')
            ->setEvent('finish')
            ->setTicket($ticket)
            ->notify();

        return $this->redirect($this->generateUrl('ticket_edit', array('number' => $ticket->getNumber())));
    }

    /**
     * Transfer ticket to another user.
     *
     * @Route("/{number}/transfer/{userId}", name="ticket_transfer", options={"expose":true})
     * @Method("GET")
     */
    public function transferAction(Request $request, $number, $userId) {
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();

        $ticket = $em->getRepository('AppBundle:Ticket')->findOneBy(array('number' => $number));

        if (!$ticket) {
            throw $this->createNotFoundException('Unable to find Ticket entity.');
        } else if ($user->isAdmin() === false) {
            throw $this->createAccessDeniedException('You don\'t have permission to do this.');
        } else {
            $attendant = $em->getRepository('AppBundle:User')->find($userId);

            if (!$attendant) {
                throw $this->createNotFoundException('Attendant not found.');
            }

            $action = ($user === $attendant) ? 'take' : 'transfer';
            $entry = $this->fillEntry(new Entry(), $ticket)
                ->setAction($action)
                ->setDirectedTo($attendant);
            
            $ticket
                ->setAttendant($attendant)
                ->setStatus('running')
                ->addEntry($entry);

            $em->persist($ticket);
            $em->flush();
            
            $this->get('app.notifier')
                ->setEvent($action)
                ->setTicket($ticket)
                ->notify();

            return $this->redirect($this->generateUrl('ticket_edit', array('number' => $ticket->getNumber())));
        }
    }

    /**
     * Reopen an existing Ticket entity.
     *
     * @Route("/{number}/reopen", name="ticket_reopen", options={"expose":true})
     * @Method("GET")
     * @Template("AppBundle:Ticket:edit.html.twig")
     */
    public function reopenAction($number) {
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();

        $ticket = $em->getRepository('AppBundle:Ticket')->findOneBy(array('number' => $number));

        if (!$ticket) {
            throw $this->createNotFoundException('Unable to find Ticket entity.');
        }

        if (!$user->isAdmin()) {
            return $this->redirect($this->generateUrl('ticket'));
        }
        
        $entry = $this->fillEntry(new Entry(), $ticket)
            ->setAction('reopen');

        $ticket
            ->setFinishedBy(null)
            ->setFinishedAt(null)
            ->setStatus('running')
            ->addEntry($entry)
        ;
        
        // Remove rating
        if ($ticket->getRating() !== null) {
            $em->remove($ticket->getRating());
        }

        $em->persist($ticket);
        $em->flush();

        $this->get('app.notifier')
            ->setEvent('reopen')
            ->setTicket($ticket)
            ->notify();

        return $this->redirect($this->generateUrl('ticket_edit', array('number' => $ticket->getNumber())));
    }
    
    /**
     * Rate a ticket
     *
     * @Route("/{id}/rating", name="ticket_rating", options={"expose"=true})
     * @Method("POST")
     * @Template()
     */
    public function ratingAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();
        $ticket = $em->getRepository('AppBundle:Ticket')->find($id);
        $user = $this->get('security.context')->getToken()->getUser();

        if (!$ticket) {
            throw $this->createNotFoundException('Unable to find Ticket entity.');
        } elseif ($user->isAdmin() === true) {
            throw $this->createNotFoundException('Only customers can rate the tickets.');
        } else {
            $rating = new Rating();
            $rating
                ->setSolved(filter_var($request->request->get('solved'), FILTER_VALIDATE_BOOLEAN))
                ->setRate((int) $request->request->get('rate'))
                ->setComment($request->request->get('comment'))
                ->setUser($user)
                ->setTicket($ticket)
                ->setCreatedAt(new \Datetime('now'));
            
//            dump($rating->getSolved()); die;
            
            $em->persist($rating);
            $em->flush();
            
            return new JsonResponse([
                'solved' => $rating->getSolved(),
                'rate' => $rating->getRate(),
                'comment' => $rating->getComment(),
            ]);
        }
    }

    /**
     * Deletes a Ticket entity.
     *
     * @Route("/{id}", name="ticket_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $ticket = $em->getRepository('AppBundle:Ticket')->find($id);

            if (!$ticket) {
                throw $this->createNotFoundException('Unable to find Ticket entity.');
            }

            return $this->redirect($this->generateUrl('ticket_edit', array('number' => $ticket->getNumber())));

            $em->remove($ticket);
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
    private function createDeleteForm($id) {
        return $this->createFormBuilder()
                ->setAction($this->generateUrl('ticket_delete', array('id' => $id)))
                ->setMethod('DELETE')
                ->add('submit', SubmitType::class, array('label' => 'Delete'))
                ->getForm()
        ;
    }
}
