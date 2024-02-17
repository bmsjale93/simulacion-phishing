$(document).ready(function () {
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

  $("#ejemploCampana").change(function () {
    var plantillaId = $(this).val();
    if (plantillaId) {
      $.ajax({
        url:
          "/simulacion-phishing/assets/database/crearCampana.php?action=getPlantillaDetails&IDPlantilla=" +
          plantillaId,
        type: "GET",
        dataType: "json",
        success: function (data) {
          $("#nombreCampana").val(data.Nombre);
          $("#asuntoCorreo").val(data.Asunto);
          $("#cuerpoCorreo").val(data.Cuerpo);
        },
        error: function (xhr, status, error) {
          alert("Ocurrió un error al cargar los detalles de la plantilla.");
        },
      });
    }
  });

  $("#formCrearCampana").on("submit", function (e) {
    e.preventDefault();
    if (!validarCamposObligatorios()) {
      alert("Por favor, complete todos los campos obligatorios.");
      return;
    }

    var formData = new FormData(this);
    $.ajax({
      url: "/simulacion-phishing/assets/database/crearCampana.php",
      type: "POST",
      data: formData,
      contentType: false,
      processData: false,
      success: function (response) {
        alert(response);
        $("#crearCampanaModal").modal("hide");
        location.reload();
      },
      error: function (xhr, status, error) {
        alert("Ocurrió un error al crear la campaña.");
      },
    });
  });

  function validarCamposObligatorios() {
    var camposRequeridos = [
      {
        selector: "#tipoCampana",
        mensaje: "El tipo de campaña es obligatorio.",
      },
      {
        selector: "#nombreCampana",
        mensaje: "El nombre de la campaña es obligatorio.",
      },
      {
        selector: "#descripcionCampana",
        mensaje: "La descripción de la campaña es obligatoria.",
      },
    ];
    var todosLosCamposSonValidos = true;

    $(".error").remove();
    camposRequeridos.forEach(function (campo) {
      var valor = $(campo.selector).val().trim();
      if (valor === "") {
        mostrarMensajeDeError(campo.selector, campo.mensaje);
        todosLosCamposSonValidos = false;
      }
    });

    return todosLosCamposSonValidos;
  }

  function mostrarMensajeDeError(selector, mensaje) {
    var $mensajeError = $(
      "<span class='error' style='color: red; display: block;'>" +
        mensaje +
        "</span>"
    );
    $(selector).after($mensajeError);
  }
});
