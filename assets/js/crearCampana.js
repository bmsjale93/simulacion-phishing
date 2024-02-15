$(document).ready(function() {
    $('#formCrearCampana').on('submit', function(e) {
        e.preventDefault();

        // Crea un FormData con los datos del formulario
        var formData = new FormData(this);

        // Realiza la solicitud AJAX al servidor
        $.ajax({
          url: "simulacion-phishing/assets/database/crearCampana.php",
          type: "POST",
          data: formData,
          contentType: false, // No especificar ningún tipo de contenido debido a FormData
          processData: false, // No procesar los datos para evitar que jQuery los convierta en una cadena de consulta
          success: function (response) {
            alert(response);
            $("#crearCampañaModal").modal("hide");
            location.reload();
          },
          error: function (xhr, status, error) {
            // Manejar errores
            alert("Ocurrió un error al crear la campaña.");
          },
        });
    });
});
