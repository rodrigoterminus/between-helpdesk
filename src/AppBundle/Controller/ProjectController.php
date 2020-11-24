<?php

namespace AppBundle\Controller;

use AppBundle\Services\ProjectService;
use AppBundle\Utils\Search;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\Project;
use AppBundle\Form\ProjectType;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Project controller.
 *
 * @Route("/project")
 */
class ProjectController extends Controller
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
     * @var ProjectService
     */
    private $service;

    public function __construct(ProjectService $projectService, Search $search, EntityManagerInterface $em)
    {
        $this->service = $projectService;
        $this->search = $search;
        $this->em = $em;
    }

    /**
     * Lists all Project entities.
     *
     * @Route("/", name="project", options={"expose": true})
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $repository = $this->getDoctrine()
            ->getRepository('AppBundle:Project');
        
        // Query
        /* @var QueryBuilder */
        $qb = $repository->createQueryBuilder('p');
        $query = $qb
            ->select(array(
                    'p.id',
                    'p.name',
                    'c.name AS customer'
                )
            )
            ->join('AppBundle:Customer', 'c', 'WITH', 'c.id = p.customer')
            ->addOrderBy('c.name, p.name', 'ASC')
            ->andWhere($qb->expr()->eq('p.deleted', ':deleted'))
            ->setParameters([
                ':deleted' => false
            ]);

        $search = $this->search
            ->addButton(array(
                'label' => 'Novo',
                'icon' => 'add',
                'type' => 'fab',
                'action_type' => 'route',
                'action' => 'project_new',
                )
            )
            ->addColumn(array('name' => 'name', 'label' => 'Nome', 'type' => 'string', 'width' => '57%', 'non_numeric' => true))
            ->addColumn(array('name' => 'customer', 'label' => 'Cliente', 'type' => 'string', 'width' => '40%', 'non_numeric' => true))
            ->setRoute('project_edit')
            ->setTranslatePrefix('project');

        $result = $query->getQuery()->getResult();

        return $this->render('AppBundle:Core:search.html.twig', array(
            'title' => 'Projetos',
            'search' => $search,
            'result' => $result,
        ));  
    }
    /**
     * Creates a new Project entity.
     *
     * @Route("/", name="project_create")
     * @Method("POST")
     * @Template("AppBundle:Project:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Project();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('project_edit', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a Project entity.
     *
     * @param Project $project The entity
     *
     * @return FormInterface The form
     */
    private function createCreateForm(Project $project)
    {
        $form = $this->createForm(ProjectType::class, $project, array(
            'action' => $this->generateUrl('project_create'),
            'method' => 'POST',
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Project entity.
     *
     * @Route("/new", name="project_new")
     * @Method("GET")
     * @Template("AppBundle:Core:form-basic.html.twig")
     */
    public function newAction()
    {
        $entity = new Project();
        $form   = $this->createCreateForm($entity);

        return array(
            'title' => 'Novo projeto',
            'entity' => $entity,
            'form' => $form->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Project entity.
     *
     * @Route("/{id}/edit", name="project_edit")
     * @Method("GET")
     * @Template("AppBundle:Core:form-basic.html.twig")
     * @param Project $project
     * @return array
     */
    public function editAction(Project $project)
    {
        $editForm = $this->createEditForm($project);
        $deleteForm = $this->createDeleteForm($project->getId());

        return array(
            'title'       => 'Editar projeto',
            'entity'      => $project,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'before_remove' => 'project.showRemoveDialog("' . $deleteForm->getName() . '")',
            'scripts' => [
                'assets/js/lib/_dialog.js'
            ],
        );
    }

    /**
    * Creates a form to edit a Project entity.
    *
    * @param Project $project The entity
    *
    * @return FormInterface The form
    */
    private function createEditForm(Project $project)
    {
        $form = $this->createForm(ProjectType::class, $project, array(
            'action' => $this->generateUrl('project_update', array('id' => $project->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit',  SubmitType::class, array('label' => 'Update'));

        return $form;
    }

    /**
     * Edits an existing Project entity.
     *
     * @Route("/{id}", name="project_update")
     * @Method("PUT")
     * @Template("AppBundle:Project:edit.html.twig")
     * @param Project $project
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function updateAction(Project $project, Request $request)
    {
        $deleteForm = $this->createDeleteForm($project->getId());
        $editForm = $this->createEditForm($project);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $this->em->flush();
            return $this->redirect($this->generateUrl('project_edit', array('id' => $project->getId())));
        }

        return array(
            'entity'      => $project,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a Project entity.
     *
     * @Route("/{id}", name="project_delete")
     * @Method("DELETE")
     * @param Project $project
     * @param Request $request
     * @return RedirectResponse
     */
    public function deleteAction(Project $project, Request $request)
    {
        $form = $this->createDeleteForm($project->getId());
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->service->remove($project);
        }

        return $this->redirect($this->generateUrl('project'));
    }

    /**
     * Creates a form to delete a Project entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return FormInterface The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('project_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit',  SubmitType::class, array('label' => 'Delete'))
            ->getForm()
        ;
    }

    /**
     * @Route("/json", name="project_json", options={"expose"=true})
     * @param Request $request
     * @return JsonResponse
     */
    public function jsonAction(Request $request){
        $response = array();
        $repository = $this->getDoctrine()
            ->getRepository('AppBundle:Project');
        $qb = $repository->createQueryBuilder('p');
        
        if(array_key_exists('id', $request->query))
        {
            $projects = $qb->where(
                $qb->expr()->eq('p.id', ':id')
            )
            ->setParameter('id', $request->query->get('id'));
        }
        else
        {
            foreach ($request->query as $key => $value) {
                $projects = $qb->where(
                    $qb->expr()->eq('p.'. $key, $value)
                );
            }

             $projects = $qb->where(
                 $qb->expr()->eq('p.deleted', 0),
                 $qb->expr()->eq('p.enabled', 1)
             );
        }
        $projects = $projects
            ->getQuery()
            ->getResult();

        foreach($projects as $project){
            $response[] = array(
                'id' => $project->getId(),
                'text' => $project->getName(),
            );
        }

        return new JsonResponse($response);
    }
}
