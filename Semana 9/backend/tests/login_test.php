<?php
require('../login.php');

if(login('user1', 'pass1')){
    echo "Login exitoso".PHP_EOL;
} else {
    echo "Login incorrecto".PHP_EOL;
}

if(login ('adsaaw', 'asdwas')){
    echo "Login exitoso".PHP_EOL;
} else {
    echo "Login incorrecto".PHP_EOL;
}
