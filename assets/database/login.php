<?php
session_start(); // Iniciar sesión
include 'config.php'; // Incluir archivo de configuración

// Verificar si el formulario ha sido enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo_electronico = $_POST['correo_electronico'];
    $contrasena = $_POST['contrasena'];

    // Buscar usuario por correo electrónico
    $stmt = $conn->prepare("SELECT id, nombre, contrasena FROM usuarios WHERE correo_electronico = ?");
    $stmt->bind_param("s", $correo_electronico);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Verificar contraseña
        if (password_verify($contrasena, $user['contrasena'])) {
            // Crear variables de sesión
            $_SESSION['loggedin'] = true;
            $_SESSION['userid'] = $user['id'];
            $_SESSION['username'] = $user['nombre'];
            echo "Inicio de sesión exitoso";
        } else {
            echo "Contraseña incorrecta";
        }
    } else {
        echo "Usuario no encontrado";
    }

    $stmt->close();
    $conn->close();
}
?>
<!-- Formulario de Inicio de Sesión HTML aquí -->