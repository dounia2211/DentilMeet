<?php

require_once __DIR__ .'/../service/dentistService.php';

class dentistController{
    private $dentistService;
    public function __construct($pdo){
        $this->dentistService = new dentistService($pdo);
    }

    //getall
    public function getAll() {
        $page   = $_GET['page'] ?? 1;
        $result = $this->dentistService->getAll($page);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    //serach
    public function search() {
        $term   = $_GET['q']    ?? '';
        $page   = $_GET['page'] ?? 1;
        $result = $this->dentistService->search($term, $page);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    //filter
    public function filter() {
        $speciality = $_GET['speciality'] ?? '';
        $page       = $_GET['page']       ?? 1;
        $result     = $this->dentistService->filter($speciality, $page);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    //getone
    public function getOne($id_dentist) {
        $result = $this->dentistService->getOne($id_dentist);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    //getsuggestion
    public function getSuggestions() { //maken in dashbord
        $result = $this->dentistService->getSuggestions();
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    //getspecialities
     public function getSpecialities() {
        $result = $this->dentistService->getSpecialities();
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }
}