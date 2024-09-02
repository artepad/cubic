jQuery(function($) {
    "use strict";
    // Cambiar la acción del formulario para que apunte a 'procesar_formulario.php'
    $('form#wrapped').attr('action', 'procesar_formulario.php');
    
    $("#wizard_container").wizard({
        stepsWrapper: "#wrapped",
        submit: ".submit",
        unidirectional: false,
        beforeSelect: function(event, state) {
            if ($('input#website').val().length != 0) {
                return false;
            }
            if (!state.isMovingForward)
                return true;
            var inputs = $(this).wizard('state').step.find(':input');
            return !inputs.length || !!inputs.valid();
        }
    }).validate({
        errorPlacement: function(error, element) {
            if (element.is(':radio') || element.is(':checkbox')){
                error.insertBefore(element.next());
            } else {
                error.insertAfter(element);
            }
        }
    });
    
    // Barra de progreso
    $("#progressbar").progressbar();
    $("#wizard_container").wizard({
        afterSelect: function(event, state) {
            $("#progressbar").progressbar("value", state.percentComplete);
            $("#location").text("" + state.stepsComplete + " de " + state.stepsPossible + " completados");
        }
    });
});

$("#wizard_container").wizard({
    transitions: {
        branchtype: function($step, action) {
            var branch = $step.find(":checked").val();
            if (!branch) {
                $("form").valid();
            }
            return branch;
        }
    }
});

// Obtener valores de nombre y correo electrónico
function getVals(formControl, controlType) {
    var value = $(formControl).val();
    switch (controlType) {
        case 'name_field':
            $("#name_field").text(value);
            break;
        case 'email_field':
            $("#email_field").text(value);
            break;
    }
}

// Asignar los eventos 'onchange' a los campos del formulario en HTML
$('input[name="nombres"]').on('change', function() {
    getVals(this, 'name_field');
});
$('input[name="email"]').on('change', function() {
    getVals(this, 'email_field');
});
