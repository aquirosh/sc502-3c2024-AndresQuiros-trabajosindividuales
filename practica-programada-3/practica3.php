<?php

$transacciones = array();
function registrarTransaccion($id, $descripcion, $monto) {
    global $transacciones;
    
    foreach ($transacciones as $transaccion) {
        if ($transaccion['id'] == $id) {
            echo "Error: Ya existe una transacción con el ID $id.<br>";
            return false;
        }
    }
    
    // Validar que el monto sea positivo
    if ($monto <= 0) {
        echo "Error: El monto debe ser mayor que cero.<br>";
        return false;
    }
    
    // Crear la nueva transacción
    $nueva_transaccion = array(
        'id' => $id,
        'descripcion' => $descripcion,
        'monto' => $monto
    );
    
    // Añadir la transacción al arreglo
    array_push($transacciones, $nueva_transaccion);
    
    echo "Transacción con ID $id registrada correctamente.<br>";
    return true;
}


function generarEstadoDeCuenta() {
    global $transacciones;
    
    // Verificar si hay transacciones
    if (empty($transacciones)) {
        return "No hay transacciones registradas.";
    }
    
    $montoTotalContado = 0;
    foreach ($transacciones as $transaccion) {
        $montoTotalContado += $transaccion['monto'];
    }
    
    // Calcular el monto final a pagar
    $montoConInteres = $montoTotalContado * 1.026; // Aplicar 2.6% de interés
    $cashback = $montoTotalContado * 0.001; // Aplicar 0.1% de cashback
    $montoFinal = $montoConInteres - $cashback;
    
    // Generar el estado de cuenta 
    $estadoCuenta = "===== ESTADO DE CUENTA =====\n";
    $estadoCuenta .= "Fecha: " . date('Y-m-d H:i:s') . "\n\n";
    $estadoCuenta .= "DETALLE DE TRANSACCIONES:\n";
    $estadoCuenta .= str_pad("ID", 10) . str_pad("DESCRIPCIÓN", 40) . str_pad("MONTO", 15) . "\n";
    $estadoCuenta .= str_repeat("-", 65) . "\n";
    
    foreach ($transacciones as $transaccion) {
        $estadoCuenta .= str_pad($transaccion['id'], 10);
        $estadoCuenta .= str_pad($transaccion['descripcion'], 40);
        $estadoCuenta .= str_pad("$" . number_format($transaccion['monto'], 2), 15) . "\n";
    }
    
    $estadoCuenta .= str_repeat("-", 65) . "\n\n";
    $estadoCuenta .= str_pad("Monto total de contado:", 50) . "$" . number_format($montoTotalContado, 2) . "\n";
    $estadoCuenta .= str_pad("Monto con interés (2.6%):", 50) . "$" . number_format($montoConInteres, 2) . "\n";
    $estadoCuenta .= str_pad("Cash back (0.1%):", 50) . "$" . number_format($cashback, 2) . "\n";
    $estadoCuenta .= str_repeat("-", 65) . "\n";
    $estadoCuenta .= str_pad("MONTO FINAL A PAGAR:", 50) . "$" . number_format($montoFinal, 2) . "\n";
    
    // Guardar el estado de cuenta en un archivo de texto
    file_put_contents('estado_cuenta.txt', $estadoCuenta);
    echo "Se ha generado el archivo 'estado_cuenta.txt' con el estado de cuenta.<br><br>";
    
    return $estadoCuenta;
}

// Simulación de transacciones
echo "<h2>Registro de Transacciones</h2>";

registrarTransaccion(1, "Compra en supermercado", 1250.50);
registrarTransaccion(2, "Restaurante", 450.75);
registrarTransaccion(3, "Gasolina", 800.00);
registrarTransaccion(4, "Suscripción streaming", 199.00);
registrarTransaccion(5, "Farmacia", 320.80);

// Intentar registrar un ID duplicado para probar la validación
registrarTransaccion(3, "Compra en línea", 150.00);

// Intentar registrar monto negativo para probar la validación
registrarTransaccion(6, "Prueba", -50.00);

// Generar y mostrar el estado de cuenta
echo "<h2>Estado de Cuenta</h2>";
echo "<pre>";
echo generarEstadoDeCuenta();
echo "</pre>";
?>