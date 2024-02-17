<?php
// Asumiendo que la conexión a la base de datos ($conn) y el ID del usuario ($userID) ya están definidos.

// Identificar la última campaña del usuario
$ultimaCampanaSql = "SELECT IDCampaña FROM Campañas WHERE IDUsuario = ? ORDER BY FechaInicio DESC LIMIT 1";
$stmt = $conn->prepare($ultimaCampanaSql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$resultado = $stmt->get_result();
$ultimaCampana = $resultado->fetch_assoc();

if ($ultimaCampana) {
    $idUltimaCampana = $ultimaCampana['IDCampaña'];

    // Obtener detalles de envíos para la última campaña
    $detallesEnviosSql = "SELECT DetallesEnvíos.EmailDestinatario, DetallesEnvíos.Estado, MAX(Clicks.FechaHoraClick) AS UltimoClick 
                          FROM DetallesEnvíos 
                          LEFT JOIN Clicks ON DetallesEnvíos.IDEnvío = Clicks.IDEnvío 
                          WHERE DetallesEnvíos.IDEnvío IN (SELECT IDEnvío FROM Envíos WHERE IDCampaña = ?) 
                          GROUP BY DetallesEnvíos.EmailDestinatario, DetallesEnvíos.Estado";
    $stmt = $conn->prepare($detallesEnviosSql);
    if ($stmt === false) {
        die("Error preparando la consulta: " . $conn->error);
    }
    $stmt->bind_param("i", $idUltimaCampana);
    $stmt->execute();
    $resultadoDetallesEnvios = $stmt->get_result();

    $detallesEnvios = [];
    while ($detalle = $resultadoDetallesEnvios->fetch_assoc()) {
        // Determinar si hubo un clic
        $detalle['ClickEnlace'] = $detalle['UltimoClick'] ? 'Sí' : 'No';
        $detallesEnvios[] = $detalle;
    }
    $stmt->close();
} else {
    // Manejar el caso de que el usuario no tenga campañas
    $detallesEnvios = [];
}