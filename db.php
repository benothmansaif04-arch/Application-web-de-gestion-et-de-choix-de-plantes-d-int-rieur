<?php
function getConnection() {
    $conn = new mysqli('localhost', 'root', '', 'plantes_db');
    $conn->set_charset("utf8mb4");
    if ($conn->connect_error) {
        die('<div style="font-family:sans-serif;color:red;padding:20px;"><h3>Erreur MySQL</h3><p>'.$conn->connect_error.'</p></div>');
    }
    return $conn;
}
?>
