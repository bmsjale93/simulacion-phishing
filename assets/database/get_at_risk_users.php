<?php
// Asumiendo que $idUltimaCampana ya está definido
$usuariosEnRiesgoSql = "SELECT Usuarios.Email, Clicks.FechaHoraClick
FROM Usuarios
JOIN Clicks ON Usuarios.ID = Clicks.IDUsuario
JOIN Envíos ON Clicks.IDEnvío = Envíos.IDEnvío
WHERE Envíos.IDCampaña = ?";
$stmt = $conn->prepare($usuariosEnRiesgoSql);
if ($stmt === false) {
    die("Error preparando la consulta: " . $conn->error);
}
$stmt->bind_param("i", $idUltimaCampana);
$stmt->execute();
$usuariosEnRiesgo = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
