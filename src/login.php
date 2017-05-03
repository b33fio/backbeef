<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);


//updated 05-01-2017
//only allows login after account has been verified
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
        //check db to see if user has verified his/her/xer account
        $sth = $pdo->prepare("SELECT verified FROM Users WHERE username=:username");
        $sth->bindParam("username", $username);
        $sth->execute();
        $ver = $sth->fetchAll();
        if($ver) {
            //create new json web token from username and id
            //
            // get user id
            $query = $pdo->prepare("SELECT user_id FROM Users WHERE username=:username");
            $query->bindParam("username", $username);
            $query->execute();
            $result = $query->fetch(PDO::FETCH_ASSOC);
            $user_id = $result['user_id'];
            $json_ = json_encode(array('username' => $username, 'user_id' => $user_id));

            $newJWT = encodeJWT($json_);
            $done = array("successful" => true, "jwt" => $newJWT);
            return $response->withJson($done, 200);
        } else {
            $notdone = array("successful" => false);
            return $response->withJson($notdone, 403);
        }
    }
    else {
        $notdone = array("successful" => false);
        return $response->withJson($notdone, 404);
    }
});

