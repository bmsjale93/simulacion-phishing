CREATE TABLE Usuarios (
    ID INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(50) NOT NULL,
    Email VARCHAR(50) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    Direccion VARCHAR(100),
    Ciudad VARCHAR(50),
    Pais VARCHAR(50),
    CodigoPostal VARCHAR(20),
    Telefono VARCHAR(20),
    TipoUsuario ENUM('cliente', 'empleado') NOT NULL,
    FechaRegistro TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE PlantillasCorreo (
    IDPlantilla INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Asunto VARCHAR(255) NOT NULL,
    Cuerpo TEXT NOT NULL,
    LogoURL VARCHAR(255);
);

CREATE TABLE Campañas (
    IDCampaña INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    IDUsuario INT(6) UNSIGNED,
    IDPlantilla INT(6) UNSIGNED NULL,
    Nombre VARCHAR(100) NOT NULL,
    Descripción TEXT,
    FechaInicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FechaFin TIMESTAMP NULL,
    FOREIGN KEY (IDUsuario) REFERENCES Usuarios(ID),
    FOREIGN KEY (IDPlantilla) REFERENCES PlantillasCorreo(IDPlantilla)
);

CREATE TABLE Envíos (
    IDEnvío INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    IDCampaña INT(6) UNSIGNED,
    FechaEnvío TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    TipoEnvío ENUM('único', 'masivo') NOT NULL,
    FOREIGN KEY (IDCampaña) REFERENCES Campañas(IDCampaña)
);

CREATE TABLE Clicks (
    IDClick INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    IDEnvío INT(6) UNSIGNED,
    IDUsuario INT(6) UNSIGNED,
    -- El ID del empleado que clicó el enlace
    FechaHoraClick TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (IDEnvío) REFERENCES Envíos(IDEnvío),
    FOREIGN KEY (IDUsuario) REFERENCES Usuarios(ID)
);

CREATE TABLE DetallesEnvíos (
    IDDetalle INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    IDEnvío INT(6) UNSIGNED,
    EmailDestinatario VARCHAR(50) NOT NULL,
    Estado ENUM('entregado', 'fallido') NOT NULL,
    FOREIGN KEY (IDEnvío) REFERENCES Envíos(IDEnvío)
);