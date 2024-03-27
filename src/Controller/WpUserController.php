<?php

namespace App\Controller;

use App\Entity\WpUser;
use App\Form\WpUserType;
use App\Repository\WpUserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/wp/user")
 */
class WpUserController extends AbstractController {

    /**
     * @Route("/", name="app_wp_user_index", methods={"GET"})
     */
    public function index(\Doctrine\ORM\EntityManagerInterface $em, \Knp\Component\Pager\PaginatorInterface $paginator, Request $request) {

        $wpUserSearch = new \App\Entity\WpUserSearch();
        $searchForm = $this->createForm('App\Form\WpUserSearchType', $wpUserSearch, ['method' => 'GET']);
        $searchForm->handleRequest($request);

        $query = "SELECT wp_users.id as id, wp_users.user_registered as user_registered, wp_users.user_login as user_login, 
                    user_meta.first_name as first_name, user_meta.last_name as last_name,
                    resumes.resumes_count as user_resumes_count, resumes_updated.resume_updated , nacionalidades.nacionalidade as nacionalidade
                FROM wp_users 
                JOIN (select t1.user_id,
                            (SELECT meta_value FROM wp_usermeta WHERE meta_key='first_name' AND user_id = t1.user_id) AS first_name,
                            (SELECT meta_value FROM wp_usermeta WHERE meta_key='last_name' AND user_id = t1.user_id) AS last_name
                            from wp_usermeta t1 group by t1.user_id) user_meta 
                        on wp_users.id = user_meta.user_id
                
                LEFT JOIN (SELECT count(p.id) as resumes_count, p.post_author FROM wp_posts p where post_type = 'resume' group by p.post_author) resumes 
                        on wp_users.id = resumes.post_author 
                
                LEFT JOIN (SELECT distinct(p.post_author), pm.meta_value as nacionalidade FROM wp_posts p join wp_postmeta pm on p.id = pm.post_id 
                            where p.post_type = 'resume' and pm.meta_key = '_candidate_nacionalidade') nacionalidades
                            on wp_users.id = nacionalidades.post_author

                LEFT JOIN (SELECT max(p.post_modified) as resume_updated, p.post_author FROM wp_posts p where post_type = 'resume' group by p.post_author) resumes_updated                
                        on wp_users.id = resumes_updated.post_author ";

        if ($searchForm->isSubmitted() && $searchForm->isValid()) {

            //$todos = $searchForm["todos"]->getData();
            $dataInicio = $searchForm["data_inicio"]->getData();
            $dataFim = $searchForm["data_fim"]->getData();
            $pais = $searchForm["pais"]->getData();

            if (!empty($dataInicio)) {
                $query = $query . " AND wp_users.user_registered >= " . $dataInicio;
            }

            if (!empty($dataFim)) {
                $query = $query . " AND wp_users.user_registered <= " . $dataFim;
            }
            
            if (!empty($pais)) {
                $query = $query . " AND nacionalidade like '%" . $pais."%'";
            }
            
            
        }



        $query = $query . " ORDER BY resumes_updated.resume_updated desc LIMIT 50 OFFSET 0 ";

        $stmt = $em->getConnection()->prepare($query);

        $resultSet = $stmt->executeQuery();

        $users = $resultSet->fetchAllAssociative();

//dump($users);
        // parameters to template
//        return $this->render('wp_user/index.html.twig', [   'search_form' => $searchForm->createView(), 'pagination' => $pagination]);
        return $this->render('wp_user/index.html.twig', ['search_form' => $searchForm->createView(), 'users' => $users]);
//
//        return new Response(
//                '<html><body>Hello</body></html>'
//        );
    }

    /**
     * @Route("/new", name="app_wp_user_new", methods={"GET", "POST"})
     */
    public function new(Request $request, WpUserRepository $wpUserRepository): Response {
        $wpUser = new WpUser();
        $form = $this->createForm(WpUserType::class, $wpUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $wpUserRepository->add($wpUser, true);

            return $this->redirectToRoute('app_wp_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('wp_user/new.html.twig', [
                    'wp_user' => $wpUser,
                    'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_wp_user_show", methods={"GET"})
     */
    public function show(WpUser $wpUser): Response {
        return $this->render('wp_user/show.html.twig', [
                    'wp_user' => $wpUser,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_wp_user_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, WpUser $wpUser, WpUserRepository $wpUserRepository): Response {
        $form = $this->createForm(WpUserType::class, $wpUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $wpUserRepository->add($wpUser, true);

            return $this->redirectToRoute('app_wp_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('wp_user/edit.html.twig', [
                    'wp_user' => $wpUser,
                    'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_wp_user_delete", methods={"POST"})
     */
    public function delete(Request $request, WpUser $wpUser, WpUserRepository $wpUserRepository): Response {
        if ($this->isCsrfTokenValid('delete' . $wpUser->getId(), $request->request->get('_token'))) {
            $wpUserRepository->remove($wpUser, true);
        }

        return $this->redirectToRoute('app_wp_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
