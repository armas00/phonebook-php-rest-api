<?php
namespace Phonebook;

use \PDO;
use \PDOException;

class Database{
    
    private $host = 'localhost';
    private $db = 'phonebook';
    private $user = 'phonebook';
    private $pass = 'phonebook';
    
    public function connect() : PDO {
        try{
            $conn = new PDO('mysql:host='.$this->host.';dbname='.$this->db,$this->user,$this->pass);
            return $conn;
        }
        catch(PDOException $e){
            echo json_encode(['message' => "Connection error " . $e->getMessage()]);
            exit;
        }
    }
}
?>