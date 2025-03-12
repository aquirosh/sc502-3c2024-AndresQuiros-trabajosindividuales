<?php

//definir un arreglo de productos (inventario)
$productos = [
    ["id" => 1, "nombre" => "Producto 1", "cantidad" => 10, "precio" => 20.5],
    ["id" => 2, "nombre" => "Producto 2", "cantidad" => 5, "precio" => 15.0],
    ["id" => 3, "nombre" => "Producto 3", "cantidad" => 8, "precio" => 7.75],
    ["id" => 4, "nombre" => "Producto 4", "cantidad" => 12, "precio" => 9.99],
];

$carrito = [];

//funcion para agregar un producto al carrito
function buscarProducto($id){
    global $productos;
    $longitud = count($productos);
    for($i = 0; $i < $longitud; $i++){
        if($productos[$i]["id"] == $id){
            return $productos[$i];
        }
    }
    return null;
}

function agregarAlCarrito($idProducto, $cantidad){
    global $carrito, $productos;
    //buscar el producto por id
    $producto = buscarProducto($idProducto);
    //si no es nulo:  
    if($producto){
        //verificar la cantidad
        $cantidadProductoDisponible = $producto["cantidad"];
        if($cantidadProductoDisponible >= $cantidad){
            //agregar al carrito
            $producto["cantidad"] = $cantidad;
            array_push($carrito, ["id" => $producto["id"],
            "cantidad" => $cantidad,
            "precio" => $producto["precio"]
        ]);
            
            //disminuir el inventario
            for($i = 0; $i < count($productos); $i++){
                if($productos[$i]["id"] == $idProducto){
                    //edite la cantidad del producto en el inventario
                    $productos[$i]["cantidad"] -= $cantidad;
                    break;
                }
            }


            echo "Producto agregado exitosamente al carrito";


        }else{
            //si no hay suficiente cantidad mostrar mensaje
            echo "No hay suficiente cantidad para el producto " . $producto["nombre"];
        }
    }else{
        //si es nulo mostrar mensaje
        echo "Producto no encontrado";
    }
}

function calcularTotalCarrito(){
    global $carrito;
    $total = 0;
    foreach($carrito as $item){
        $total += $item["cantidad"] * $item["precio"];
    }
    return $total;
}

//simular agregar productos al carrito
agregarAlCarrito(1, 3);
echo '<br>';
agregarAlCarrito(2, 2);
echo '<br>';
agregarAlCarrito(3, 5);
echo '<br>';
echo "Total a pagar: " . calcularTotalCarrito();



?>