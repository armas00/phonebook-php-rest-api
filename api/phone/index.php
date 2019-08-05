<?php
ini_set('error_reporting', E_ALL);

use Phonebook\Database;
use Phonebook\ContactManager;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Methods: POST", false);
header("Access-Control-Allow-Methods: PUT", false);
header("Access-Control-Allow-Methods: DELETE", false);
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$req = $_REQUEST;
$data = file_get_contents("php://input");
$output = [];

try{
    $data = (array) json_decode($data);
    
    require __DIR__ . '/../../db/Database.php';
    require __DIR__ . '/../../entity/ContactManager.php';

    switch($_SERVER['REQUEST_METHOD']){
        case 'GET':
            $output = pb_get($req);
            break;
        case 'POST':
            $output = pb_post($data);
            break;
        case 'PUT':
            $output = pb_put($data);
            break;
        case 'DELETE':
            $output = pb_delete($data);
            break;
        default:
            http_response_code(405);
            throw new Exception('Method Not Allowed');
            break;
    }

}catch(Exception $e){
    $output['success'] = false;
    $output['error'] = $e->getMessage();
}

echo json_encode($output);
exit();

function pb_post(array $data) : array {
    if(empty($data)){
        http_response_code(400);
        throw new Exception('Invalid Request');
    }
    
    if(!empty($data['name']) && !empty($data['surname']) && (!empty($data['phones']) || !empty($data['emails']))){
        $name = $data['name'];
        $surname = $data['surname'];
        $phones = !empty($data['phones']) ? $data['phones'] : [];
        $emails = !empty($data['emails']) ? $data['emails'] : [];
        
        $db = new Database();
        $mgr = new ContactManager($db);
        $result = $mgr->create($name, $surname, $phones, $emails);
    }else{
        throw new Exception('Please fill the name, surname and at least a phone or an email');
    }
    
    if($result['success']){
        $out['success'] = true;
        $out['message'] = 'Contact created successfuly';
    }else{
        $out['success'] = false;
        $out['error'] = $result['error'];
    }

    return $out;
}

function pb_get(array $req) : array {
    $field = '';
    $value = '';
    if(!empty($req['field'])){
        switch($req['field']){
            case 'name':
            case 'surname':
            case 'number':
            case 'email':
                $field = $req['field'];
                $value = $req['value'];
                break;
            default:
                // Try searching for a specific id
                $field = 'id';
                $value = $req['field'];
                break;
        }
    }else{
        // All records
    }
    
    if($field == '' || !empty($value)){
        $db = new Database();
        $mgr = new ContactManager($db);
        $result = $mgr->read($field, $value);
    }else{
        throw new Exception('For searching, a value must be provided');
    }
    
    if($result['success']){
        $out['success'] = true;
        $out['count'] = count($result['body']);
        $out['body'] = $result['body'];
    }else{
        $out['success'] = false;
        $out['error'] = $result['error'];
    }

    return $out;
}

function pb_put(array $data) : array {
    if(empty($data)){
        http_response_code(400);
        throw new Exception('Invalid Request');
    }
    
    if(!empty($data['id']) && (!empty($data['phones']) || !empty($data['emails']))){
        $id = (int) $data['id'];
        $name = !empty($data['name']) ? $data['name'] : '';
        $surname = !empty($data['surname']) ? $data['surname'] : '';
        $phones = !empty($data['phones']) ? $data['phones'] : [];
        $emails = !empty($data['emails']) ? $data['emails'] : [];
        
        $db = new Database();
        $mgr = new ContactManager($db);
        $result = $mgr->update($id, $name, $surname, $phones, $emails);
    }else{
        throw new Exception('Please fill the contact id and at least a phone or an email');
    }
    
    if($result['success']){
        $out['success'] = true;
        $out['message'] = 'Contact updated successfuly';
    }else{
        $out['success'] = false;
        $out['error'] = $result['error'];
    }

    return $out;
}

function pb_delete(array $data) : array {
    if(empty($data)){
        http_response_code(400);
        throw new Exception('Invalid Request');
    }
    
    if(!empty($data['id'])){
        $id = (int) $data['id'];
        
        $db = new Database();
        $mgr = new ContactManager($db);
        $result = $mgr->delete($id);
    }else{
        throw new Exception('Please fill the contact id for deleting');
    }
    
    if($result['success']){
        $out['success'] = true;
        $out['message'] = 'Contact deleted successfuly';
    }else{
        $out['success'] = false;
        $out['error'] = $result['error'];
    }

    return $out;
}

?>