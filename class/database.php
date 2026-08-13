<?php
class DataBase {
	
    private $PDO;

    public function __construct() {
        try {
            $this->PDO = new PDO(
                "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]
            );
        }
        catch(PDOException $ex)
        {
            error_log('Database connection failed: ' . $ex->getMessage());
            http_response_code(500);
            die('Database connection failed. Please check the server log.');
        }
    }
    public function select($query, $bindings = []) {
        $STH = $this->PDO->prepare($query);
        $STH->execute($bindings);
        $result = $STH->fetchAll(PDO::FETCH_ASSOC);
		$result ??= false;
		return $result;
    }

    public function query($query, $bindings = []){
        $STH = $this->PDO->prepare($query);
        return $STH->execute($bindings);
    }

    public function beginTransaction(){
        return $this->PDO->beginTransaction();
    }

    public function commit(){
        return $this->PDO->commit();
    }

    public function rollBack(){
        return $this->PDO->rollBack();
    }

    public function inTransaction(){
        return $this->PDO->inTransaction();
    }

}
