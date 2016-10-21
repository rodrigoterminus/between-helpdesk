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
use AppBundle\Form\TicketType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\ORM\EntityRepository;

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
            )
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

            $result = $query->getQuery()->getResult();
//            dump($query->getQuery()->getDql()); die;

//            if (count($result) == 1) {
//                return $this->redirect($this->generateUrl('ticket_edit', array('number' => $result[0]['number'])));
//            } else {
                $search->totalizer($result);

                $this->get('session')->getFlashBag()
                    ->add(
                        'success', count($result) . ' resultados encontrados.'
                );
//            }
        } else {
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
            'title' => 'Abertura de chamado',
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
     * @Route("/{number}", name="ticket_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($number) {
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();

        $ticket = $em->getRepository('AppBundle:Ticket')->findOneBy(array('number' => $number));

        $entry = new Entry();
        $ticket->getEntries()->add($entry);

        if (!$ticket) {
            throw $this->createNotFoundException('Unable to find Ticket entity.');
        }

        if (!$this->isAllowed($ticket)) {
            return $this->redirect($this->generateUrl('ticket'));
        }

        $editForm = $this->createEditForm($ticket);
        $deleteForm = $this->createDeleteForm($ticket->getId());

        $qb = $em->getRepository('AppBundle:User')->createQueryBuilder('user');
        $queryUsers = $qb->select()
            ->where("user.id != " . $this->get('security.context')->getToken()->getUser()->getId())
            ->andWhere("user.roles LIKE '%_ADMIN%'")
            ->andWhere("user.enabled = 1")
            ->orderBy('user.name', 'ASC');

        if ($ticket->getAttendant() !== null) {
            $qb->andWhere("user.id != " . $ticket->getAttendant()->getId());
        }

        $users = $queryUsers->getQuery()->getResult();

        return array(
            'title' => '#' . $ticket->getNumber(),
            'entity' => $ticket,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'users' => $users,
        );
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
                
                $entry = $this->fillEntry(new Entry(), $ticket);
                $entry->setAction('take');
                $entries->add($entry);
            }
            
            foreach ($ticket->getEntries() as $entry) {
                if ($counter === $length) {
                    if ($entry->getText() != null) {
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
            
//            dump($ticket->getEntries()); die;
            
            $em->persist($ticket);
            $em->flush();

            if ($newPost === true) {
                $this->get('app.mailer')
                    ->setEvent('entry')
                    ->setTicket($ticket)
                    ->send();
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
        
        $entry = new Entry();
        $entry
            ->setCreatedBy($user)
            ->setCreatedAt(new \DateTime('now'))
            ->setAction('finish')
            ->setTicket($ticket)
            ;

        $ticket
            ->setFinishedBy($user)
            ->setFinishedAt($entry->getCreatedAt())
            ->setStatus('finished')
            ->addEntry($entry);

        $this->get('app.mailer')
            ->setEvent('finish')
            ->setTicket($ticket)
            ->send();

        $em->persist($ticket);
        $em->flush();

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
            $entry = new Entry();
            $entry
                ->setCreatedBy($user)
                ->setCreatedAt(new \DateTime('now'))
                ->setAction($action)
                ->setTicket($ticket)
                ;
            
            if ($user !== $attendant) {
                $entry->setDirectedTo($attendant);
            }
            
            $ticket
                ->setAttendant($attendant)
                ->setStatus('running')
                ->addEntry($entry);

            $em->persist($ticket);
            $em->flush();
            
            $this->get('app.mailer')
                ->setEvent('transfer')
                ->setTicket($ticket)
                ->send();

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
        
        $entry = new Entry();
        $entry
            ->setCreatedBy($user)
            ->setCreatedAt(new \DateTime('now'))
            ->setAction('reopen')
            ->setTicket($ticket)
            ;

        $ticket
            ->setFinishedBy(null)
            ->setFinishedAt(null)
            ->setStatus('running')
            ->addEntry($entry)
        ;

        $em->persist($ticket);
        $em->flush();

        $this->get('app.mailer')
            ->setEvent('reopen')
            ->setTicket($ticket)
            ->send();

        return $this->redirect($this->generateUrl('ticket_edit', array('number' => $ticket->getNumber())));
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

    /**
     * Send emails to users
     * 
     * @param $action
     * 
     * @return boolean
     */
    private function sendEmail($action, $ticket) {
        $currentUser = $this->get('security.token_storage')->getToken()->getUser();
        $messages = [];

        switch ($action) {
            case 'transfer':
                $title = 'Transferência de chamado';
                $subject = 'Um chamado foi transferido para você';

                // Avoid the user to receive the email when it transfer the ticket to itself                
                if ($ticket->getAttendant() !== $currentUser) {
                    $attendantPreference = $ticket->getAttendant()->getPreference('notifications.email.transfer', true);

                    if ($attendantPreference === true || $attendantPreference === null) {
                        $messages[] = [
                            'to' => $ticket->getAttendant(),
                            'title' => $title,
                            'subject' => $subject,
                            'content' => 'Olá, ' . $ticket->getAttendant()->getName() . '.<br><br>' .
                            'O usuário <b>' . $currentUser->getName() . '</b> transferiu o chamado <b>#' . $ticket->getNumber() . '</b> para você. ' .
                            'Para acessá-lo, <a href="' . $this->generateUrl('ticket_edit', ['number' => $ticket->getNumber()]) . '">clique aqui</a>.',
                        ];
                    }
                }

                // Watchers
                foreach ($ticket->getWatchers() as $watcher) {
                    // Watcher notification preference
                    $watcherPreference = $watcher->getPreference('notifications.email.watcher', true);

                    // Avoid current user and the receiver set above to receive the email
                    if ($watcher !== $currentUser && $watcher !== $ticket->getAttendant() && ($watcherPreference === true || $watcherPreference === null)) {
                        $messages[] = [
                            'to' => $watcher,
                            'title' => 'Chamado #' . $ticket->getNumber() . ' transferido',
                            'subject' => 'Chamado #' . $ticket->getNumber() . ' transferido',
                            'content' => 'Olá, ' . $watcher->getName() . '.<br><br>' .
                            'O usuário <b>' . $currentUser->getName() . '</b> transferiu o chamado <b>#' . $ticket->getNumber() . '</b> para o usuário <b>' . $ticket->getAttendant()->getName() . '</b>. ' .
                            'Para acessá-lo, <a href="' . $this->generateUrl('ticket_edit', ['number' => $ticket->getNumber()]) . '">clique aqui</a>.',
                        ];
                    }
                }
                break;
               
            case 'comment':
                $users = [];
                $title = 'Novo comentário';
                $subject = 'Novo comentário no chamado #'. $ticket->getNumber();
                $content = 'Olá, %s.<br><br>' .
                    'O usuário <b>' . $currentUser->getName() . '</b> inseriu um novo comentário no chamado <b>#' . $ticket->getNumber() . '</b>. ' .
                    'Para acessá-lo, <a href="' . $this->generateUrl('ticket_edit', ['number' => $ticket->getNumber()]) . '">clique aqui</a>.';
                
                // Add attendant                
                if ($ticket->getAttendant() !== $currentUser) {
                    $attendantPreference = $ticket->getAttendant()->getPreference('notifications.email.comment', true);

                    if ($attendantPreference === true || $attendantPreference === null) {
                        $users[] = $ticket->getAttendant();
                    }
                }

                // Watchers
                foreach ($ticket->getWatchers() as $watcher) {
                    // Watcher notification preference
                    $watcherPreference = $watcher->getPreference('notifications.email.watcher', true);

                    if (!in_array($watcher, $users) && ($watcherPreference === true || $watcherPreference === null)) {
                        $users[] = $watcher;
                    }
                }
                
                foreach ($users as $user) {
                    // Avoid current user to receive the email
                    if ($currentUser !== $user) {
                        $messages[] = [
                            'to' => $user,
                            'title' => $title,
                            'subject' => $subject,
                            'content' => sprintf($content, $user->getName(), $words['content']),
                        ];
                    }
                }
                
                break;
                
            case 'reopen':
            case 'finish':
                $users = [];
                $title = 'Chamado %s';
                $subject = 'Chamado #' . $ticket->getNumber() . ' %s';
                $content = 'Olá, %s.<br><br>' .
                    'O usuário <b>' . $currentUser->getName() . '</b> %s o chamado <b>#' . $ticket->getNumber() . '</b>. ' .
                    'Para acessá-lo, <a href="' . $this->generateUrl('ticket_edit', ['number' => $ticket->getNumber()]) . '">clique aqui</a>.';

                // Add attendant                
                if ($ticket->getAttendant() !== $currentUser) {
                    $attendantPreference = $ticket->getAttendant()->getPreference('notifications.email.' . $action, true);

                    if ($attendantPreference === true || $attendantPreference === null) {
                        $users[] = $ticket->getAttendant();
                    }
                }

                // Add ticket's creator wether it is a customer
                if ($ticket->getCreatedBy()->isAdmin() === false) {
                    $creatorPreference = $ticket->getCreatedBy()->getPreference('notifications.email.' . $action, true);

                    if ($creatorPreference === true || $creatorPreference === null) {
                        $users[] = $ticket->getCreatedBy();
                    }
                }

                // Watchers
                foreach ($ticket->getWatchers() as $watcher) {
                    // Watcher notification preference
                    $watcherPreference = $watcher->getPreference('notifications.email.watcher', true);

                    if (!in_array($watcher, $users) && ($watcherPreference === true || $watcherPreference === null)) {
                        $users[] = $watcher;
                    }
                }

                switch ($action) {
                    case 'reopen': 
                        $words = ['title' => 'reaberto', 'subject' => 'reaberto', 'content' => 'reabriu'];
                        break;
                    case 'finish': 
                        $words = ['title' => 'finalizado', 'subject' => 'finalizado', 'content' => 'finalizou'];
                        break;
                }

                foreach ($words as $key => $value) {
                    if ($key !== 'content') {
                        ${$key} = sprintf(${$key}, $value);
                    }
                }

                foreach ($users as $user) {
                    // Avoid current user to receive the email
                    if ($currentUser !== $user) {
                        $messages[] = [
                            'to' => $user,
                            'title' => $title,
                            'subject' => $subject,
                            'content' => sprintf($content, $user->getName(), $words['content']),
                        ];
                    }
                }
                break;

            case 'entry':
                $users = [];
                $title = 'Nova interação no chamado #' . $ticket->getNumber();
                $subject = 'Nova interação de chamado';
                $content = 'Olá, %s.<br><br>' .
                    'O usuário <b>' . $currentUser->getName() . '</b> atualizou o chamado <b>#' . $ticket->getNumber() . '</b>. ' .
                    'Para acessá-lo, <a href="' . $this->generateUrl('ticket_edit', ['number' => $ticket->getNumber()], true) . '">clique aqui</a>.';

                // Add attendant                
                if ($ticket->getAttendant() !== $currentUser) {
                    $attendantPreference = $ticket->getAttendant()->getPreference('notifications.email.entry', true);

                    if ($attendantPreference === true || $attendantPreference === null) {
                        $users[] = $ticket->getAttendant();
                    }
                }

                // Add ticket's creator wether it is a customer
                if (!$ticket->getCreatedBy()->isAdmin()) {
                    $creatorPreference = $ticket->getCreatedBy()->getPreference('notifications.email.entry', true);

                    if ($creatorPreference === true || $creatorPreference === null) {
                        $users[] = $ticket->getCreatedBy();
                    }
                }

                // Watchers
                foreach ($ticket->getWatchers() as $watcher) {
                    // Watcher notification preference
                    $watcherPreference = $watcher->getPreference('notifications.email.watcher', true);

                    if (!in_array($watcher, $users) && ($watcherPreference === true || $watcherPreference === null)) {
                        $users[] = $watcher;
                    }
                }

                foreach ($users as $user) {
                    // Avoid current user to receive the email
                    if ($user !== $currentUser) {
                        $messages[] = [
                            'to' => $user,
                            'title' => $title,
                            'subject' => $subject,
                            'content' => sprintf($content, $user->getName()),
                        ];
                    }
                }
                break;
        }

        foreach ($messages as $key => $message) {
            $user = $message['to'];

            $email = \Swift_Message::newInstance()
                ->setSubject($message['subject'])
                ->setFrom(array($this->container->getParameter('contact_email') => $this->container->getParameter('contact_name')))
                ->setTo($user->getEmail())
                ->setBody(
                $this->renderView(
                    'AppBundle:Email:email.single.html.twig', [
                    'title' => $message['title'],
                    'content' => $message['content'],
                    'ticket' => $ticket,
                    ]
                ), 'text/html'
            );
            $this->get('mailer')->send($email);
        }
    }

}
