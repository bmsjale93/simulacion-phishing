$(document).ready(function() {
    function cargarCampanas() {
        $.ajax({
          url: "simulacion-phishing/assets/database/listarCampanas.php",
          type: "GET",
          dataType: "json",
          success: function (campañas) {
            var html = "";
            campañas.forEach(function (campana) {
              html +=
                '<div class="campana">' +
                "<h3>" +
                campana.Nombre +
                "</h3>" +
                "<p>" +
                campana.Descripción +
                "</p>" +
                "</div>";
            });
            $("#contenedorCampanas").html(html);
          },
          error: function () {
            alert("Error al cargar las campañas");
          },
        });
    }

    cargarCampanas();
});
