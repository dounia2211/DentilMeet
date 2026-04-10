<?php

require_once __DIR__ . '/../models/clinicModel.php';

class clinicService {
    private $clinicModel;

    public function __construct($pdo){
        $this->clinicModel = new clinicModel($pdo);
    }

    //getone called when the patient click view the localisation button
     public function getOne($id_clinic) {
 
        if (!$id_clinic || !is_numeric($id_clinic)) {
            return [
                'code' => 400,
                'body' => ['message' => 'Invalid clinic ID.']
            ];
        }
 
        $clinic = $this->clinicModel->findById($id_clinic);
 
        if (!$clinic) {
            return [
                'code' => 404,
                'body' => ['message' => 'Clinic not found.']
            ];
        }
 
        return [
            'code' => 200,
            'body' => ['clinic' => $clinic]
        ];
    }

    //getdentists called when showing dentists at this clinic
     public function getDentists($id_clinic) {
 
        if (!$id_clinic || !is_numeric($id_clinic)) {
            return [
                'code' => 400,
                'body' => ['message' => 'Invalid clinic ID.']
            ];
        }
 
        $dentists = $this->clinicModel->getDentists($id_clinic);
 
        return [
            'code' => 200,
            'body' => [
                'dentists' => $dentists,
                'total'    => count($dentists)
            ]
        ];
    }
}  