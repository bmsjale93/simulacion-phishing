
/* 
Registra cada intento de phishing enviado,
incluyendo a quién se envió y si ha sido abierto.
*/
CREATE TABLE correos_enviados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    email_destinatario VARCHAR(255) NOT NULL,
    fecha_envio DATETIME NOT NULL,
    abierto BOOLEAN DEFAULT 0,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
);