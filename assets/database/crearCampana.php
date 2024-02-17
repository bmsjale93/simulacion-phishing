<?php
session_start();
require 'config.php';
require '/Applications/MAMP/htdocs/simulacion-phishing/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($_SESSION['userID'])) {
    header("Location: index.php");
    exit;
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $camposRequeridos = ['tipoCampana', 'nombreCampana', 'descripcionCampana'];
    $camposFaltantes = [];
    foreach ($camposRequeridos as $campo) {
        if (empty($_POST[$campo])) {
            $camposFaltantes[] = $campo;
        }
    }
    if (!empty($camposFaltantes)) {
        echo "<script>alert('Los campos " . implode(", ", $camposFaltantes) . " son obligatorios.'); window.history.back();</script>";
        exit;
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

        if ($archivoCorreos) {
            $tipoArchivoPermitido = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'];
            if (!in_array($_FILES['archivoCorreos']['type'], $tipoArchivoPermitido) || $_FILES['archivoCorreos']['size'] > 5000000) {
                throw new Exception('Tipo de archivo no permitido o el archivo es demasiado grande.');
            }
        }

        $idCampana = insertarCampana($conn, $idUsuario, $tipoCampana, $nombreCampana, $descripcionCampana);
        if (!$idCampana) {
            throw new Exception('Error al crear la campaña.');
        }

        procesarCorreosUnicos($conn, $idCampana, $correosUnicos);
        procesarArchivoCorreos($conn, $idCampana, $archivoCorreos, $asuntoCorreo, $cuerpoCorreo, $nombreCampana);

        $conn->commit();
        echo "<script>alert('Campaña creada con éxito.'); window.location.href='usuario.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('{$e->getMessage()}'); window.history.back();</script>";
    }
} else {
    echo "Método de solicitud no permitido.";
    exit;
}

function insertarCampana($conn, $idUsuario, $tipoCampana, $nombreCampana, $descripcionCampana, $idPlantilla = null)
{
    $sql = "INSERT INTO Campañas (IDUsuario, TipoCampana, Nombre, Descripcion, IDPlantilla, FechaInicio) VALUES (?, ?, ?, ?, ?, NOW())";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("isssi", $idUsuario, $tipoCampana, $nombreCampana, $descripcionCampana, $idPlantilla);
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
            $sqlEnvio = "INSERT INTO DetallesEnvíos (IDCampana, EmailDestinatario, Estado) VALUES (?, ?, 'pendiente')";
            if ($stmtEnvio = $conn->prepare($sqlEnvio)) {
                $stmtEnvio->bind_param("is", $idCampana, $correo);
                $stmtEnvio->execute();
                $stmtEnvio->close();
            }
        }
    }
}

function procesarArchivoCorreos($conn, $idCampana, $archivoCorreos, $asuntoCorreo, $cuerpoCorreo, $nombreCampana)
{
    if ($archivoCorreos && file_exists($archivoCorreos)) {
        $correosYnombres = leerArchivoCorreos($archivoCorreos);
        foreach ($correosYnombres as $cn) {
            $correo = $cn['correo'];
            $nombreDestinatario = $cn['nombre'] ?? '';
            enviarCorreoPersonalizado($correo, $nombreDestinatario, $asuntoCorreo, $cuerpoCorreo, $nombreCampana);
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

function enviarCorreoPersonalizado($emailDestinatario, $nombreDestinatario, $asunto, $cuerpoBase, $nombreCampana)
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp-relay.brevo.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'bmsjale@gmail.com';
        $mail->Password = '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';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('tu_correo@dominio.com', $nombreCampana);
        $mail->addAddress($emailDestinatario, $nombreDestinatario);

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: auto; background: #ffffff; padding: 20px; }
        .header { text-align: center; padding-bottom: 20px; }
        .content { font-size: 16px; color: #444444; }
        .footer { font-size: 12px; text-align: center; padding-top: 20px; border-top: 1px solid #ddd; }
        .button { display: inline-block; padding: 10px 20px; background-color: #007bff; color: #ffffff; text-decoration: none; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <img src='cid:logo' alt='Logo' style='max-width: 100px;'>
        </div>
        <div class='content'>
            <h1>{$asunto}</h1>
            <p>Estimado/a {$nombreDestinatario},</p>
            <p>{$cuerpoBase}</p>
            <a href='https://www.ejemplo-link.com' class='button'>Haz clic aquí</a>
        </div>
        <div class='footer'>
            <p>Este correo es parte de un proyecto educativo. Si tienes alguna duda o consulta, por favor contacta con nosotros.</p>
        </div>
    </div>
</body>
</html>";
        $mail->AltBody = 'Este es el cuerpo en texto plano para clientes de correo no HTML';

        $mail->send();
    } catch (Exception $e) {
        error_log("No se pudo enviar el correo a $emailDestinatario: {$mail->ErrorInfo}");
    }
}

function obtenerDetallesPlantilla($conn, $idPlantilla)
{
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