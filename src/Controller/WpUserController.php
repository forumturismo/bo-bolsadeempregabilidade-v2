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

    private $em;

    public function __construct(\Doctrine\ORM\EntityManagerInterface $em) {
        $this->em = $em;
    }

    /**
     * @Route("/dashboard", name="app_wp_user_dashboard", methods={"GET|POST"})
     */
    public function dashboard(\Doctrine\ORM\EntityManagerInterface $em, Request $request) {

        $wpDashboardSearch = new \App\Entity\WpUserSearch();

        $searchForm = $this->createForm('App\Form\WpDashboardSearchType', $wpDashboardSearch);
        $searchForm->handleRequest($request);

        $curriculosToday = 0;
        $curriculos7Days = 0;
        $curriculos30Days = 0;
        $curriculosByLocationFilter = [];

        $vagasToday = 0;
        $vagas7Days = 0;
        $vagas30Days = 0;
        $vagasByRegionFilter = [];

        $candidaturasToday = 0;
        $candidaturas7Days = 0;
        $candidaturas30Days = 0;
        $candidaturasByRegionFilter = [];

        $candidatosFilter = 0;
        $curriculosFilter = 0;
        $vagasFilter = 0;
        $candidaturasFilter = 0;

        $intervalInDays = 1;

        if ($searchForm->isSubmitted() && $searchForm->isValid()) {

            $dataInicio = $searchForm["data_inicio"]->getData();
            $dataFim = $searchForm["data_fim"]->getData();

            if ($dataInicio == NULL):
                $datetime = new \DateTime();
                $dataInicio = $datetime->modify("-6 year");
            endif;

            if ($dataFim == NULL):
                $datetime = new \DateTime();
                $dataFim = $datetime->modify("1 day");
            endif;

            $candidatosFilter = $this->countCandidatos($dataInicio, $dataFim);
            $curriculosFilter = $this->countCurriculos($dataInicio, $dataFim);
            $vagasFilter = $this->countVagas($dataInicio, $dataFim);
            $candidaturasFilter = $this->countCandidaturas($dataInicio, $dataFim);

            //$candidatosByLocationFilter = $this->countCandidatos($dataInicio, $dataFim);
            $curriculosByLocationFilter = $this->countCurriculosByLocation($dataInicio, $dataFim);
            $vagasByRegionFilter = $this->countVagasByRegion($dataInicio, $dataFim);
            $candidaturasByRegionFilter = $this->countCandidaturasByRegion($dataInicio, $dataFim);

            $interval = $dataInicio->diff($dataFim);
            $intervalInDays = $interval->days + 1;
        }

        // HOJE

        $today = new \DateTime();
        $datetime = new \DateTime();
        //dump('today = '.$today);

        $last7Days = clone $datetime->modify("-6 day");
        $last30Days = clone $datetime->modify("-22 day");

        $candidatosToday = $this->countCandidatos($today, $today);
        $candidatos7Days = $this->countCandidatos($last7Days, $today);
        $candidatos30Days = $this->countCandidatos($last30Days, $today);

        $curriculosToday = $this->countCurriculos($today, $today);
        $curriculos7Days = $this->countCurriculos($last7Days, $today);
        $curriculos30Days = $this->countCurriculos($last30Days, $today);

        $vagasToday = $this->countVagas($today, $today);
        $vagas7Days = $this->countVagas($last7Days, $today);
        $vagas30Days = $this->countVagas($last30Days, $today);

        $candidaturasToday = $this->countCandidaturas($today, $today);
        $candidaturas7Days = $this->countCandidaturas($last7Days, $today);
        $candidaturas30Days = $this->countCandidaturas($last30Days, $today);

        $results = ['candidatosToday' => $candidatosToday,
            'candidatos7Days' => $candidatos7Days,
            'candidatos30Days' => $candidatos30Days,
            'candidatosFilter' => $candidatosFilter,
            'curriculosToday' => $curriculosToday,
            'curriculos7Days' => $curriculos7Days,
            'curriculos30Days' => $curriculos30Days,
            'curriculosFilter' => $curriculosFilter,
            'curriculosByLocationFilter' => $curriculosByLocationFilter,
            'vagasToday' => $vagasToday,
            'vagas7Days' => $vagas7Days,
            'vagas30Days' => $vagas30Days,
            'vagasFilter' => $vagasFilter,
            'vagasByRegionFilter' => $vagasByRegionFilter,
            'candidaturasToday' => $candidaturasToday,
            'candidaturas7Days' => $candidaturas7Days,
            'candidaturas30Days' => $candidaturas30Days,
            'candidaturasFilter' => $candidaturasFilter,
            'candidaturasByRegionFilter' => $candidaturasByRegionFilter,
            'intervalInDays' => $intervalInDays
        ];

        // parameters to template
        return $this->render('wp_user/dashboard.html.twig', ['search_form' => $searchForm->createView(), 'results' => $results
        ]);
    }

    public function countCandidatos($dataInicio, $dataFim, $calculationMethod = 'count') {

        $query = "SELECT " . $calculationMethod . "(wp_users.id) as candidatos FROM wp_users join wp_usermeta on wp_users.id = wp_usermeta.user_id  "
                . "where 1=1 AND wp_usermeta.meta_key = 'wp_capabilities' and wp_usermeta.meta_value like '%candidate%' ";

        if (!empty($dataInicio)) {
            $query = $query . " AND wp_users.user_registered >= '" . $dataInicio->format("Y-m-d") . " 00:00'";
        }

        if (!empty($dataFim)) {
            $query = $query . " AND wp_users.user_registered < '" . $dataFim->format("Y-m-d") . " 23:59'";
        }


        if ($calculationMethod == "avg"):

        endif;

        $stmt = $this->em->getConnection()->prepare($query);
        $resultSet = $stmt->executeQuery();
        $candidatos = $resultSet->fetchAllAssociative();
        return $candidatos[0]['candidatos'];
    }

    public function countCurriculos($dataInicio, $dataFim) {

        $query = "SELECT count(p.id) as curriculos FROM wp_posts p where post_type = 'resume' and post_status = 'publish' ";

        if (!empty($dataInicio)) {
            $query = $query . " AND p.post_date >= '" . $dataInicio->format("Y-m-d") . " 00:00'";
        }

        if (!empty($dataFim)) {
            $query = $query . " AND p.post_date < '" . $dataFim->format("Y-m-d") . " 23:59'";
        }

        $stmt = $this->em->getConnection()->prepare($query);
        $resultSet = $stmt->executeQuery();
        $candidatos = $resultSet->fetchAllAssociative();
        return $candidatos[0]['curriculos'];
    }

    public function countCurriculosByLocation($dataInicio, $dataFim) {

        $query = "SELECT count(p.id) as curriculos, pm.meta_value as location FROM wp_posts p join wp_postmeta pm on p.id = pm.post_id "
                . "where post_type = 'resume' and post_status = 'publish' and pm.meta_key = '_candidate_location'";

        if (!empty($dataInicio)) {
            $query = $query . " AND p.post_date >= '" . $dataInicio->format("Y-m-d") . " 00:00'";
        }

        if (!empty($dataFim)) {
            $query = $query . " AND p.post_date < '" . $dataFim->format("Y-m-d") . " 23:59'";
        }

        $query = $query . " GROUP by location ORDER BY curriculos DESC ";

        $stmt = $this->em->getConnection()->prepare($query);
        $resultSet = $stmt->executeQuery();
        $curriculos = $resultSet->fetchAllAssociative();
        return $curriculos;
    }

    public function countVagas($dataInicio, $dataFim) {

        $query = "SELECT count(p.id) as vagas FROM wp_posts p where post_type = 'job_listing' ";

        if (!empty($dataInicio)) {
            $query = $query . " AND p.post_date >= '" . $dataInicio->format("Y-m-d") . " 00:00'";
        }

        if (!empty($dataFim)) {
            $query = $query . " AND p.post_date < '" . $dataFim->format("Y-m-d") . " 23:59'";
        }

        $stmt = $this->em->getConnection()->prepare($query);
        $resultSet = $stmt->executeQuery();
        $vagas = $resultSet->fetchAllAssociative();
        return $vagas[0]['vagas'];
    }

    public function countVagasByRegion($dataInicio, $dataFim) {

//        $query = "SELECT count(p.id) as vagas, pm.meta_value as location "
//                . "FROM wp_posts p join wp_postmeta pm on p.id = pm.post_id "
//                . "WHERE post_type = 'job_listing' and pm.meta_key = '_job_location'";
        
        
        $query = "SELECT  count(p.id) as vagas, wp_terms.name as region
                FROM wp_posts p  
                join wp_term_relationships on p.id = wp_term_relationships.object_id
                join wp_term_taxonomy on wp_term_relationships.term_taxonomy_id = wp_term_taxonomy.term_taxonomy_id 
                join wp_terms on wp_term_taxonomy.term_id = wp_terms.term_id
                WHERE post_type = 'job_listing' and post_status = 'publish' 
                and wp_term_taxonomy.taxonomy = 'job_listing_region' ";
        
        
        
        
        
        //adicionar post_status

        if (!empty($dataInicio)) {
            $query = $query . " AND p.post_date >= '" . $dataInicio->format("Y-m-d") . " 00:00'";
        }

        if (!empty($dataFim)) {
            $query = $query . " AND p.post_date < '" . $dataFim->format("Y-m-d") . " 23:59'";
        }

        $query = $query . " GROUP by region ORDER BY vagas DESC ";

        $stmt = $this->em->getConnection()->prepare($query);
        $resultSet = $stmt->executeQuery();
        $vagas = $resultSet->fetchAllAssociative();

        return $vagas;
    }

    public function countCandidaturas($dataInicio, $dataFim) {

        $query = "SELECT count(p.id) as candidaturas FROM wp_posts p where post_type = 'job_application' ";

        if (!empty($dataInicio)) {
            $query = $query . " AND p.post_date >= '" . $dataInicio->format("Y-m-d") . " 00:00'";
        }

        if (!empty($dataFim)) {
            $query = $query . " AND p.post_date < '" . $dataFim->format("Y-m-d") . " 23:59'";
        }

        $stmt = $this->em->getConnection()->prepare($query);
        $resultSet = $stmt->executeQuery();
        $candidaturas = $resultSet->fetchAllAssociative();
        return $candidaturas[0]['candidaturas'];
    }

    public function countCandidaturasByRegion($dataInicio, $dataFim) {

//        $query = "SELECT count(p.id) as candidaturas , pm.meta_value as location "
//                . "FROM wp_posts p join wp_postmeta pm on p.post_parent = pm.post_id "
//                . "where post_type = 'job_application' and pm.meta_key = '_job_location' ";

        
          $query = "SELECT count(p.id) as candidaturas, wp_terms.name as region
                FROM wp_posts p 
                join wp_term_relationships on p.post_parent = wp_term_relationships.object_id
                join wp_term_taxonomy on wp_term_relationships.term_taxonomy_id = wp_term_taxonomy.term_taxonomy_id 
                join wp_terms on wp_term_taxonomy.term_id = wp_terms.term_id
                WHERE post_type = 'job_application'  
                and wp_term_taxonomy.taxonomy = 'job_listing_region' ";
        
        
        
        if (!empty($dataInicio)) {
            $query = $query . " AND p.post_date >= '" . $dataInicio->format("Y-m-d") . " 00:00'";
        }

        if (!empty($dataFim)) {
            $query = $query . " AND p.post_date < '" . $dataFim->format("Y-m-d") . " 23:59'";
        }

        $query = $query . " GROUP by region ORDER BY candidaturas DESC ";

        $stmt = $this->em->getConnection()->prepare($query);
        $resultSet = $stmt->executeQuery();
        $candidaturas = $resultSet->fetchAllAssociative();
        return $candidaturas;
    }

    /**
     * @Route("/index", name="app_wp_user_index", methods={"GET|POST"})
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
