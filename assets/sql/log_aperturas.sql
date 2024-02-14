
/*
Captura información detallada cada vez que un correo de phishing es abierto por el destinatario.
*/
CREATE TABLE log_aperturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_correo_enviado INT NOT NULL,
    fecha_apertura DATETIME NOT NULL,
    FOREIGN KEY (id_correo_enviado) REFERENCES correos_enviados(id)
);