<?php

namespace AppBundle\Controller;

use AppBundle\Presenters\Statistics\CustomerStatistics;
use AppBundle\Services\CustomerService;
use AppBundle\Utils\Search;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\Customer;
use AppBundle\Form\CustomerType;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Customer controller.
 *
 * @Route("/customer", options={"expose"=true})
 */
class CustomerController extends Controller
{
    /**
     * @var Search
     */
    private $search;

    /**
     * @var CustomerService
     */
    private $service;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * CustomerController constructor.
     * @param CustomerService $service
     * @param Search $search
     * @param SerializerInterface $serializer
     */
    public function __construct(
        CustomerService $service,
        Search $search,
        SerializerInterface $serializer,
        EntityManagerInterface $em
    )
    {
        $this->search = $search;
        $this->service = $service;
        $this->serializer = $serializer;
        $this->em = $em;
    }

    /**
     * Lists all Customer entities.
     *
     * @Route("/", name="customer", options={"expose"=true})
     * @Method("GET")
     * @Template("AppBundle:Core:search.html.twig")
     */
    public function indexAction()
    {
        $repository = $this->getDoctrine()
            ->getRepository(Customer::class);
        
        // Query
        /** @var QueryBuilder $qb */
        $qb = $repository->createQueryBuilder('c');
        $query = $qb
            ->andWhere($qb->expr()->eq('c.deleted', ':deleted'))
            ->setParameters([
                ':deleted' => false
            ])
            ->addOrderBy('c.name', 'ASC');

        $search = $this->search
            ->addButton(array(
                'label' => 'Novo',
                'icon' => 'add',
                'type' => 'fab',
                'action_type' => 'route',
                'action' => 'customer_new',
                )
            )
            ->addColumn(array('name' => 'name', 'label' => 'Nome', 'type' => 'string', 'width' => '87%', 'non_numeric' => true))
            ->addColumn(array('name' => 'activated', 'label' => 'Ativo', 'type' => 'string', 'width' => '10%', 'non_numeric' => true, 'translated' => true))
            ->setRoute('customer_edit')
            ->setTranslatePrefix('customer');

        $result = $query->getQuery()->getResult();

        return $this->render('AppBundle:Core:search.html.twig', array(
            'title' => 'Clientes',
            'search' => $search,
            'result' => $result,
        ));  
    }

    /**
     * Creates a new Customer entity.
     *
     * @Route("/", name="customer_create", options={"expose"=true})
     * @Method("POST")
     * @Template("AppBundle:Customer:new.html.twig")
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function createAction(Request $request)
    {
        $customer = new Customer();
        $form = $this->createCreateForm($customer);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->em->persist($customer);
            $this->em->flush();

            return $this->redirect($this->generateUrl('customer_edit', array('id' => $customer->getId())));
        }

        return array(
            'entity' => $customer,
            'form'   => $form->createView(),
        );
    }

    /**
     * Displays a form to create a new Customer entity.
     *
     * @Route("/new", name="customer_new", options={"expose"=true})
     * @Method("GET")
     * @Template("AppBundle:Core:form-basic.html.twig")
     */
    public function newAction()
    {
        $entity = new Customer();
        $form   = $this->createCreateForm($entity);

        return array(
            'title'  => 'Novo cliente',
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Customer entity.
     *
     * @Route("/{id}", name="customer_show", options={"expose"=true})
     * @Method("GET")
     * @Template()
     * @param $id
     * @return array
     */
    public function showAction(Customer $customer)
    {
        $deleteForm = $this->createDeleteForm($customer->getId());

        return array(
            'entity'      => $customer,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Customer entity.
     *
     * @Route("/{id}/edit", name="customer_edit", options={"expose"=true})
     * @Method("GET")
     * @Template("AppBundle:Core:form-basic.html.twig")
     * @param Customer $customer
     * @return array
     */
    public function editAction(Customer $customer)
    {
        $editForm = $this->createEditForm($customer);
        $deleteForm = $this->createDeleteForm($customer->getId());
        $statistics = new CustomerStatistics($customer);

        return array(
            'title' => 'Editar cliente',
            'entity' => $customer,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'before_remove' => 'customer.showRemoveDialog(' . $customer->getId() . ', "' . $deleteForm->getName() . '")',
            'cards' => [
                'statistics' => [
                    'title' => 'Estatísticas',
                    'template' => '@App/Customer/statistics.html.twig',
                    'data' => [
                        'statistics' => $statistics,
                    ]
                ]
            ],
            'scripts' => [
                'assets/js/lib/_dialog.js'
            ],
        );
    }

    /**
     * @Route(
     *     "/{id}/statistics.{_format}",
     *     name="customer_statistics",
     *     defaults={"_format": "json"},
     *     requirements={"format": "json|html"},
     *     options={"expose"=true})
     * @Method("GET")
     * @param Customer $customer
     * @param string $_format
     * @return mixed
     */
    public function getStatisticsAction(Customer $customer, string $_format)
    {
        $statistics = new CustomerStatistics($customer);

        switch ($_format) {
            case 'html':
                return $this->render('@App/Customer/statistics.html.twig', [
                    'statistics' => $statistics,
                ]);

            default:
                $serialized = $this->serializer->serialize($statistics, 'json');
                return JsonResponse::fromJsonString($serialized);
        }
    }

    /**
     * Edits an existing Customer entity.
     *
     * @Route("/{id}", name="customer_update", options={"expose"=true})
     * @Method("PUT")
     * @Template("AppBundle:Customer:edit.html.twig")
     * @param Customer $customer
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function updateAction(Customer $customer, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $deleteForm = $this->createDeleteForm($customer->getId());
        $editForm = $this->createEditForm($customer);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            return $this->redirect(
                $this->generateUrl(
                    'customer_edit',
                    ['id' => $customer->getId()]
                )
            );
        }

        return array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a Customer entity.
     *
     * @Route("/{id}", name="customer_delete", options={"expose"=true})
     * @Method("DELETE")
     * @param Request $request
     * @param Customer $customer
     * @return RedirectResponse
     */
    public function deleteAction(Request $request, Customer $customer)
    {
        $form = $this->createDeleteForm($customer->getId());
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->service->delete($customer);
        }

        return $this->redirect($this->generateUrl('customer'));
    }

    /**
     * Creates a form to create a Customer entity.
     *
     * @param Customer $entity The entity
     *
     * @return FormInterface The form
     */
    private function createCreateForm(Customer $entity)
    {
        $form = $this->createForm(CustomerType::class, $entity, array(
            'action' => $this->generateUrl('customer_create'),
            'method' => 'POST',
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Create'));

        return $form;
    }

    /**
     * Creates a form to edit a Customer entity.
     *
     * @param Customer $customer The entity
     *
     * @return FormInterface The form
     */
    private function createEditForm(Customer $customer)
    {
        return $this
            ->createForm(CustomerType::class, $customer, array(
                'action' => $this->generateUrl('customer_update', array('id' => $customer->getId())),
                'method' => 'PUT',
            ))
            ->add('submit', SubmitType::class, array('label' => 'Update'));
    }

    /**
     * Creates a form to delete a Customer entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return FormInterface The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('customer_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, array('label' => 'Delete'))
            ->getForm()
        ;
    }
}
