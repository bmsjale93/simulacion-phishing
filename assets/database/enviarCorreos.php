<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'simulacion-phishing/PHPMailer/src/Exception.php';
require 'simulacion-phishing/PHPMailer/src/PHPMailer.php';
require 'simulacion-phishing/PHPMailer/src/SMTP.php';

// Inicializa PHPMailer
$mail = new PHPMailer(true);

try {
    // Configuración del servidor SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.ejemplo.com'; // El servidor SMTP a utilizar
    $mail->SMTPAuth = true;
    $mail->Username = 'tu@correo.com'; // SMTP username
    $mail->Password = 'tuPassword'; // SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Habilita encriptación TLS; `PHPMailer::ENCRYPTION_SMTPS` también aceptado
    $mail->Port = 587; // Puerto TCP al cual conectarse

    // Remitentes y destinatarios
    $mail->setFrom('de@ejemplo.com', 'Simulador de Phishing');
    $mail->addAddress('destinatario1@example.com', 'Nombre del Destinatario');

    // Contenido
    $mail->isHTML(true); // Establece el formato de email a HTML
    $mail->Subject = 'Asunto del correo';
    $mail->Body    = 'Este es el contenido del correo <b>en HTML!</b>';
    $mail->AltBody = 'Este es el cuerpo en texto plano para clientes de correo no HTML';

    $mail->send();
    echo 'Mensaje enviado';
} catch (Exception $e) {
    echo "No se pudo enviar el mensaje. Error de Mailer: {$mail->ErrorInfo}";
}
