<?php
session_start();
require 'config.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

// Redirecciona al usuario si no está autenticado
if (!isset($_SESSION['userID'])) {
    header("Location: index.php");
    exit;
}

// Procesa el formulario cuando se envía con el método POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recoge y sanea los datos del formulario
    $idUsuario = $_SESSION['userID'];
    $tipoCampana = htmlspecialchars($_POST['tipoCampana']);
    $nombreCampana = htmlspecialchars($_POST['nombreCampana']);
    $descripcionCampana = htmlspecialchars($_POST['descripcionCampana']);
    $cabeceraCorreo = isset($_POST['cabeceraCorreo']) ? htmlspecialchars($_POST['cabeceraCorreo']) : null;
    $asuntoCorreo = isset($_POST['asuntoCorreo']) ? htmlspecialchars($_POST['asuntoCorreo']) : null;
    $cuerpoCorreo = isset($_POST['cuerpoCorreo']) ? htmlspecialchars($_POST['cuerpoCorreo']) : null;
    $correosUnicos = isset($_POST['correosUnicos']) ? htmlspecialchars($_POST['correosUnicos']) : '';
    $archivoCorreos = $_FILES['archivoCorreos']['tmp_name'] ?? null;

    // Inserta la nueva campaña en la base de datos y obtiene el ID de la campaña recién creada
    $idCampana = insertarCampana($conn, $idUsuario, $nombreCampana, $descripcionCampana);

    if ($idCampana) {
        procesarCorreosUnicos($conn, $idCampana, $correosUnicos);
        procesarArchivoCorreos($conn, $idCampana, $archivoCorreos, $asuntoCorreo, $cuerpoCorreo);

        echo "<script>alert('Campaña creada con éxito.'); window.location.href='usuario.php';</script>";
    } else {
        echo "<script>alert('Error al crear la campaña.'); window.history.back();</script>";
    }
} else {
    echo "Método de solicitud no permitido.";
}

// Funciones auxiliares para la lógica de negocio

/**
 * Inserta una nueva campaña en la base de datos y devuelve el ID de la campaña.
 */
function insertarCampana($conn, $idUsuario, $nombreCampana, $descripcionCampana)
{
    $sql = "INSERT INTO Campañas (IDUsuario, Nombre, Descripción, FechaInicio) VALUES (?, ?, ?, NOW())";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("iss", $idUsuario, $nombreCampana, $descripcionCampana);
        if ($stmt->execute()) {
            return $conn->insert_id; // Devuelve el ID de la campaña recién creada
        }
        $stmt->close();
    }
    return false; // Devuelve falso si falla la inserción
}

/**
 * Procesa y guarda en la base de datos los correos electrónicos únicos proporcionados.
 */
function procesarCorreosUnicos($conn, $idCampana, $correosUnicos)
{
    if (!empty($correosUnicos)) {
        $correosArray = explode(',', $correosUnicos);
        foreach ($correosArray as $correo) {
            $correo = trim($correo);
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                continue; // Ignora correos inválidos
            }
            $sqlEnvio = "INSERT INTO DetallesEnvíos (IDEnvío, EmailDestinatario, Estado) VALUES (?, ?, 'pendiente')";
            if ($stmtEnvio = $conn->prepare($sqlEnvio)) {
                $stmtEnvio->bind_param("is", $idCampana, $correo);
                $stmtEnvio->execute();
                $stmtEnvio->close();
            }
        }
    }
}

/**
 * Procesa el archivo de correos, guardando los detalles en la base de datos y enviando correos personalizados.
 */
function procesarArchivoCorreos($conn, $idCampana, $archivoCorreos, $asuntoCorreo, $cuerpoCorreo)
{
    if ($archivoCorreos && file_exists($archivoCorreos)) {
        $correosYnombres = leerArchivoCorreos($archivoCorreos);

        foreach ($correosYnombres as $cn) {
            $correo = $cn['correo'];
            $nombreDestinatario = $cn['nombre'] ?? '';
            // Inserta el correo en la base de datos y envía el correo personalizado
            enviarCorreoPersonalizado($correo, $nombreDestinatario, $asuntoCorreo, $cuerpoCorreo);
        }
    }
}

/**
 * Lee el archivo de correos (CSV o Excel) y devuelve un array con los correos y nombres.
 */
function leerArchivoCorreos($archivo)
{
    $correosYnombres = [];
    $extension = pathinfo($archivo, PATHINFO_EXTENSION);
    try {
        // Elige el lector adecuado basado en la extensión del archivo
        $reader = ($extension === 'csv') ? new \PhpOffice\PhpSpreadsheet\Reader\Csv() : IOFactory::createReaderForFile($archivo);
        $spreadsheet = $reader->load($archivo);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        foreach ($sheetData as $row) {
            // Asume que la columna A contiene correos y la columna B nombres
            $correo = $row['A'] ?? null;
            $nombre = $row['B'] ?? '';
            if ($correo && filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $correosYnombres[] = ['correo' => $correo, 'nombre' => $nombre];
            }
        }
    } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
        die('Error al leer el archivo: ' . $e->getMessage());
    }

    return $correosYnombres;
}

/**
 * Envía un correo personalizado al destinatario utilizando PHPMailer.
 */
function enviarCorreoPersonalizado($emailDestinatario, $nombreDestinatario, $asunto, $cuerpoBase)
{
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.ejemplo.com'; // Servidor SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'tu@correo.com'; // SMTP username
        $mail->Password = 'tuContraseña'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Configura los remitentes y destinatarios
        $mail->setFrom('de@ejemplo.com', 'Nombre Remitente');
        $mail->addAddress($emailDestinatario, $nombreDestinatario);

        // Configura el contenido del correo
        $mail->isHTML(true); // Establece el formato de correo como HTML
        $mail->Subject = $asunto;
        $saludo = $nombreDestinatario ? "Estimado/a $nombreDestinatario," : "Estimado/a usuario/a,";
        $mail->Body = $saludo . "<br>" . $cuerpoBase;
        $mail->AltBody = strip_tags($saludo) . "\n" . strip_tags($cuerpoBase);

        $mail->send();
    } catch (Exception $e) {
        // Log o manejo de error en el envío
        error_log("No se pudo enviar el correo a $emailDestinatario: {$mail->ErrorInfo}");
    }
}
