<?php

    function connection(){
        $host = "localhost";
        $username = "root";
        $password = "admin";
        $dbname = "paanakandb";
        
        $dsn = "mysql:host=$host;dbname=$dbname";
        try {
            $pdo = new PDO($dsn, $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    } 
?>