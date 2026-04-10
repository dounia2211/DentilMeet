<?php 

class clinicModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo =$pdo;
    }

    //findbyid used by the view localisation button in dentist profile panel
    public function findById($id_clinic) {
        $stmt=$this->pdo->prepare("
          SELECT 
          id_clinic,
          name,
          city,
          phone
          FROM clinic
          WHERE id_clinic = ?
          LIMIT 1
        ");

        $stmt->execute([$id_clinic]);
        return $stmt->fetch();
    }

    //getdentists shwoing which dentist work at this clinic
    public function getDentists($id_clinic){
        $stmt= $this->pdo->prepare("
          SELECT
           id_dentist,
           full_name,
           speciality,
           photo,
           phone
           FROM dentist
           WHERE id_clinic = ?
           AND verification_status = 'approuve'
           ORDER BY full_name ASC
        ");

        $stmt->execute([$id_clinic]);
        return $stmt->fetchAll();
    }
}