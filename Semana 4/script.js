function calculate() {
    let num1 = parseFloat(document.getElementById('num1').value);
    let num2 = parseFloat(document.getElementById('num2').value);
    let operation = document.getElementById('operation').value;
    let result = 0;

    switch (operation) {
        case 'sum':
            result = num1 + num2;
            break;
        case 'sub':
            result = num1 - num2;
            break;
        case 'mul':
            result = num1 * num2;
            break;
        case 'div':
            if (num2 == 0) {
                alert("No se puede dividir entre cero");
            } else {
                result = num1 / num2;
            }
            break;
        default:
            alert("La operacion seleccionada no existe o no esta manejada");

    }

    document.getElementById('result').innerText = result;

}