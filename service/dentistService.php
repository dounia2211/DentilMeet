<?php

require_once __DIR__ . '/../models/dentistModel.php';

class dentistService {
    private $dentistModel;

    //how many dentist to show per page
    const PER_PAGE = 9;

    public function __construct($pdo) {
        $this->dentistModel = new dentistModel($pdo);
    }

    //getall called when dentist list page loads
    public function getAll($page = 1){ //if no page given we default to 1
       $page = max(1, (int) $page);
       $offset = ($page -1 )* self::PER_PAGE; 

       //get the dentists for this page
       $dentists=$this->dentistModel->getAll(self::PER_PAGE, $offset);

       //get total count for pagination info
       $total = $this->dentistModel->countAll();
       $total_pages= (int) ceil ($total / self::PER_PAGE);

       return [
          'code' => 200,
          'body' => [
             'dentists' => $dentists,
             'pagination' => [
                 'current_page' => $page,
                 'per_page' => self::PER_PAGE,
                 'total' => $total,
                 'total_pages' =>$total_pages,
                 'has_previous' => $page > 1,
                 'has_next' => $page < $total_pages,
                ]        
           ]
        ];
    }

    //search calles when patient types in the search bar
    public function search($term, $page=1){
        $term = trim($term ?? '');

        //empty search tell frontend to show all dentists instead
        if (empty($term)){
            return [
                'code' => 400,
                'body' => ['message' => 'Serach term is required.']
            ];
        }

        //too short would return too many irrelevent results
        if (strlen($term)<2){
            return [
                'code' => 400,
                'body' => ['message' => 'Search term must be at least 2 characters.']
            ];
        }

        $page = max(1, (int) $page);
        $offset = ($page -1) * self::PER_PAGE;

        $dentists = $this->dentistModel->search($term, self::PER_PAGE, $offset);
        $total =$this->dentistModel->countSearch($term);
        $total_pages = (int) ceil($total / self::PER_PAGE);

        return [
            'code' => 200,
            'body' => [
                'dentists' => $dentists,
                'search_term' => $term,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => self::PER_PAGE,
                    'total' =>$total,
                    'total_pages => $total_pages',
                    'has_previous' => $page > 1,
                    'has_next' => $page < $total_pages,
                ]
            ]
        ];
    }

    //filter called when patient clicks filters and picks a speciality
    public function filter($speciality, $page=1){
        $speciality = trim($speciality ?? '');
        if (empty($speciality)){
            return [
                'code' => 400,
                'body' => ['message' => 'Speciality is required for filtering.']
            ];
        }

        $page = max(1, (int) $page);
        $offset = ($page-1)* self::PER_PAGE;

        $dentists    = $this->dentistModel->filter($speciality, self::PER_PAGE, $offset);
        $total       = $this->dentistModel->countFilter($speciality);
        $total_pages = (int) ceil($total / self::PER_PAGE);

        return [
            'code' => 200,
            'body' => [
                'dentists'   => $dentists,
                'speciality' => $speciality,
                'pagination' => [
                    'current_page' => $page,
                    'per_page'     => self::PER_PAGE,
                    'total'        => $total,
                    'total_pages'  => $total_pages,
                    'has_previous' => $page > 1,
                    'has_next'     => $page < $total_pages,
                ]
            ]
        ];
    }

    //getOne called when patient click view profile button
    public function getOne($id_dentist) {
        if (!$id_dentist || !is_numeric($id_dentist)){
            return [
                'code' => 400,
                'body' => ['message' => 'Invalid dentist ID.']
            ];
        }

        //get the dentist details
        $dentist = $this->dentistModel->findById($id_dentist);

        if(!$dentist) {
            return [
                'code' => 404,
                'body' => ['message' => 'Dentist not found.']
            ];
        }

        //get their availability schedule
        $availability= $this->dentistModel->getAvailability($id_dentist);

        return [
            'code' => 200,
            'body' => [
                'dentist' => $dentist,
                'availability' =>$availability
            ]
        ];
    }

    //getsuggestions called when dashboard page loads
    public function getSuggestions() { //already make in dashboard page
        $dentists = $this->dentistModel->getSuggestions();
        return [
            'code' => 200,
            'body' =>['dentists' => $dentists]
        ];
    }

    //getspecialities called when patient open the filter dropdown
    public function getSpecialities() {
        $specialities = $this->dentistModel->getSpecialities();
        return [
            'code' => 200,
            'body' => ['specialities' =>$specialities]
        ];
    }
}