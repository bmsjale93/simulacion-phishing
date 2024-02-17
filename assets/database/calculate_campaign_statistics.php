<?php
// Cálculo de estadísticas básicas de la campaña
$estadisticasSql = "SELECT 
    COUNT(*) AS TotalEnvios, 
    SUM(CASE WHEN Estado = 'entregado' THEN 1 ELSE 0 END) AS Entregados,
    (SELECT COUNT(DISTINCT Clicks.IDClick) FROM Clicks JOIN Envíos ON Clicks.IDEnvío = Envíos.IDEnvío WHERE Envíos.IDCampaña = ?) AS Clicks 
FROM DetallesEnvíos 
JOIN Envíos ON DetallesEnvíos.IDEnvío = Envíos.IDEnvío
WHERE Envíos.IDCampaña = ?";
$stmt = $conn->prepare($estadisticasSql);
if ($stmt === false) {
    die("Error preparando la consulta: " . $conn->error);
}
$stmt->bind_param("ii", $idUltimaCampana, $idUltimaCampana);
$stmt->execute();
$estadisticas = $stmt->get_result()->fetch_assoc();
$stmt->close();

$porcentajeClicks = isset($estadisticas['TotalEnvios']) && $estadisticas['TotalEnvios'] > 0 ? ($estadisticas['Clicks'] / $estadisticas['TotalEnvios']) * 100 : 0;
