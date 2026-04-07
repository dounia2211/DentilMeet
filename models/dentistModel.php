<?php

class dentistModel{

  private $pdo;
  public function __construct($pdo){
    $this->pdo= $pdo;
  }

  //method1 getall used by the main dentist list page
  public function getAll($limit, $offset) {
    $stmt = $this->pdo->prepare("
      SELECT
        id_dentist,
        full_name,
        speciality,
        phone,
        year_of_experience,
        description,
        photo,
        id_clinic
      FROM dentist
      WHERE verification_status = 'approuve'
      ORDER BY full_name ASC
      LIMIT ? OFFSET ?
    ");

    // bindValue() lets us specify the data type explicitly
    // PDO::PARAM_INT tells MySQL these are integers
    $stmt->bindValue(1, (int) $limit,  \PDO::PARAM_INT);
    $stmt->bindValue(2, (int) $offset, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
  }

  //method2 countall used by pagination to knpw total pages
  public function countAll (){
    $stmt= $this->pdo->prepare("
      SELECT COUNT(*) as total 
      FROM dentist
      WHERE verification_status = 'approuve'
    ");

    $stmt->execute();
    $row= $stmt->fetch();
    return $row ? (int) $row['total'] : 0;
  }

  //method3 shearch used by search bar 
  public function search ($term, $limit, $offset){
    $like= '%' .$term . '%';

    $stmt=$this->pdo->prepare("
      SELECT d.id_dentist, d.full_name, d.speciality, d.phone,
      d.year_of_experience, d.description, d.photo, d.id_clinic,
      c.city AS clinic_city,
      c.name AS clinic_name,
      c.address AS clinic_address
      FROM dentist d 
      LEFT JOIN clinic c ON d.id_clinic = c.id_clinic
      WHERE d.verification_status ='approuve'
      AND (
        d.full_name LIKE ?
        OR d.speciality LIKE ?
        OR c.city LIKE ?
        OR c.name LIKE ?
      )
      ORDER BY d.full_name ASC 
      LIMIT ? OFFSET ?
    ");

    $stmt->bindValue(1, $like, \PDO::PARAM_STR);
    $stmt->bindValue(2, $like, \PDO::PARAM_STR);
    $stmt->bindValue(3, $like, \PDO::PARAM_STR);
    $stmt->bindValue(4, $like, \PDO::PARAM_STR);
    $stmt->bindValue(5, (int) $limit,  \PDO::PARAM_INT);
    $stmt->bindValue(6, (int) $offset, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
  }
 

  //method4 countsearch used by pagination for search result
  public function countSearch ($term) {
    $like = '%' .$term . '%';
    $stmt =$this->pdo->prepare("
      SELECT count(*) as total
      FROM dentist d
      LEFT JOIN clinic c ON d.id_clinic = c.id_clinic
      WHERE d.verification_status ='approuve'
      AND (
        d.full_name LIKE ?
        OR d.speciality LIKE ?
        OR c.city LIKE ?
        OR c.name LIKE ?
      )
    ");

    $stmt->bindValue(1, $like, \PDO::PARAM_STR);
    $stmt->bindValue(2, $like, \PDO::PARAM_STR);
    $stmt->bindValue(3, $like, \PDO::PARAM_STR);
    $stmt->bindValue(4, $like, \PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch();
    return $row ? (int) $row['total'] : 0;
  }

  //method5 filter used by the filters button on the dentist list page
  public function filter($speciality, $limit, $offset){
    $stmt= $this->pdo->prepare("
      SELECT d.id_dentist, d.full_name, d.speciality, d.phone,
      d.year_of_experience, d.description, d.photo, d.id_clinic,
      c.city AS clinic_city,
      c.name AS clinic_name
      FROM dentist d 
      LEFT JOIN clinic c ON d.id_clinic = c.id_clinic
      WHERE d.verification_status = 'approuve' 
      AND d.speciality = ?
      ORDER BY d.full_name ASC
      LIMIT ? OFFSET ?
    ");

    $stmt->bindValue(1, $speciality, \PDO::PARAM_STR);
    $stmt->bindValue(2, (int) $limit, \PDO::PARAM_INT);
    $stmt->bindValue(3, (int) $offset, \PDO::PARAM_INT).
    $stmt-> execute();
    return $stmt->fetchAll();
  }

  //method6 countfilter used by pagination for filter results
  public function countFilter ($speciality){
    $stmt= $this->pdo->prepare("
      SELECT COUNT(*) as total
      FROM dentist
      WHERE verification_status = 'approuve'
      AND speciality = ?
    ");
    $stmt->execute([$speciality]);
    $row= $stmt->fetch();
    return $row ? (int) $row['total'] : 0;
  }

  //method7 findbyid used by view profile button
  public function findById($id_dentist){
    $stmt = $this->pdo->prepare("
      SELECT d.id_dentist, d.full_name, d.speciality, d.phone,
      d.year_of_experience, d.description, d.photo,d.id_clinic,
      c.name AS clinic_name,
      c.address AS clinic_address,
      c.city AS clinic_city,
      c.phone AS clinic_phone
      FROM dentist d
      LEFT JOIN clinic c ON d.id_clinic = c.id_clinic
      WHERE d.id_dentist = ?
      AND d.verification_status = 'approuve'
      LIMIT 1
    ");
    $stmt->execute([$id_dentist]);
    return $stmt-> fetch(); //return array or false
  }

  //method8 getavailability used by profile panel working hours and availability section
  public function getAvailability($id_dentist){
    $stmt = $this->pdo->prepare("
      SELECT id_availability, day_of_week, start_time, end_time, specific_date, 
      is_blocked 
      FROM availability 
      WHERE id_dentist = ?
      AND is_blocked = 0
      ORDER BY FIELD(day_of_week,
        'lundi', 'mardi', 'mercredi', 'jeudi',
        'vendredi', 'samedi','dimanche'
      )  
   ");

   $stmt-> execute([$id_dentist]);
   return $stmt-> fetchAll();
  } 

    //method9 getsuggestions used by dashboard suggestions section
    public function getSuggestions(){
      $stmt =$this->pdo->prepare("
        SELECT
        id_dentist,
        full_name,
        speciality,
        photo
        FROM dentist 
        WHERE verification_status= 'approuve'
        ORDER BY RAND()
        LIMIT 4 
      ");

      $stmt->execute();
      return $stmt->fetchAll();
    }
    
    //method10 getspecialities used by filters dropdown to show available speciality options
    public function getSpecialities() {
      $stmt = $this->pdo->prepare("
        SELECT DISTINCT speciality
        FROM dentist 
        WHERE verification_status ='approuve'
        AND speciality IS NOT NULL 
        ORDER BY speciality ASC 
      ");

      $stmt->execute();
      return array_column($stmt->fetchAll(), 'speciality');
    }

}