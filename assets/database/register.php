<?php
include 'config.php'; // Incluir archivo de configuración

// Verificar si el formulario ha sido enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $correo_electronico = $_POST['correo_electronico'];
    $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT); // Encriptar contraseña
    $rol = $_POST['rol'];

    // Preparar el SQL y vincular los parámetros
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, correo_electronico, contrasena, rol) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nombre, $correo_electronico, $contrasena, $rol);

    // Ejecutar
    if ($stmt->execute()) {
        echo "Usuario registrado exitosamente";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
<!-- Formulario de Registro HTML aquí -->