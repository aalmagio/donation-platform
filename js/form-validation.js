// JavaScript Document
// Wait for the DOM to be ready
$(function () {
    // Initialize form validation on the registration form.
    // It has the name attribute "registration"
    $("#donaora").validate({
        // Specify validation rules
        rules: {
            // The key name on the left side is the name attribute
            // of an input field. Validation rules are defined
            // on the right side
            nome: {
                required: true,
                minlength: 2
            },
            cognome: {
                required: true,
                minlength: 2
            },
            tel: {
                required: false,
                minlength: 8
            },

            privacy: "required",
            mail: {
                required: true,
                // Specify that email should be validated
                // by the built-in "email" rule
                email: true
            },
            importo_libero: {
                required: '#importo_altro[value="0"]:checked',
                minlength: 2,
                digits: true,
                min: 20
            },
            cartan: {
                required: '#pay_method_0[value="CC"]:checked',
                creditcard: true
            },
            titolare: {
                required: '#pay_method_0[value="CC"]:checked',
                minlength: 2
            },
            cvv: {
                required: '#pay_method_0[value="CC"]:checked',
                minlength: 3,
                digits: true
            },
            exp_mm: {
                required: '#pay_method_0[value="CC"]:checked',
                minlength: 2,
                digits: true
            },
            exp_yy: {
                required: '#pay_method_0[value="CC"]:checked',
                minlength: 2,
                digits: true
            }
        },
        // Specify validation error messages
        messages: {
            nome: {
                required: "Inserisci il nome ",
                minlength: "Il nome deve contenere almeno 2 catteri"
            },
            cognome: {
                required: "Inserisci il cognome",
                minlength: "Il nome deve contenere almeno 2 catteri"
            },

            tel: "inserisci almeno 8 numeri",
            mail: {
                required: "Inserisci un indirizzo mail",
                email: "Inserisci un indirizzo mail valido"
            },
            privacy: "&Egrave; necessario il consesso al trattamento per potere effettuare la donazione ",
            cartan: {
                required: "Il numero della carta &egrave; obbligatorio ",
                creditcard: "Il numero della carta &egrave; obbligatorio ",
            },
            titolare: {
                required: "Il titolare della carta &egrave; obbligatorio ",
                minlength: "Il titolare  deve contenere almeno 2 catteri"
            },
            cvv: {
                required: "Il codice cvv &egrave; obbligatorio",
                minlength: "Il cvv deve contenere almeno 3 catteri",
                digit: "Il codice cvv &egrave; un numero",
            },
            
            
            exp_mm: {
                required: "Il mese di scadenza &egrave; obbligatorio ",
                minlength: "Il mese di scadenza  deve contenere almeno 2 catteri",
                digit: "Il mese di scadenza &egrave; un numero",
            },
            exp_yy: {
                required: "L'anno di scadenza &egrave; obbligatorio ",
                minlength: "L'anno di scadenza  deve contenere almeno 2 catteri",
                digit: "L'anno di scadenza &egrave; un numero",
            }
        },
        highlight: function (element) {
            $(element).parent().addClass('error')
        },
        unhighlight: function (element) {
            $(element).parent().removeClass('error')
        },
        // Make sure the form is submitted to the destination defined
        // in the "action" attribute of the form when valid
        submitHandler: function (form) {
            form.submit();
        }
    });
});

$(function () {
    // $(".costex").hide();
    $("#importolibero").hide();
});

$(function () {
    $("input[name='importo']").click(function () {
        if ($("#importo_altro").is(":checked")) {
            $("#importolibero").show();
        } else {
            $("#importolibero").hide();
        }
    });
});

$(function () {
    $("input[name='pay_method']").click(function () {
        if ($("#pay_method_0").is(":checked")) {
            $(".cc_fieldset").show();
        } else {
            $(".cc_fieldset").hide();
        }
    });
});

// Cost example

$(function () {
    $("input[name='importo']").click(function () {
        if ($("#importo_3").is(":checked")) {
            $(".costex_3").show();
        } else {
            $(".costex_3").hide();
        }
        if ($("#importo_2").is(":checked")) {
            $(".costex_2").show();
        } else {
            $(".costex_2").hide();
        }
        if ($("#importo_1").is(":checked")) {
            $(".costex_1").show();
        } else {
            $(".costex_1").hide();
        }
        if ($("#importo_0").is(":checked")) {
            $(".costex_0").show();
        } else {
            $(".costex_0").hide();
        }
    });
});
