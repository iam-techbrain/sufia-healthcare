<?php
/**
 * Contact / Treatment Enquiry API Endpoint
 * Handles POST requests and saves into SQLite contact_inquiries table
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/admin/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['result' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Get JSON or POST body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    $data = $_POST;
}

$name       = trim($data['name'] ?? '');
$phone      = trim($data['phone'] ?? '');
$email      = trim($data['email'] ?? '');
$city       = trim($data['city'] ?? '');
$department = trim($data['department'] ?? $data['dept'] ?? 'General Consultation');
$message    = trim($data['query'] ?? $data['message'] ?? '');

if (empty($name) || empty($phone) || empty($message)) {
    echo json_encode(['result' => 'error', 'message' => 'Please provide name, phone and medical query.']);
    exit;
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("INSERT INTO contact_inquiries (name, phone, email, department, message, status, created_at) VALUES (?, ?, ?, ?, ?, 'New', CURRENT_TIMESTAMP)");
    $stmt->execute([$name, $phone, $email, $department, $message]);
    $lead_id = $pdo->lastInsertId();

    // ---WEBHOOK NOTIFICATION (Fires on every new lead) ---
    // Webhook URL daal diya hai
    $webhook_url = 'https://hook.eu1.make.com/ag20afy6pjozjywslpjgwbmnwr1r7o7x'; 

    if (!empty($webhook_url)) {
        $payload = [
            "lead_id"      => $lead_id,
            "website_name" => "Sufia Healthcare",
            "type"         => "lead",
            "name"         => $name,
            "phone"        => $phone,
            "email"        => $email,
            "city"         => $city,
            "service"      => $department,
            "message"      => $message,
            "status"       => "New",
            "date"         => date('Y-m-d H:i:s')
        ];

        $ch = curl_init($webhook_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4); // 4 seconds timeout so user response is instant
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        @curl_exec($ch);
        curl_close($ch);
    }
    // ----------------------------------------------------------------

    echo json_encode(['result' => 'success', 'message' => 'Enquiry submitted successfully.']);
} catch (Exception $e) {
    echo json_encode(['result' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}