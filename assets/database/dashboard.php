<?php
session_start();
include 'config.php'; // Incluir configuración de base de datos

// Verificar si el usuario está logueado y es un cliente
if (!isset($_SESSION['loggedin']) || $_SESSION['rol'] !== 'cliente') {
    echo "Acceso denegado. Debes iniciar sesión como cliente para acceder a esta página.";
    exit;
}

$id_usuario = $_SESSION['userid']; // ID del usuario cliente

// Obtener los correos enviados por el cliente
$stmt = $conn->prepare("SELECT * FROM correos_enviados WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

echo "<h2>Panel de Control del Cliente</h2>";
echo "<h3>Correos Enviados</h3>";
if ($result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID Correo</th><th>Email Destinatario</th><th>Fecha Envío</th><th>Abierto</th></tr>";
    // Mostrar los resultados en una tabla
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>" . $row["id"] . "</td><td>" . $row["email_destinatario"] . "</td><td>" . $row["fecha_envio"] . "</td><td>" . ($row["abierto"] ? 'Sí' : 'No') . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "No has enviado ningún correo aún.";
}

$stmt->close();
$conn->close();
