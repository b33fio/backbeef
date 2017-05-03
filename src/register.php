<?php
header('Content-Type: application/json;charset=utf-8');
ini_set('display_errors', 'On');
error_reporting(E_ALL);

//create a user
$app->post('/register', function($request, $response) {
    //get data
    $data = $request->getParsedBody();

        // Check if inputs are empty
        if(!isset($data['username']) || !isset($data['first_name']) || !isset($data['email']) || !isset($data['password']) || empty($data['username']) || empty($data['first_name']) || empty($data['email']) || empty($data['password']) ) {
          $error = array('successful'=>false, 'error'=>'fields are empty');
            return $this->response->withJson($error);
        }
    
        if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
           $error = array('successful'=>false, 'error'=>'Email address is invalid!');
            return $this->response->withJson($error); 
        }
    
        //unique username check
        $u_sql = "SELECT * FROM Users WHERE username =:username";
        $u_query = $this->db->prepare($u_sql);
        $u_query->bindParam("username", $data['username']);
        $u_query->execute();
        $u_result = $u_query->fetchAll();
        //if there exists one, return ivalid and print error
        if($u_query->rowCount() > 0) {
            $invalid = array("successful" => false, 'error' => 'username is already used');
            return $this->response->withJson($invalid);
        }
        else {
            //unique email check
            $e_sql = "SELECT * FROM Users WHERE email =:email";
            $e_query = $this->db->prepare($e_sql);
            $e_query->bindParam("email", $data['email']);
            $e_query->execute();
            $e_result = $e_query->fetchAll();
            if($e_query->rowCount() > 0) {
                $invalid = array("successful" => false, 'error' => 'email is already registered');
                return $this->response->withJson($invalid);
            }
            else {

                $hashed = password_hash($data['password'], PASSWORD_DEFAULT);

                //add user to table User
                $sql = "insert into Users (first_name, username, password, email, phone_number, account_created) 
                values (:first_name, :username, :password, :email, :phone_number, NOW())";
                $sth = $this->db->prepare($sql);
                $sth->bindParam("first_name", $data['first_name']);
                $sth->bindParam("username", $data['username']);
                $sth->bindParam("password", $hashed);
                $sth->bindParam("email", $data['email']);
                $sth->bindParam("phone_number", $data['phone_number']);
                $sth->execute();

                //Session Token -> store in db, check for each page

                //send verification email
                sendVerification($data['username'], $data['email'], $data['first_name']);
                $valid = array("successful" => true);
                return $this->response->withJson($valid);
            }
        }
});

