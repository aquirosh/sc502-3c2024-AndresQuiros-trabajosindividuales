<!DOCTYPE html>
<html>
    <head>
        <title>Hola mundo desde  PHP</title>
</head>
<body>
    <?php
        echo "<p>Hola mundo en PHP</p>";
        // declaracion de variables
        $nombre = "Diego";//variable global, puede ser accedida desde cualquier funcion
        $contador = 9;
        $peso = 69.7;
        $mayorEdad = true;

        function miFunction(){
            $variableLocal = "Variable local"; //solo tiene vida dentre dela funcion
            $nombre = "Juan";
            echo $variableLocal;
        }
        echo $nombre;

        miFunction();

        $edad = 15;
        //if simple
        if($edad < 18){
            echo "eres menor de edad";
        } elseif ($edad == 18) {
            echo "tienes 18";
        } else {
            echo "eres mayor de edad";
            $mayorEdad = true;
        }

        //aninados
        $membresia = true;
        if($edad >= 18){
            echo " Adulto";
            if($membresia){
               echo "  miembro";
            }
        } else {
            echo "menor de edad";
            if($membresia){
                echo " y miembro";
            }
        }

        //switch
        $nota = "F";
        switch($nota){
            case "A":
                echo "Excelente";
            break;

            case "B":
                echo "Muy bien!";
            break;

            case "C":
                echo "Bien!";
            break;
            default:
                echo "Necesitas mejorar";
            break;
        }
        


    ?>
</body>
</html>