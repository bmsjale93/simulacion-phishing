<?php
session_start(); // Asegurarse de que hay una sesión iniciada
include 'config.php'; // Incluir configuración de base de datos

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Variables ejemplo (estas deberían venir del formulario y de la sesión del usuario)
$id_usuario = $_SESSION['userid']; // ID del usuario (cliente) obtenido de la sesión
$email_destinatario = 'recipient@example.com'; // Email del destinatario (esto vendría del formulario)

$mail = new PHPMailer(true);

try {
    // Configuración del servidor de correo
    $mail->isSMTP();
    $mail->Host       = 'smtp.example.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'your_email@example.com';
    $mail->Password   = 'your_password';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Remitentes y destinatarios
    $mail->setFrom('from@example.com', 'Simulador Phishing');
    $mail->addAddress($email_destinatario); // Añadir un destinatario

    // Contenido del correo
    $mail->isHTML(true); // Establecer formato de email a HTML
    $mail->Subject = 'Asunto del Correo de Phishing';
    $mail->Body    = 'Este es el contenido del correo de phishing. <b>¡Cuidado!</b>';
    $mail->AltBody = 'Este es el cuerpo en texto plano para clientes de correo no HTML';

    $mail->send();
    echo 'Mensaje enviado';

    // Registrar el envío en la base de datos
    $stmt = $conn->prepare("INSERT INTO correos_enviados (id_usuario, email_destinatario, fecha_envio, abierto) VALUES (?, ?, NOW(), 0)");
    $stmt->bind_param("is", $id_usuario, $email_destinatario);

    if ($stmt->execute()) {
        echo "Registro de correo enviado guardado exitosamente.";
    } else {
        echo "Error al guardar el registro: " . $stmt->error;
    }

    $stmt->close();
} catch (Exception $e) {
    echo "No se pudo enviar el mensaje. Error de Mailer: {$mail->ErrorInfo}";
}

$conn->close();
