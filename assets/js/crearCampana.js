$(document).ready(function () {
  mostrarCamposBasadosEnTipo($("#tipoCampana").val());

  $("#tipoCampana").change(function () {
    var tipo = $(this).val();
    mostrarCamposBasadosEnTipo(tipo);
  });

  function mostrarCamposBasadosEnTipo(tipo) {
    $("#tipoPlantilla").val(tipo);
    if (tipo == "personalizada") {
      $("#campanaPersonalizada").show();
      $("#campanaMaquetado").hide();
      $("#IDPlantilla").val("");
    } else {
      $("#campanaMaquetado").show();
      $("#campanaPersonalizada").hide();
    }
  }

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
          $("#tipoPlantilla").val("predeterminada");
          $("#IDPlantilla").val(plantillaId);
          $("#campanaPersonalizada").show();
          $("#campanaMaquetado").hide();
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
