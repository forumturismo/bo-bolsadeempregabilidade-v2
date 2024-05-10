<?php

namespace App\Controller;

use App\Entity\WpUser;
use App\Form\WpUserType;
use App\Repository\WpUserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * @Route("/wp/user")
 */
class WpUserController extends AbstractController {

    /**
     * @Route("/", name="app_wp_user_index", methods={"GET|POST"})
     */
    public function index(\Doctrine\ORM\EntityManagerInterface $em, \Knp\Component\Pager\PaginatorInterface $paginator, Request $request) {

        $wpUserSearch = new \App\Entity\WpUserSearch();

        $queryNacionalidades = "SELECT distinct(pm.meta_value) as nacionalidade 
            FROM wp_posts p join wp_postmeta pm on p.id = pm.post_id 
                            where p.post_type = 'resume' and pm.meta_key = '_candidate_nacionalidade' order by nacionalidade";
        $stmtNacionalidades = $em->getConnection()->prepare($queryNacionalidades);
        $resultSetNacionalidades = $stmtNacionalidades->executeQuery();
        $nacionalidades = $resultSetNacionalidades->fetchAllAssociative();

        $new_nacionalidades["Todos"] = "";
        
        foreach ($nacionalidades as $key => $value) {
            $new_nacionalidades[$value['nacionalidade']] = $value['nacionalidade'];
        }



        $searchForm = $this->createForm('App\Form\WpUserSearchType', $wpUserSearch, ['method' => 'GET', 'nacionalidades' => $new_nacionalidades]);
        $searchForm->handleRequest($request);

        $query = "SELECT wp_users.id as id, wp_users.user_registered as user_registered, wp_users.user_login as user_login, 
                    candidate_names.name as name,
                    resumes.resumes_count as user_resumes_count, resumes_updated.resume_updated , nacionalidades.nacionalidade as nacionalidade
                FROM wp_users 
                JOIN (select t1.user_id,
                            (SELECT meta_value FROM wp_usermeta WHERE meta_key='first_name' AND user_id = t1.user_id) AS first_name,
                            (SELECT meta_value FROM wp_usermeta WHERE meta_key='last_name' AND user_id = t1.user_id) AS last_name
                            from wp_usermeta t1 group by t1.user_id) user_meta 
                        on wp_users.id = user_meta.user_id
                
                LEFT JOIN (SELECT count(p.id) as resumes_count, p.post_author FROM wp_posts p where post_type = 'resume' and post_status = 'publish' group by p.post_author) resumes 
                        on wp_users.id = resumes.post_author 
                
                LEFT JOIN (SELECT distinct(p.post_author), pm.meta_value as nacionalidade FROM wp_posts p join wp_postmeta pm on p.id = pm.post_id 
                            where p.post_type = 'resume' and post_status = 'publish' and pm.meta_key = '_candidate_nacionalidade') nacionalidades
                            on wp_users.id = nacionalidades.post_author
                            

                LEFT JOIN (SELECT distinct(p.post_author), pm.meta_value as name FROM wp_posts p join wp_postmeta pm on p.id = pm.post_id 
                            where p.post_type = 'resume' and post_status = 'publish' and pm.meta_key = '_candidate_name') candidate_names
                            on wp_users.id = candidate_names.post_author

                LEFT JOIN (SELECT max(p.post_modified) as resume_updated, p.post_author FROM wp_posts p where post_type = 'resume' and post_status = 'publish' group by p.post_author) resumes_updated                
                        on wp_users.id = resumes_updated.post_author 
                        
                WHERE 1=1

                ";

        if ($searchForm->isSubmitted() && $searchForm->isValid()) {

            $todos = $searchForm["todos"]->getData();
            $dataInicio = $searchForm["data_inicio"]->getData();
            $dataFim = $searchForm["data_fim"]->getData();
            $pais = $searchForm["pais"]->getData();

            if (!empty($todos)) {
                $query = $query . " AND (wp_users.id = '" . $todos . "' "
                        . "OR wp_users.user_login like '%" . $todos . "%' "
                        . "OR candidate_names.name like '%" . $todos . "%' )";
            }
            if (!empty($dataInicio)) {
                $query = $query . " AND wp_users.user_registered >= '" . $dataInicio->format("Y-m-d") . " 00:00'";
            }

            if (!empty($dataFim)) {
                $query = $query . " AND wp_users.user_registered < '" . $dataFim->format("Y-m-d") . " 23:59'";
            }

            if (!empty($pais)) {

                foreach ($pais as $key => $value) {

                    if ($key == 0):
                        $query = $query . " AND (";
                    else:
                        $query = $query . " OR";
                    endif;

                    $query = $query . " nacionalidade like '%" . $value . "%'";
                }
                $query = $query . " )";
            }
        }

        $query = $query . " ORDER BY resumes_updated.resume_updated desc";

        $stmt = $em->getConnection()->prepare($query);

        $resultSet = $stmt->executeQuery();

        $users = $resultSet->fetchAllAssociative();

        $this->exportToExcel($users);

        $pagination = $paginator->paginate(
                $users, /* query NOT result */
                $request->query->getInt('page', 1), /* page number */
                50 /* limit per page */
        );

        $pagination->setParam('section', 'supplier');


        // parameters to template
        return $this->render('wp_user/index.html.twig', ['search_form' => $searchForm->createView(), 'pagination' => $pagination]);

    }

    public function exportToExcel($users) {


        $spreadsheet = new Spreadsheet();
        $activeWorksheet = $spreadsheet->getActiveSheet();

        $activeWorksheet->setCellValue('A1', 'Id');
        $activeWorksheet->setCellValue('B1', 'Data Registo');
        $activeWorksheet->setCellValue('C1', 'User Login');
        $activeWorksheet->setCellValue('D1', 'Nome');
        $activeWorksheet->setCellValue('E1', 'Nº Curriculos');
        $activeWorksheet->setCellValue('F1', 'Ult. Curri.');
        $activeWorksheet->setCellValue('G1', 'Nacionalidade');

        $line = 2;
        foreach ($users as $key => $user) {

            $activeWorksheet->setCellValue('A' . $line, $user['id']);
            $activeWorksheet->setCellValue('B' . $line, $user['user_registered']);
            $activeWorksheet->setCellValue('C' . $line, $user['user_login']);
            $activeWorksheet->setCellValue('D' . $line, $user['name']);
            $activeWorksheet->setCellValue('E' . $line, $user['user_resumes_count']);
            $activeWorksheet->setCellValue('F' . $line, $user['resume_updated']);
            $activeWorksheet->setCellValue('G' . $line, $user['nacionalidade']);
            $line = $line + 1;
        }



        $writer = new Xlsx($spreadsheet);
        $writer->save('assets/bolsa_empregabilidade_users.xlsx');
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
