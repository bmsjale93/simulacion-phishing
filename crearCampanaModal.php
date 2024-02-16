<?php
require_once 'assets/database/config.php';

if ($conn === null || mysqli_connect_errno()) {
  die('Error al conectar con la base de datos');
}

// Función para obtener las plantillas de correo
function obtenerPlantillasCorreo($conn)
{
  if ($conn !== null && !mysqli_connect_errno()) {
    $sql = "SELECT IDPlantilla, Nombre, Asunto, Cuerpo, LogoURL FROM PlantillasCorreo";
    $resultado = mysqli_query($conn, $sql);

    // Verificar si la consulta fue exitosa
    if ($resultado) {
      $plantillas = [];

      while ($fila = mysqli_fetch_assoc($resultado)) {
        $plantillas[] = $fila;
      }
      return $plantillas;
    } else {
      return [];
    }
  } else {
    return [];
  }
}
?>

<!-- Modal para Crear Nueva Campaña -->
<div class="modal fade" id="crearCampanaModal" tabindex="-1" role="dialog" aria-labelledby="crearCampanaModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="crearCampanaModalLabel">Crear Nueva Campaña de Phishing</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="formCrearCampana" method="POST" action="assets/database/crearCampana.php" enctype="multipart/form-data">
        <div class="modal-body">
          <div class="form-group">
            <label for="tipoCampana">Tipo de Campaña:</label>
            <select class="form-control" id="tipoCampana" name="tipoCampana" required>
              <option value="personalizada">Personalizada</option>
              <option value="maquetado">Maquetado</option>
            </select>
          </div>
          <div id="campanaPersonalizada" style="display: none;">
            <!-- Campos para campaña personalizada -->
            <div class="form-group">
              <label for="nombreCampana">Nombre de la Campaña:</label>
              <input type="text" class="form-control" id="nombreCampana" name="nombreCampana" required>
            </div>
            <div class="form-group">
              <label for="descripcionCampana">Descripción:</label>
              <textarea class="form-control" id="descripcionCampana" name="descripcionCampana" required></textarea>
            </div>
            <div class="form-group">
              <label for="cabeceraCorreo">Cabecera del Correo:</label>
              <input type="text" class="form-control" id="cabeceraCorreo" name="cabeceraCorreo">
            </div>
            <div class="form-group">
              <label for="asuntoCorreo">Asunto del Correo:</label>
              <input type="text" class="form-control" id="asuntoCorreo" name="asuntoCorreo">
            </div>
            <div class="form-group">
              <label for="cuerpoCorreo">Cuerpo del Correo:</label>
              <textarea class="form-control" id="cuerpoCorreo" name="cuerpoCorreo"></textarea>
            </div>
          </div>
          <div id="campanaMaquetado" style="display: none;">
            <!-- Campos para campaña maquetada -->
            <div class="form-group">
              <label>Ejemplos de Campañas:</label>
              <select class="form-control" id="ejemploCampana" name="ejemploCampana">
                <?php foreach (obtenerPlantillasCorreo($conn) as $plantilla) : ?>
                  <option value="<?php echo $plantilla['IDPlantilla']; ?>">
                    <?php echo $plantilla['Nombre']; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label>Método de Ingreso de Correos:</label>
          <div>
            <input type="radio" id="manual" name="metodoCorreos" value="manual" checked>
            <label for="manual">Introducir Correos</label>
          </div>
          <div>
            <input type="radio" id="masiva" name="metodoCorreos" value="masiva">
            <label for="masiva">Subida Masiva de Correos</label>
          </div>
        </div>
        <div id="introducirCorreos" style="display: block;">
          <!-- Campo para introducir correos manualmente -->
          <div class="form-group">
            <label for="correosUnicos">Correos (separados por comas):</label>
            <input type="text" class="form-control" id="correosUnicos" name="correosUnicos">
          </div>
        </div>
        <div id="subidaMasiva" style="display: none;">
          <!-- Campo para subida masiva de correos -->
          <div class="form-group">
            <label for="archivoCorreos">Archivo con correos (XML, Google Hoja de Cálculo, CSV):</label>
            <input type="file" class="form-control-file" id="archivoCorreos" name="archivoCorreos" accept=".xml, .csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.google-apps.spreadsheet">
          </div>
        </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      <button type="submit" class="btn btn-primary">Guardar Campaña</button>
    </div>
    </form>
  </div>
</div>
</div>