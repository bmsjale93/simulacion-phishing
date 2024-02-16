<?php
session_start();
include 'assets/database/config.php';

if (!isset($_SESSION['userID'])) {
  header("Location: index.php");
  exit;
}

$userID = $_SESSION['userID'];

// Obtener la información del usuario
$sql = "SELECT * FROM Usuarios WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$userInfo = $result->fetch_assoc();

if (!$userInfo) {
  echo "No se pudo cargar la información del usuario.";
  exit;
}

// Consulta para obtener las campañas del usuario
$campañasSql = "SELECT * FROM Campañas WHERE IDUsuario = ?";
$stmt = $conn->prepare($campañasSql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$campañasResult = $stmt->get_result();

$campañas = [];
while ($campaña = $campañasResult->fetch_assoc()) {
  $campañas[] = $campaña;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Administración - Simulador de Phishing</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link href="assets/css/user_style.css" rel="stylesheet">
</head>

<body id="userProfilePage">
  <header id="userHeader">
    <!-- Navegación -->
  </header>
  <main id="userMain" class="py-4">
    <div class="container">
      <div class="row">
        <div class="col-md-4">
          <!-- Card de Información Personal -->
          <div class="card">
            <div class="card-header">Información Personal</div>
            <div class="card-body">
              <p>Nombre: <?php echo htmlspecialchars($userInfo['Nombre']); ?></p>
              <p>Email: <?php echo htmlspecialchars($userInfo['Email']); ?></p>
              <p>Dirección: <?php echo htmlspecialchars($userInfo['Direccion']); ?></p>
              <p>Ciudad: <?php echo htmlspecialchars($userInfo['Ciudad']); ?></p>
              <p>País: <?php echo htmlspecialchars($userInfo['Pais']); ?></p>
              <p>Código Postal: <?php echo htmlspecialchars($userInfo['CodigoPostal']); ?></p>
              <p>Teléfono: <?php echo htmlspecialchars($userInfo['Telefono']); ?></p>
              <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#editarInfoModal">Editar Información</button>
            </div>
          </div>
        </div>
        <div class="col-md-8">
          <!-- Card de Campañas de Phishing -->
          <div class="card mb-4">
            <div class="card-header">Mis Campañas de Phishing</div>
            <div class="card-body">
              <button type="button" class="btn btn-primary crear-campana-trigger" data-toggle="modal" data-target="#crearCampanaModal">Crear Nueva Campaña</button>
              <!-- Listado de Campañas -->
              <div class="list-group">
                <?php foreach ($campañas as $campaña) { ?>
                  <a href="#" class="list-group-item list-group-item-action">
                    <h5 class="mb-1"><?php echo htmlspecialchars($campaña['Nombre']); ?></h5>
                    <p class="mb-1"><?php echo htmlspecialchars($campaña['Descripción']); ?></p>
                    <small>Inicio: <?php echo htmlspecialchars($campaña['FechaInicio']); ?> - Fin: <?php echo htmlspecialchars($campaña['FechaFin']); ?></small>
                  </a>
                <?php } ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <footer class="py-4 bg-dark text-white-50">
    <div class="container text-center">
      <small>Portal de Búsqueda de Trabajo © 2024 | Desarrollado por Alejandro Delgado & Álzaro Alvarez |</small>
    </div>
  </footer>

  <div id="crear-campana-modal-container">
    <?php include 'crearCampanaModal.php';
    $conn->close(); ?>
  </div>

  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>

  <script src="assets/js/modal.js"></script>
  <script src="assets/js/login_register.js"></script>
  <script src="assets/js/delete_account.js"></script>
  <script src="assets/js/crearCampana.js"></script>
  <script src="assets/js/listarCampanas.js"></script>


</body>

</html>