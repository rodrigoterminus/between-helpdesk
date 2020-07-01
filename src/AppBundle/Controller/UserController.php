<?php

namespace AppBundle\Controller;

use AppBundle\Utils\Search;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\User;
use AppBundle\Form\UserType;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * User controller.
 *
 * @Route("/user")
 */
class UserController extends Controller
{
    /**
     * @var Search
     */
    private $search;

    public function __construct(Search $search)
    {
        $this->search = $search;
    }

    /**
     * Lists all User entities.
     *
     * @Route("/", name="user", options={"expose": true})
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        // $em = $this->getDoctrine()->getManager();

        // $entities = $em->getRepository('AppBundle:User')->findAll();

        // return array(
        //     'entities' => $entities,
        // );

        $repository = $this->getDoctrine()
            ->getRepository('AppBundle:User');
        
        // Query
        $qb = $repository->createQueryBuilder('u');
        $query = $qb
            ->select(array(
                    'u.id',
                    'u.name',
                    'cm.name AS customer',
                    'u.roles',
                    'u.enabled',
                    'u.lastLogin',
                )
            )
            ->leftJoin('AppBundle:Customer', 'cm', 'WITH', 'cm.id = u.customer')
            ->where($qb->expr()->neq('u.id', 1))
            ->addOrderBy('u.name', 'ASC');

        $search = $this->search
            ->addButton(array(
                'label' => 'Novo',
                'icon' => 'add',
                'type' => 'fab',
                'action_type' => 'route',
                'action' => 'user_new',
                )
            )
            ->addColumn(array('name' => 'name', 'label' => 'Nome', 'type' => 'string', 'width' => '34%', 'non_numeric' => true))
            ->addColumn(array('name' => 'customer', 'label' => 'Cliente', 'type' => 'string', 'width' => '33%', 'non_numeric' => true))
            ->addColumn(array('name' => 'roles', 'label' => 'Nível', 'type' => 'array', 'width' => '33%', 'non_numeric' => true, 'translated' => true))
            ->addColumn(array('name' => 'enabled', 'label' => 'Ativo', 'type' => 'string', 'width' => '15%', 'non_numeric' => true, 'translated' => true))
            ->addColumn(array('name' => 'lastLogin', 'label' => 'Último acesso', 'type' => 'datetime', 'width' => '15%', 'non_numeric' => true))
            ->addColumn(array('name' => 'actions', 'label' => 'Ações', 'type' => 'actions', 'width' => '3%', 'actions' => array(
                    array('icon' => 'edit', 'label' => 'Editar', 'type' => 'route', 'route_name' => 'user_edit', 'arguments' => array('id' => ':id')),
                )
            ))
            ->setTranslatePrefix('user');

        $result = $query->getQuery()->getResult();

        return $this->render('AppBundle:Core:search.html.twig', array(
            'title' => 'Usuários',
            'search' => $search,
            'result' => $result,
        ));  
    }
    /**
     * Creates a new User entity.
     *
     * @Route("/", name="user_create")
     * @Method("POST")
     * @Template("AppBundle:User:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new User();
        $form = $this->createCreateForm($entity);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            // Check if email already exists
            $duplicatedEmails = $em->getRepository('AppBundle:User')->findOneBy(array('email' => $entity->getEmail()));

            if ($duplicatedEmails)
                throw new HttpException(500, "Email já cadastrado.");

            $entity
                ->setRoles(array($form->getData()->getRole()))
                ->setPlainPassword($form->getData()->getPassword());

            // var_dump($entity); die;

            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('user_edit', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a User entity.
     *
     * @param User $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(User $entity)
    {
        $form = $this->createForm(UserType::class, $entity, array(
            'action' => $this->generateUrl('user_create'),
            'method' => 'POST',
        ));

        $form['enabled']->setData(true);

        $form
            ->add('password', PasswordType::class, array('label' => 'Senha'))
            ->add('submit', SubmitType::class, array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new User entity.
     *
     * @Route("/new", name="user_new")
     * @Method("GET")
     * @Template("AppBundle:Core:form-basic.html.twig")
     */
    public function newAction()
    {
        $entity = new User();
        $form   = $this->createCreateForm($entity);

        return array(
            'title'  => 'Novo usuário',
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a User entity.
     *
     * @Route("/{id}", name="user_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:User')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing User entity.
     *
     * @Route("/{id}/edit", name="user_edit")
     * @Method("GET")
     * @Template("AppBundle:Core:form-basic.html.twig")
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:User')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'title'       => 'Editar usuário',
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
    * Creates a form to edit a User entity.
    *
    * @param User $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(User $entity)
    {
        $form = $this->createForm(UserType::class, $entity, array(
            'action' => $this->generateUrl('user_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));
        // var_dump($entity->getRoles());
        $roles = $entity->getRoles();
        $form['role']->setData($roles[0]);

        $form->add('submit', SubmitType::class, array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing User entity.
     *
     * @Route("/{id}", name="user_update")
     * @Method("PUT")
     * @Template("AppBundle:User:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:User')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        $entity->setRoles(array($editForm->getData()->getRole()));

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('user_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a User entity.
     *
     * @Route("/{id}", name="user_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('AppBundle:User')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find User entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('user'));
    }

    /**
     * Creates a form to delete a User entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('user_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, array('label' => 'Delete'))
            ->getForm()
        ;
    }
    
    /**
     * Updates user's preferences.
     *
     * @Route("/preferences", name="user_preferences", options={"expose":true})
     * @Method("POST")
     */
    public function preferencesAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        
        $user->setPreferences($request->request->get('preferences'));
        $em->persist($user);
        $em->flush();
        
        return new JsonResponse($user->getPreferencesObject());
    }
}
