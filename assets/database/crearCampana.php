<?php
session_start();
require_once 'config.php';
require_once '/Applications/MAMP/htdocs/simulacion-phishing/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit;
}

// Redirige según el método de solicitud HTTP.
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        handleGetRequest($conn);
        break;
    case 'POST':
        handlePostRequest($conn);
        break;
    default:
        echo json_encode(['error' => 'Método de solicitud no soportado.']);
        exit;
}

function handleGetRequest($conn)
{
    if (isset($_GET['action']) && $_GET['action'] === 'getPlantillaDetails' && isset($_GET['IDPlantilla'])) {
        $idPlantilla = $_GET['IDPlantilla'];
        $detallesPlantilla = obtenerDetallesPlantilla($conn, $idPlantilla);
        echo $detallesPlantilla ? json_encode($detallesPlantilla) : json_encode(['error' => 'No se encontraron detalles para la plantilla con ID ' . $idPlantilla]);
    } else {
        echo json_encode(['error' => 'Acción GET no válida o parámetros faltantes.']);
    }
    exit;
}

function handlePostRequest($conn)
{
    $camposRequeridos = ['nombreCampana', 'descripcionCampana', 'tipoPlantilla', 'correosUnicos'];
    foreach ($camposRequeridos as $campo) {
        if (empty($_POST[$campo])) {
            echo json_encode(['error' => "El campo $campo es obligatorio"]);
            exit;
        }
    }

    $idUsuario = $_SESSION['userID'];
    $nombreCampana = htmlspecialchars($_POST['nombreCampana']);
    $descripcionCampana = htmlspecialchars($_POST['descripcionCampana']);
    $tipoPlantilla = htmlspecialchars($_POST['tipoPlantilla']);
    $correosUnicos = htmlspecialchars($_POST['correosUnicos']);

    if ($_POST['tipoPlantilla'] === 'predeterminada') {
        $idPlantilla = isset($_POST['IDPlantilla']) ? $_POST['IDPlantilla'] : null;
        $idPlantillaPersonalizada = null; // Asegúrate de que este valor sea nulo si no se usa
    } elseif ($_POST['tipoPlantilla'] === 'personalizada') {
        $idPlantillaPersonalizada = isset($_POST['IDPlantillaPersonalizada']) ? $_POST['IDPlantillaPersonalizada'] : null;
        $idPlantilla = null; // Asegúrate de que este valor sea nulo si no se usa
    } else {
        echo json_encode(['error' => "El tipo de plantilla seleccionado no es válido"]);
        exit;
    }

    $conn->begin_transaction();
    try {
        $idCampana = insertarCampana($conn, $idUsuario, $nombreCampana, $descripcionCampana, $idPlantilla, $idPlantillaPersonalizada);
        $idEnvio = insertarEnvio($conn, $idCampana, 'único');
        procesarCorreosUnicos($conn, $idCampana, $correosUnicos, $nombreCampana, $idEnvio);

        $conn->commit();
        echo json_encode(['success' => 'Campaña creada con éxito', 'idCampana' => $idCampana]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

function insertarCampana($conn, $idUsuario, $nombreCampana, $descripcionCampana, $idPlantilla, $idPlantillaPersonalizada)
{
    $sql = "INSERT INTO Campañas (IDUsuario, Nombre, Descripción, IDPlantilla, IDPlantillaPersonalizada, FechaInicio) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error al preparar la inserción de la campaña: ' . $conn->error);
    }

    $stmt->bind_param("issii", $idUsuario, $nombreCampana, $descripcionCampana, $idPlantilla, $idPlantillaPersonalizada);
    if (!$stmt->execute()) {
        throw new Exception('Error al insertar la campaña: ' . $conn->error);
    }
    $idCampana = $conn->insert_id;
    $stmt->close();
    return $idCampana;
}

function procesarCorreosUnicos($conn, $idCampana, $correosUnicos, $nombreCampana, $idEnvio)
{
    $correosArray = explode(',', $correosUnicos);
    foreach ($correosArray as $correo) {
        $correo = trim($correo);
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            continue; // Omitir correos inválidos
        }

        $envioExitoso = enviarCorreo($correo, "Asunto de prueba", "Cuerpo del correo", $nombreCampana);
        $estadoEnvio = $envioExitoso ? 'entregado' : 'fallido';

        insertarDetalleEnvio($conn, $idCampana, $correo, $estadoEnvio, $idEnvio);
    }
}


function obtenerDetallesPlantilla($conn, $idPlantilla)
{
    $sql = "SELECT Nombre, Asunto, Cuerpo, LogoURL FROM PlantillasCorreo WHERE IDPlantilla = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $idPlantilla);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }
    $resultado = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $resultado ?: null;
}

function enviarCorreo($emailDestinatario, $asunto, $cuerpo, $nombreCampana)
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp-relay.brevo.com'; // Especifica tu servidor SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'bmsjale@gmail.com'; // SMTP username
        $mail->Password = 'gMAVXYwhSH8Lv0j4'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('bmsjale@gmail.com', $nombreCampana);
        $mail->addAddress($emailDestinatario);

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = $cuerpo;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar correo a $emailDestinatario: " . $mail->ErrorInfo);
        return false;
    }
}

function insertarDestinatario($conn, $idCampana, $correo)
{
    $sql = "INSERT INTO DestinatariosCampaña (IDCampaña, EmailDestinatario) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error al preparar la inserción del destinatario: ' . $conn->error);
    }

    $stmt->bind_param("is", $idCampana, $correo);
    if (!$stmt->execute()) {
        throw new Exception('Error al insertar destinatario: ' . $conn->error);
    }
    $idDestinatario = $conn->insert_id;
    $stmt->close();
    return $idDestinatario;
}

function insertarDetalleEnvio($conn, $idCampana, $correo, $estadoEnvio, $idEnvio)
{
    $sql = "INSERT INTO DetallesEnvíos (IDEnvío, EmailDestinatario, Estado) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error al preparar la inserción del detalle de envío: ' . $conn->error);
    }

    $stmt->bind_param("iss", $idEnvio, $correo, $estadoEnvio);
    if (!$stmt->execute()) {
        throw new Exception('Error al insertar detalle de envío: ' . $conn->error);
    }
    $stmt->close();
}

function obtenerUltimoIDEnvio($conn, $idCampana)
{
    $sql = "SELECT IDEnvío FROM Envíos WHERE IDCampaña = ? ORDER BY FechaEnvío DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error al preparar la consulta para obtener el último IDEnvío: ' . $conn->error);
    }

    $stmt->bind_param("i", $idCampana);
    if (!$stmt->execute()) {
        throw new Exception('Error al obtener el último IDEnvío: ' . $conn->error);
    }

    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return $row['IDEnvío'];
    } else {
        $stmt->close();
        throw new Exception('No se encontró un IDEnvío para la campaña con ID ' . $idCampana);
    }
}

function insertarEnvio($conn, $idCampana, $tipoEnvio = 'único')
{
    $sql = "INSERT INTO Envíos (IDCampaña, FechaEnvío, TipoEnvío) VALUES (?, NOW(), ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error al preparar la inserción de envío: ' . $conn->error);
    }

    $stmt->bind_param("is", $idCampana, $tipoEnvio);
    if (!$stmt->execute()) {
        throw new Exception('Error al insertar envío: ' . $conn->error);
    }
    $idEnvio = $conn->insert_id;
    $stmt->close();
    return $idEnvio;
}
