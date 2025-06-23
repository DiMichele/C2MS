<?php
echo "Test routing - File raggiunto correttamente!";
echo "<br>REQUEST_URI: " . $_SERVER['REQUEST_URI'];
echo "<br>SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'];
echo "<br>PATH_INFO: " . (isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : 'Non impostato'); 