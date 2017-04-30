<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);

$app->post('/login', function($request, $response) {
    $pdo = $this->db;
    $jsoned = $request->getBody();
    $data = json_decode($jsoned);
    $username = $data->username;
    $raw_pw = $data->password;
    
    //check for empty inputs
    if(!isset($username) || !isset($raw_pw) || empty($username) || empty($raw_pw)) {
        $invalid = json_encode(array('successful'=>false, 'error'=> 'empty inputs'));
        return $invalid;
    }
    
    //check if the inputs are valid
    $set = $pdo->prepare('SELECT password FROM Users WHERE username=:username');
    $set->bindParam("username", $username);
    $set->execute();
    $get = $set->fetch(PDO::FETCH_ASSOC);
    $hashed = $get['password'];
    
    $valid = password_verify($raw_pw, $hashed);
    //only log in if the pw is valid
    if($valid) {
        $done = json_encode(array('successful'=>true));
        return $done;
    }
    else {
        $notdone = json_encode(array('successful'=>false));
        return $notdone;
    }
});

?>