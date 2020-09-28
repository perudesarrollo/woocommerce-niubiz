console.log('joder visanet', configuration);

payform.setConfiguration(configuration);

var elementStyles = {
    base: {
        color: '#666666',
        fontWeight: 700,
        fontSize: '16px',
        fontSmoothing: 'antialiased',
        placeholder: {
            color: '#999999'
        },
        autofill: {
            color: '#e39f48',
        }
    },
    invalid: {
        color: '#E25950',
        '::placeholder': {
            color: '#FFCCA5',
        }
    }
};

var cardNumber = payform.createElement(
    'card-number', {
        style: elementStyles,
        placeholder: 'Número de Tarjeta'
    },
    'txtNumeroTarjeta');


cardNumber.then(element => {
    element.on('bin', function(data) { //Tu código aquí
        console.log('bin', data);
    });
    element.on('change', function(data) { //Tu código aquí
        console.log('change', data);
    });
    element.on('dcc', function(data) { //Tu código aquí
        console.log('dcc', data);
    });
    element.on('installments', function(data) { //Tu código aquí
        console.log('installments', data);
    });
});

function soloNumeros(e) {
    key = e.keyCode || e.which;
    tecla = String.fromCharCode(key).toLowerCase();
    //       letras = " �����abcdefghijklmn�opqrstuvwxyz";
    letras = "0123456789.";
    especiales = "8-37-39-46";

    tecla_especial = false
    for (var i in especiales) {
        if (key == especiales[i]) {
            tecla_especial = true;
            break;
        }
    }

    if (letras.indexOf(tecla) == -1 && !tecla_especial) {
        return false;
    }
}

function soloLetras(e) {
    key = e.keyCode || e.which;
    tecla = String.fromCharCode(key).toLowerCase();
    letras = " �����abcdefghijklmn�opqrstuvwxyz";
    //       letras = "0123456789";       
    especiales = "8-37-39-46";

    tecla_especial = false
    for (var i in especiales) {
        if (key == especiales[i]) {
            tecla_especial = true;
            break;
        }
    }

    if (letras.indexOf(tecla) == -1 && !tecla_especial) {
        return false;
    }
}

function valid_pan(value) {
    // accept only digits, dashes or spaces
    if (/[^0-9-\s]+/.test(value)) return false;

    // The Luhn Algorithm. It's so pretty.
    var nCheck = 0,
        nDigit = 0,
        bEven = false;
    value = value.replace(/\D/g, "");

    for (var n = value.length - 1; n >= 0; n--) {
        var cDigit = value.charAt(n),
            nDigit = parseInt(cDigit, 10);

        if (bEven) {
            if ((nDigit *= 2) > 9) nDigit -= 9;
        }

        nCheck += nDigit;
        bEven = !bEven;
    }

    valor = nCheck % 10;
    if (valor == 0) {
        //alert('TODO OK');
        document.getElementById('pagar').disabled = false;
        document.getElementById('pagar').className = 'iButton col-7';
        document.getElementById('msgpan').style.display = 'none';
    } else {
        //alert('N�mero de tarjeta no v�lido');
        //if (document.getElementById('PAN').value=
        //alert('Error: Tarjeta ingresada es incorrecta!');
        document.getElementById('msgpan').style.display = '';
        document.getElementById('pagar').disabled = true;
        document.getElementById('pagar').className = 'iButtonDisabled';
        document.getElementById('PAN').focus();
        //return false;
    }
}