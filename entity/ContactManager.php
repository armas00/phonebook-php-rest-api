<?php
namespace Phonebook;

use \PDO;

class ContactManager {
    private $conn;
    
    public function __construct(Database $db) {
        $this->conn = $db->connect();
    }
    
    public function create(string $name, string $surname, array $phones, array $emails) : array {
        $ok = true;
        
        $this->conn->beginTransaction();
        
        $sql_contact = "INSERT INTO contact (name,surname) VALUES (:name,:surname)";
        $stmt_contact = $this->conn->prepare($sql_contact);
        
        $stmt_contact->bindValue(':name', trim($name));
        $stmt_contact->bindValue(':surname', trim($surname));
        
        if($stmt_contact->execute()){
            $sql_id = "SELECT MAX(id) AS id FROM contact";
            $res_id = $this->conn->query($sql_id);
            if(!$res_id){
                $ok = false;
            }
            $id = $res_id->fetchColumn();
            if(!$id){
                $ok = false;
            }
            
            $sql_phone = "INSERT INTO phone (id,number) VALUES (:id,:number)";
            $stmt_phone = $this->conn->prepare($sql_phone);

            foreach($phones as $item){
                $stmt_phone->bindValue(':id', $id);
                $stmt_phone->bindValue(':number', trim($item));
                if(!$stmt_phone->execute()){
                    $ok = false;
                }
            }
            
            $sql_email = "INSERT INTO email (id,address) VALUES (:id,:address)";
            $stmt_email = $this->conn->prepare($sql_email);

            foreach($emails as $item){
                $stmt_email->bindValue(':id', $id);
                $stmt_email->bindValue(':address', trim($item));
                if(!$stmt_email->execute()){
                    $ok = false;
                }
            }
        }else{
            $ok = false;
        }
        
        if(!$ok){
            $this->conn->rollBack();
            throw new Exception('Contact could not be created');
        }
        $this->conn->commit();
        
        $result['success'] = true;
        return $result;
    }
    
    public function read(string $field = '', string $value = '') : array {
        $where = '';
        switch($field){
            case 'id':
                $sql = "SELECT contact.id, name, surname, GROUP_CONCAT(DISTINCT number SEPARATOR ', ') AS num, " . 
                                "GROUP_CONCAT(DISTINCT address SEPARATOR ', ') AS addr " .
                        "FROM contact LEFT JOIN phone ON contact.id = phone.id " .
                                "LEFT JOIN email ON contact.id = email.id " .
                        "WHERE contact.id = :id " .
                        "GROUP BY contact.id ORDER BY name ASC limit 50";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindValue(':id', $value);
                $stmt->execute();
                break;
            case 'name':
                $sql = "SELECT contact.id, name, surname, GROUP_CONCAT(DISTINCT number SEPARATOR ', ') AS num, " . 
                                "GROUP_CONCAT(DISTINCT address SEPARATOR ', ') AS addr " .
                        "FROM contact LEFT JOIN phone ON contact.id = phone.id " .
                                "LEFT JOIN email ON contact.id = email.id " .
                        "WHERE name like :name " .
                        "GROUP BY contact.id ORDER BY name ASC limit 50";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindValue(':name', "%{$value}%");
                $stmt->execute();
                break;
            case 'surname':
                $sql = "SELECT contact.id, name, surname, GROUP_CONCAT(DISTINCT number SEPARATOR ', ') AS num, " . 
                                "GROUP_CONCAT(DISTINCT address SEPARATOR ', ') AS addr " .
                        "FROM contact LEFT JOIN phone ON contact.id = phone.id " .
                                "LEFT JOIN email ON contact.id = email.id " .
                        "WHERE surname like :surname " .
                        "GROUP BY contact.id ORDER BY name ASC limit 50";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindValue(':surname', "%{$value}%");
                $stmt->execute();
                break;
            case 'number':
                $sql = "SELECT contact.id, name, surname, GROUP_CONCAT(DISTINCT number SEPARATOR ', ') AS num, " . 
                                "GROUP_CONCAT(DISTINCT address SEPARATOR ', ') AS addr " .
                        "FROM contact LEFT JOIN phone ON contact.id = phone.id " .
                                "LEFT JOIN email ON contact.id = email.id " .
                        "WHERE number like :number " .
                        "GROUP BY contact.id ORDER BY name ASC limit 50";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindValue(':number', "%{$value}%");
                $stmt->execute();
                break;
            case 'email':
                $sql = "SELECT contact.id, name, surname, GROUP_CONCAT(DISTINCT number SEPARATOR ', ') AS num, " . 
                                "GROUP_CONCAT(DISTINCT address SEPARATOR ', ') AS addr " .
                        "FROM contact LEFT JOIN phone ON contact.id = phone.id " .
                                "LEFT JOIN email ON contact.id = email.id " .
                        "WHERE address like :address " .
                        "GROUP BY contact.id ORDER BY name ASC limit 50";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindValue(':address', "%{$value}%");
                $stmt->execute();
                break;
            default:
                $sql = "SELECT contact.id, name, surname, GROUP_CONCAT(DISTINCT number SEPARATOR ', ') AS num, " . 
                                "GROUP_CONCAT(DISTINCT address SEPARATOR ', ') AS addr " .
                        "FROM contact LEFT JOIN phone ON contact.id = phone.id " .
                                "LEFT JOIN email ON contact.id = email.id " .
                        "GROUP BY contact.id ORDER BY name ASC limit 50";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute();
                break;
        }
        
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result['success'] = true;
        $result['body'] = $contacts;
        return $result;
    }
    
    public function update(int $id, string $name, string $surname, array $phones, array $emails) : array {
        $ok = true;
        
        $this->conn->beginTransaction();
        
        $sql_contact = "SELECT * FROM contact WHERE id=:id";
        $stmt_contact = $this->conn->prepare($sql_contact);
        $stmt_contact->bindValue(':id', $id);
        $stmt_contact->execute();
        if(!$stmt_contact || $stmt_contact->rowCount() == 0 || 
                                    !$contact = $stmt_contact->fetch(PDO::FETCH_ASSOC)){
            $ok = false;
        }
        
        $name = !empty($name) ? $name : $contact['name'];
        $surname = !empty($surname) ? $surname : $contact['surname'];
        
        $sql_contact = "UPDATE contact SET name = :name, surname = :surname WHERE id = :id";
        $stmt_contact = $this->conn->prepare($sql_contact);
        
        $stmt_contact->bindValue(':name', trim($name));
        $stmt_contact->bindValue(':surname', trim($surname));
        $stmt_contact->bindValue(':id', $id);
        
        if($stmt_contact->execute()){
            if(!empty($phones)){
                $sql_phone = "DELETE FROM phone WHERE id = :id";
                $stmt_phone = $this->conn->prepare($sql_phone);
                $stmt_phone->bindValue(':id', $id);
                if(!$stmt_phone->execute()){
                    $ok = false;
                }
                
                $sql_phone = "INSERT INTO phone (id,number) VALUES (:id,:number)";
                $stmt_phone = $this->conn->prepare($sql_phone);

                foreach($phones as $item){
                    $stmt_phone->bindValue(':id', $id);
                    $stmt_phone->bindValue(':number', trim($item));
                    if(!$stmt_phone->execute()){
                        $ok = false;
                    }
                }
            }
            
            if(!empty($emails)){
                $sql_email = "DELETE FROM email WHERE id = :id";
                $stmt_email = $this->conn->prepare($sql_email);
                $stmt_email->bindValue(':id', $id);
                if(!$stmt_email->execute()){
                    $ok = false;
                }
                            
                $sql_email = "INSERT INTO email (id,address) VALUES (:id,:address)";
                $stmt_email = $this->conn->prepare($sql_email);

                foreach($emails as $item){
                    $stmt_email->bindValue(':id', $id);
                    $stmt_email->bindValue(':address', trim($item));
                    if(!$stmt_email->execute()){
                        $ok = false;
                    }
                }
            }
            
        }else{
            $ok = false;
        }
        
        if(!$ok){
            $this->conn->rollBack();
            throw new Exception('Contact could not be updated');
        }
        $this->conn->commit();
        
        $result['success'] = true;
        return $result;
    }
    
    public function delete(int $id) : array {
        $ok = true;
        
        $sql_contact = "SELECT * FROM contact WHERE id=:id";
        $stmt_contact = $this->conn->prepare($sql_contact);
        $stmt_contact->bindValue(':id', $id);
        $stmt_contact->execute();
        if(!$stmt_contact || $stmt_contact->rowCount() == 0 || 
                                    !$contact = $stmt_contact->fetch(PDO::FETCH_ASSOC)){
            $ok = false;
        }
        
        $sql_contact = "DELETE from contact WHERE id = :id";
        $stmt_contact = $this->conn->prepare($sql_contact);
        $stmt_contact->bindValue(':id', $id);
        
        if(!$stmt_contact->execute()){
            $ok = false;
        }
        
        if(!$ok){
            throw new Exception('Contact could not be deleted');
        }
        
        $result['success'] = true;
        return $result;
    }
    
}
?>