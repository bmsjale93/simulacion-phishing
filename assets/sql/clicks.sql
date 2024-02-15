CREATE TABLE Clicks (
    IDClick INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    IDEnvío INT(6) UNSIGNED,
    IDUsuario INT(6) UNSIGNED,
    -- El ID del empleado que clicó el enlace
    FechaHoraClick TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (IDEnvío) REFERENCES Envíos(IDEnvío),
    FOREIGN KEY (IDUsuario) REFERENCES Usuarios(ID)
);