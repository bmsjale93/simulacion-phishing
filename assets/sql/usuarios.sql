/*
Esta tabla almacena información sobre los usuarios que pueden ser administradores (clientes) 
que crean y envían campañas de phishing,
o empleados que son los destinatarios de los correos de phishing.
 */
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    correo_electronico VARCHAR(255) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    rol ENUM('cliente', 'empleado') NOT NULL
);