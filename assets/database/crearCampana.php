<?php
session_start();
require 'config.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($_SESSION['userID'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validación de campos
    $camposRequeridos = ['tipoCampana', 'nombreCampana', 'descripcionCampana'];
    foreach ($camposRequeridos as $campo) {
        if (empty($_POST[$campo])) {
            echo "<script>alert('El campo $campo es obligatorio.'); window.history.back();</script>";
            exit;
        }
    }

    $conn->begin_transaction();
    try {
        $idUsuario = $_SESSION['userID'];
        $tipoCampana = htmlspecialchars($_POST['tipoCampana']);
        $nombreCampana = htmlspecialchars($_POST['nombreCampana']);
        $descripcionCampana = htmlspecialchars($_POST['descripcionCampana']);
        $cabeceraCorreo = isset($_POST['cabeceraCorreo']) ? htmlspecialchars($_POST['cabeceraCorreo']) : null;
        $asuntoCorreo = isset($_POST['asuntoCorreo']) ? htmlspecialchars($_POST['asuntoCorreo']) : null;
        $cuerpoCorreo = isset($_POST['cuerpoCorreo']) ? htmlspecialchars($_POST['cuerpoCorreo']) : null;
        $correosUnicos = isset($_POST['correosUnicos']) ? htmlspecialchars($_POST['correosUnicos']) : '';
        $archivoCorreos = $_FILES['archivoCorreos']['tmp_name'] ?? null;

        // Validación y manejo de archivos
        if ($archivoCorreos) {
            $tipoArchivoPermitido = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'];
            if (!in_array($_FILES['archivoCorreos']['type'], $tipoArchivoPermitido) || $_FILES['archivoCorreos']['size'] > 5000000) {
                throw new Exception('Tipo de archivo no permitido o el archivo es demasiado grande.');
            }
        }

        $idCampana = insertarCampana($conn, $idUsuario, $nombreCampana, $descripcionCampana, $tipoCampana);
        if (!$idCampana) {
            throw new Exception('Error al crear la campaña.');
        }

        procesarCorreosUnicos($conn, $idCampana, $correosUnicos);
        procesarArchivoCorreos($conn, $idCampana, $archivoCorreos, $asuntoCorreo, $cuerpoCorreo);

        $conn->commit();
        echo "<script>alert('Campaña creada con éxito.'); window.location.href='usuario.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('{$e->getMessage()}'); window.history.back();</script>";
    }
} else {
    echo "Método de solicitud no permitido.";
}

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'getPlantillaDetails' && isset($_GET['IDPlantilla'])) {
    $idPlantilla = $_GET['IDPlantilla'];
    $detallesPlantilla = obtenerDetallesPlantilla($conn, $idPlantilla);
    if ($detallesPlantilla) {
        echo json_encode($detallesPlantilla);
    } else {
        echo json_encode(['error' => 'No se encontraron detalles para la plantilla con ID ' . $idPlantilla]);
    }
    exit;
}

function insertarCampana($conn, $idUsuario, $nombreCampana, $descripcionCampana, $idPlantilla = null)
{
    $sql = "INSERT INTO Campañas (IDUsuario, Nombre, Descripción, IDPlantilla, FechaInicio) VALUES (?, ?, ?, ?, NOW())";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("issi", $idUsuario, $nombreCampana, $descripcionCampana, $idPlantilla);
        if ($stmt->execute()) {
            return $conn->insert_id; 
        }
        $stmt->close();
    }
    return false;
}


function procesarCorreosUnicos($conn, $idCampana, $correosUnicos)
{
    if (!empty($correosUnicos)) {
        $correosArray = explode(',', $correosUnicos);
        foreach ($correosArray as $correo) {
            $correo = trim($correo);
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                continue;
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

function procesarArchivoCorreos($conn, $idCampana, $archivoCorreos, $asuntoCorreo, $cuerpoCorreo)
{
    if ($archivoCorreos && file_exists($archivoCorreos)) {
        $correosYnombres = leerArchivoCorreos($archivoCorreos);
        foreach ($correosYnombres as $cn) {
            $correo = $cn['correo'];
            $nombreDestinatario = $cn['nombre'] ?? '';
            enviarCorreoPersonalizado($correo, $nombreDestinatario, $asuntoCorreo, $cuerpoCorreo);
        }
    }
}

function leerArchivoCorreos($archivo)
{
    $correosYnombres = [];
    $extension = pathinfo($archivo, PATHINFO_EXTENSION);
    try {
        $reader = ($extension === 'csv') ? new \PhpOffice\PhpSpreadsheet\Reader\Csv() : IOFactory::createReaderForFile($archivo);
        $spreadsheet = $reader->load($archivo);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        foreach ($sheetData as $row) {
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

function enviarCorreoPersonalizado($emailDestinatario, $nombreDestinatario, $asunto, $cuerpoBase)
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.ejemplo.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tu@correo.com';
        $mail->Password = 'tuContraseña';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('de@ejemplo.com', 'Nombre Remitente');
        $mail->addAddress($emailDestinatario, $nombreDestinatario);
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $saludo = $nombreDestinatario ? "Estimado/a $nombreDestinatario," : "Estimado/a usuario/a,";
        $mail->Body = $saludo . "<br>" . $cuerpoBase;
        $mail->AltBody = strip_tags($saludo) . "\n" . strip_tags($cuerpoBase);
        $mail->send();
    } catch (Exception $e) {
        error_log("No se pudo enviar el correo a $emailDestinatario: {$mail->ErrorInfo}");
    }
}

function obtenerDetallesPlantilla($conn, $idPlantilla)
{
    // Implementación de la función, como se describió anteriormente
    $sql = "SELECT Nombre, Asunto, Cuerpo, LogoURL FROM PlantillasCorreo WHERE IDPlantilla = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $idPlantilla);
        if ($stmt->execute()) {
            $resultado = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $resultado;
        }
    }
    return null;
}