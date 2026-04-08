<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/dashboardModel.php';

class DashboardService {

    private $dashboardModel;

    public function __construct($pdo) {
        $this->dashboardModel = new DashboardModel($pdo);
    }

    // ── getDashboard() ───────────────────────────────────────
    // GET /api/dashboard
    //
    // Paramètre reçu : id_patient → extrait du JWT par AuthMiddleware
    //
    // Appelle 5 méthodes du model et les regroupe en 1 seul JSON
    //
    // 
    //   Variables JSON retournées au FRONT                 
    //                                                      
    //   favorites_count             → "5 Favorite Doctors" 
    //   upcoming_appointments_count → "2 Upcoming Appts"   
    //   new_messages_count          → "1 New messages"     
    //   remaining_payment           → "3500 DA / Pay Now"  
    //                                                      
    //   suggestions[] (4 dentistes les mieux notés)        
    //     id_dentist                                       
    //     full_name   → "Name" dans le design              
    //     speciality  → "Speciality" dans le design        
    //     photo       → image de la carte                  
    //     avg_rating  → "Rating" dans le design            
    // 
    public function getDashboard(int $patientId): array {

        $favoritesCount   = $this->dashboardModel->getFavoritesCount($patientId);
        $upcomingCount    = $this->dashboardModel->getUpcomingCount($patientId);
        $unreadMessages   = $this->dashboardModel->getUnreadMessagesCount($patientId);
        $remainingPayment = $this->dashboardModel->getRemainingPayment($patientId);
        $suggestions      = $this->dashboardModel->getSuggestions(4);

        return [
            'code' => 200,
            'body' => [
                'favorites_count'             => $favoritesCount,
                'upcoming_appointments_count' => $upcomingCount,
                'new_messages_count'          => $unreadMessages,
                'remaining_payment'           => $remainingPayment,
                'suggestions'                 => $suggestions,
            ]
        ];
    }
}