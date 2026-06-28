<?php
try {
    $conn = new PDO(
        sprintf(mysql:host=%s;port=%s;dbname=%s, getenv(DB_HOST), getenv(DB_PORT) ?: 3306, getenv(DB_DATABASE)),
        getenv(DB_USERNAME),
        getenv(DB_PASSWORD)
    );
    echo Success\n;
} catch (PDOException $e) {
    echo $e->getMessage() . \n;
}
