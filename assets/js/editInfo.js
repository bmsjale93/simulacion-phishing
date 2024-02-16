$(document).ready(function () {
  $("#editarInfoForm").on("submit", function (e) {
    e.preventDefault(); // Prevenir el comportamiento de envío por defecto

    var formData = $(this).serialize(); // Serializar los datos del formulario

    $.ajax({
      type: "POST",
      url: "/simulacion-phishing/assets/database/editInfo.php", // URL del script PHP
      data: formData, // Datos del formulario serializados
      dataType: "json", // Tipo de datos esperados de la respuesta
      success: function (response) {
        if (response.success) {
          alert(response.message); // Mostrar mensaje de éxito
          $("#editarInfoModal").modal("hide"); // Ocultar modal

          // Actualizar la información del usuario en la página
          $("#nombreUsuario").text("Nombre: " + $("#nombre").val());
          $("#direccionUsuario").text($("#direccion").val());
          $("#ciudadUsuario").text($("#ciudad").val());
          $("#paisUsuario").text($("#pais").val());
          $("#codigoPostalUsuario").text($("#codigoPostal").val());
          $("#telefonoUsuario").text($("#telefono").val());
        } else {
          alert("Error al actualizar: " + response.message);
        }
      },
      error: function (xhr, status, error) {
        // Manejar errores de la petición AJAX
        alert("Ha ocurrido un error: " + error);
      },
    });
  });
});
