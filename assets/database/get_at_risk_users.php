<?php
// Asumiendo que $userID ya está definido y contiene el ID del usuario en sesión
$usuariosEnRiesgoSql = "SELECT DestinatariosCampaña.EmailDestinatario AS Email, Clicks.FechaHoraClick
FROM DestinatariosCampaña
JOIN Clicks ON DestinatariosCampaña.IDDestinatario = Clicks.IDDestinatario
JOIN Envíos ON Clicks.IDEnvío = Envíos.IDEnvío
JOIN Campañas ON Envíos.IDCampaña = Campañas.IDCampaña
WHERE Campañas.IDUsuario = ?";
$stmt = $conn->prepare($usuariosEnRiesgoSql);
if ($stmt === false) {
    die("Error preparando la consulta: " . $conn->error);
}
$stmt->bind_param("i", $userID);
$stmt->execute();
$usuariosEnRiesgo = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
