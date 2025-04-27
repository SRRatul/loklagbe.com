<?php
require_once 'config.php'; // This already includes sanitize_input()

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $message = sanitize_input($_POST['message']);
    $recaptchaResponse = $_POST['g-recaptcha-response'];

    // Google reCAPTCHA secret key
    $secretKey = "6Le6-wgrAAAAAOx6G9852vU0IUoCmPEGaZ_R8Ofk";

    // Verify reCAPTCHA
    $url = "https://www.google.com/recaptcha/api/siteverify";
    $data = [
        'secret' => $secretKey,
        'response' => $recaptchaResponse
    ];

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($data)
        ]
    ];
    $context = stream_context_create($options);
    $verifyResponse = file_get_contents($url, false, $context);
    $captchaSuccess = json_decode($verifyResponse);

    if (!$captchaSuccess->success) {
        $error = "Please complete the reCAPTCHA verification.";
    } elseif (empty($name) || empty($email) || empty($phone) || empty($message)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Insert data into MySQL database
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, phone, message, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $name, $email, $phone, $message);

        if ($stmt->execute()) {
            $success = "Thank you for your message. We will get back to you soon!";
        } else {
            $error = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>