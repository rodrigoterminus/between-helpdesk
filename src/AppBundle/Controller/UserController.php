<?php

namespace AppBundle\Controller;

use AppBundle\Services\UserService;
use AppBundle\Utils\Search;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
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

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var UserService
     */
    private $service;

    public function __construct(UserService $userService, Search $search, EntityManagerInterface $em)
    {
        $this->search = $search;
        $this->em = $em;
        $this->service = $userService;
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
            ->andWhere($qb->expr()->eq('u.deleted', ':deleted'))
            ->addOrderBy('u.name', 'ASC')
            ->setParameters([
                ':deleted' => false
            ]);

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
            ->setRoute('user_edit')
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
     * @param Request $request
     * @return array|RedirectResponse
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
     * @param int $id
     * @return array
     */
    public function showAction(int $id)
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
     * @param User $user
     * @return array
     */
    public function editAction(User $user)
    {
        $editForm = $this->createEditForm($user);
        $deleteForm = $this->createDeleteForm($user->getId());

        return array(
            'title'       => 'Editar usuário',
            'entity'      => $user,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'before_remove' => 'between.showConfirmationDialog("Confirma a exclusão deste usuário?", () => between.submitForm("' . $deleteForm->getName() . '"))',
            'scripts' => [
                'assets/js/lib/_dialog.js'
            ],
        );
    }

    /**
     * Edits an existing User entity.
     *
     * @Route("/{id}", name="user_update")
     * @Method("PUT")
     * @Template("AppBundle:User:edit.html.twig")
     */
    public function updateAction(User $user,  Request $request)
    {
        $deleteForm = $this->createDeleteForm($user->getId());
        $editForm = $this->createEditForm($user);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $this->em->flush();
            return $this->redirect(
                $this->generateUrl(
                    'user_edit',
                    ['id' => $user->getId()])
            );
        }

        return array(
            'entity'      => $user,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a User entity.
     *
     * @Route("/{id}", name="user_delete")
     * @Method("DELETE")
     * @param User $user
     * @param Request $request
     * @return RedirectResponse
     */
    public function deleteAction(User $user, Request $request)
    {
        $form = $this->createDeleteForm($user->getId());
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->service->remove($user);
        }

        return $this->redirect($this->generateUrl('user'));
    }

    /**
     * Creates a form to create a User entity.
     *
     * @param User $user The entity
     *
     * @return FormInterface The form
     */
    private function createCreateForm(User $user)
    {
        $form = $this->createForm(UserType::class, $user, array(
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
     * Creates a form to edit a User entity.
     *
     * @param User $user The entity
     *
     * @return FormInterface The form
     */
    private function createEditForm(User $user)
    {
        $form = $this->createForm(UserType::class, $user, array(
            'action' => $this->generateUrl('user_update', array('id' => $user->getId())),
            'method' => 'PUT',
        ));
        // var_dump($entity->getRoles());
        $roles = $user->getRoles();
        $form['role']->setData($roles[0]);

        $form->add('submit', SubmitType::class, array('label' => 'Update'));

        return $form;
    }

    /**
     * Creates a form to delete a User entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return FormInterface The form
     */
    private function createDeleteForm(int $id)
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
     * @param Request $request
     * @return JsonResponse
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
