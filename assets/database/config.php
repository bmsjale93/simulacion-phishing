<?php
require_once '/Applications/MAMP/htdocs/simulacion-phishing/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "phishing_simulator";

// Configuración de SMTP
$smtpHost = 'smtp-relay.brevo.com';
$smtpAuth = true;
$smtpUsername = 'alexraicesmunto@gmail.com';
$smtpPassword = 'DCwxbUcrq0Tm1Vzn';
$smtpSecure = PHPMailer::ENCRYPTION_STARTTLS;
$smtpPort = 587;
$smtpCharset = 'UTF-8';
$smtpFromEmail = 'alexraicesmunto@gmail.com';
$smtpFromName = ''; // Puedes dejar este campo vacío o asignarle un valor por defecto

// Crear conexión a la base de datos
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
