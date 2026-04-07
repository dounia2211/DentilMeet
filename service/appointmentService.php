<?php

require_once __DIR__ . '/../models/appointmentModel.php';

class appointmentService{
  private $appointmentModel;

  public function __construct($pdo) {
    $this->appointmentModel = new appointmentModel ($pdo);
  }

  //get all appointments for logged in patient // upcoming_count needs for dashboard page
  public function getAll($id_patient) {
    $appointments =$this->appointmentModel->getAllByPatient($id_patient);
    return [
      'code' => 200,
      'body' => [
        'appointments' => $appointments,
        'total' => count($appointments),
        'upcoming_count'  => $this->appointmentModel->countUpcoming($id_patient)
      ]
    ];
  }

  //get one appointment
  public function getOne($id_patient, $id_appointment) {
    $appointment = $this->appointmentModel->findById($id_appointment, $id_patient);
    if (!$appointment) {
      return [
        'code' => 404,
        'body' => ['messsage' => 'Appointment not found']
      ];
    }
    return [
      'code' => 200,
      'body' => ['appointment' => $appointment]
    ];
  }

  //book a new appointment
  public function create($data, $id_patient){
    //step1 extract and clean inputs
    $id_dentist  = $data['id_dentist'] ?? null;
    $date        = $data['appointment_date'] ?? null;
    $time        = $data['appointment_time'] ?? null;
    $reason      = trim($data['reason'] ?? '');
    $service     = trim($data['service_type'] ?? '');
    

    //step2 validate
    $errors = [];
 
    if (empty($id_dentist))
     $errors[] = 'Dentist is required.';

    if (empty($date))
     $errors[] = 'Appointment date is required.';
    elseif ($date < date('Y-m-d'))
     $errors[] = 'Appointment date cannot be in the past.';

    if (empty($time))
      $errors[] = 'Appointment time is required.';

    //service type must be one of the 3
    $validServices = ['Check-up', 'Cleaning', 'Emergency'];
    if (!in_array($service, $validServices))
      $errors[] = 'Visit type must be Check-up, Cleaning or Emergency.';

    if (!empty($errors)) {
      return ['code' => 400, 'body' => ['errors' => $errors]];
    }

    //step 3 check slot not taken
    $bookedSlots = $this->appointmentModel->getBookedSlots($id_dentist, $date);
    if (in_array($time, $bookedSlots)) {
      return [
        'code' => 409, //  slot already taken
        'body' => ['message' => 'This time slot was just taken. Please choose another.']
      ];
    }

    //step4 convert time from 9:00 AM to 9:00:00
    $timeForDB = date('H:i:s', strtotime($time));

    //step5 insert into DB
    $id_appointment=$this->appointmentModel->create(
      $id_patient,
      1,
      $date,
      $timeForDB,
      $reason,
      $service
    );

    if (!$id_appointment){
      return [
        'code' => 500,
        'body' => ['message' => 'Booking failed. Try again']
      ];
    }

    //step6 return success
    return [
      'code' => 201,
      'body' => [
        'message' => 'Appointment booked successfuly.',
        'id_appointment' => (int) $id_appointment,
        'date'=> $date,
        'time' => $time,//return original format
        'service_type' => $service
      ]
    ];
  }

  //cancel appointment
  public function cancel ($id_patient, $id_appointment){
    $updated = $this->appointmentModel->cancel($id_appointment, $id_patient);

    if(!$updated) {
      return [
        'code' => 404,
        'body' => [ 'message' => 'Appointment not found or already cancelled.']
      ];
    }
    return [
      'code' => 200,
      'body' => ['message' => 'Appointment cancelled successfully.']
    ];
  }

  //confirmation
  public function getConfirmation($id_patient, $id_appointment) {
    $appointment = $this->appointmentModel->findById($id_appointment, $id_patient);

    //if not found or belong to a diff patient
    if (!$appointment) {
      return ['code' => 404, 
      'body' => ['message' => 'Appointment not found.']];
    }
    
    //format the time back to 9:00AM for display
    $timeFormatted = date('g:i A', strtotime($appointment['appointment_time']));

    return [
      'code' => 200,
      'body' => [
        'id_appointment'   => (int) $appointment['id_appointment'],
        'appointment_date' => $appointment['appointment_date'],
        'appointment_time' => $timeFormatted,
        'service_type'     => $appointment['service_type'],
        'reason'           => $appointment['reason'],
        'status'           => $appointment['appointment_status'],
        'dentist_name'     => $appointment['dentist_name'] ,
        'speciality'       => $appointment['dentist_speciality'],
      ]
    ];
  }

  //get booked days 
  public function getBookedDays ($id_dentist, $year, $month){
    if (!$id_dentist || !$year || !$month) {
      return [
        'code' => 400,
        'body' => ['message' => 'id_dentist, year and month are required.']
      ];
    }
    $days=$this->appointmentModel->getBookedDays($id_dentist, $year, $month);
    return[
      'code' => 200,
      'body' => [
        'booked_days' => $days,
        'id_dentist' => (int)$id_dentist,
        'year' => (int) $year,
        'month' => (int) $month,
      ]
    ];
  }

  //get availible slot
  public function getAvailableSlots($id_dentist, $date) {
    if (!$id_dentist || !$date) {
      return [
        'code' => 400,
        'body' => ['message' => 'id_dentist and date are required.']
      ];
    }

    // Validate date format — must be YYYY-MM-DD
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
      return [
        'code' => 400,
        'body' => ['message' => 'Date format must be YYYY-MM-DD.']
      ];
    }

    // Cannot book in the past
    if ($date < date('Y-m-d')) {
      return [
        'code' => 400,
        'body' => ['message' => 'Cannot book appointments in the past.']
      ];
    }

    //step1 all possible time slots
    $allSlots = [
      '9:00 AM','9:30 AM','10:00 AM','10:30 AM',
      '11:00 AM','11:30 AM','12:00 PM','12:30 PM',
      '1:00 PM','1:30 PM','2:00 PM','2:30 PM'
    ];

    //step2 get already booked slots from DB
    $bookedSlots = $this->appointmentModel->getBookedSlots($id_dentist, $date);
    $availableSlots = array_values(array_diff($allSlots, $bookedSlots));

    return [
      'code' => 200,
      'body' => [
        'available_slots' => $availableSlots,
        'date' => $date,
        'id_dentist' => (int) $id_dentist
      ]
    ];
  } 
}

