<?php

function getDbConnection() {
    require_once "secrets.php";

    global $host, $username, $password, $dbname;
    
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        return [
            'success' => false,
            'error' => 'Database connection failed: ' . $conn->connect_error
        ];
    }
    
    return [
        'success' => true,
        'connection' => $conn
    ];
}


function closeDbConnection($conn) {
    require_once "update.php";

    if ($conn) {
        $conn->close();
    }

    autoUpdate();
}