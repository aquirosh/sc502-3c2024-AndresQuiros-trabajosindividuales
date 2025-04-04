<?php
session_unset();
session_destroy();
header('/semana11/app-tareas/index.html');
