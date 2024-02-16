$(document).ready(function () {
  // Lógica para mostrar/ocultar secciones del formulario
  $("#tipoCampana").change(function () {
    if ($(this).val() == "personalizada") {
      $("#campanaPersonalizada").show();
      $("#campanaMaquetado").hide();
    } else {
      $("#campanaMaquetado").show();
      $("#campanaPersonalizada").hide();
    }
  });

  $("input[type=radio][name=metodoCorreos]").change(function () {
    if (this.value == "manual") {
      $("#introducirCorreos").show();
      $("#subidaMasiva").hide();
    } else if (this.value == "masiva") {
      $("#subidaMasiva").show();
      $("#introducirCorreos").hide();
    }
  });

  // Escucha cambios en el select de plantillas
  $("#ejemploCampana").change(function () {
    var plantillaId = $(this).val();
    // Solo procede si se selecciona una plantilla válida
    if (plantillaId) {
      $.ajax({
        url:
          "/simulacion-phishing/assets/database/crearCampana.php?action=getPlantillaDetails&IDPlantilla=" +
          plantillaId,
        type: "GET",
        data: { IDPlantilla: plantillaId },
        dataType: "json",
        success: function (data) {
          $("#nombreCampana").val(data.Nombre);
          $("#asuntoCorreo").val(data.Asunto);
          $("#cuerpoCorreo").val(data.Cuerpo);
          // $("#logoCampana").attr("src", data.LogoURL);
        },
        error: function (xhr, status, error) {
          alert("Ocurrió un error al cargar los detalles de la plantilla.");
        },
      });
    }
  });

  // Manejo del evento submit del formulario para realizar la solicitud AJAX
  $("#formCrearCampana").on("submit", function (e) {
    e.preventDefault();

    // Crea un FormData con los datos del formulario
    var formData = new FormData(this);

    // Realiza la solicitud AJAX al servidor
    $.ajax({
      url: "/simulacion-phishing/assets/database/crearCampana.php",
      type: "POST",
      data: formData,
      contentType: false, // No especificar ningún tipo de contenido debido a FormData
      processData: false, // No procesar los datos para evitar que jQuery los convierta en una cadena de consulta
      success: function (response) {
        alert(response);
        $("#crearCampanaModal").modal("hide");
        location.reload();
      },
      error: function (xhr, status, error) {
        // Manejar errores
        alert("Ocurrió un error al crear la campaña.");
      },
    });
  });
});
