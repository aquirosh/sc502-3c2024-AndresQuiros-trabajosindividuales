<?php
session_start();
session_unset();
session_destroy();
header('Location: /semana11/app-tareas/index.html');