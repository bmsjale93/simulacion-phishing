<?php
include 'config.php'; // Incluir configuración de base de datos

// Supongamos que cada enlace enviado en el correo tiene un parámetro único, por ejemplo, ?id_correo=123
$id_correo = $_GET['id_correo'] ?? 0; // Obtener el ID del correo enviado desde la URL

if ($id_correo) {
    // Registrar la apertura del correo
    $stmt = $conn->prepare("UPDATE correos_enviados SET abierto = 1 WHERE id = ?");
    $stmt->bind_param("i", $id_correo);
    $stmt->execute();
    $stmt->close();

    // Aquí podrías redireccionar al usuario a una página educativa o mostrar el contenido directamente
    echo "Este correo es parte de un simulacro de phishing. Aprende más sobre cómo evitar ataques de phishing.";
} else {
    echo "Error: No se proporcionó un ID de correo válido.";
}

$conn->close();
