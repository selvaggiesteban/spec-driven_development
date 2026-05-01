<?php

/**
 * Procesamiento de formulario de contacto con verificación ReCaptcha.
 * Lógica en inglés, comentarios en español.
 */

// Cargar variables de entorno si existe una librería o usar getenv
$mail_from = $_POST["email"] ?? '';
$name = $_POST["name"] ?? '';
$tel = $_POST["tel"] ?? '';
$subject = "Web request through form";

// Configuración recuperada de variables de entorno o valores por defecto
$to = getenv('CONTACT_EMAIL') ?: "hello@selvaggiesteban.com.ar";
$recaptcha_secret = getenv('RECAPTCHA_SECRET') ?: "#";

$headers = "From: " . $to;
$message = "Email: " . $mail_from . "\n\n" . "Name: " . $name . "\n\n" . "Phone number: " . $tel . "\n\n" . "Web request through form.";

// Verificación de Google ReCaptcha
$data = array(
    'secret' => $recaptcha_secret,
    'response' => $_POST['g-recaptcha-response'] ?? ''
);

$verify = curl_init();
curl_setopt($verify, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
curl_setopt($verify, CURLOPT_POST, true);
curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($verify, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);

$response_raw = curl_exec($verify);
$response_data = json_decode($response_raw, true);

if (isset($response_data['success']) && $response_data['success'] === true) {
    // Enviar correo si la verificación es exitosa
    mail($to, $subject, $message, $headers);
    // Redirección a página de agradecimiento
    header("Location: thanks.html");
} else {
    // Alerta en caso de fallo en el captcha
    header("Refresh:0; url=index.html");
    $alert_message = "Please validate the captcha field before submitting!";
    echo "<script type='text/javascript'>alert('$alert_message');</script>";
}