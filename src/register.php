<?php
header('Content-Type: application/json;charset=utf-8');
ini_set('display_errors', 'On');
error_reporting(E_ALL);

//add functionalities
//3. sessions
//4. (ICING) mailgun

//create a user
$app->post('/register', function($request, $response) {
    //get data
    $data = $request->getParsedBody();

        // Check if inputs are empty
        if(!isset($data['username']) || !isset($data['first_name']) || !isset($data['email']) || !isset($data['password']) || !isset($data['user_type']) || !isset($data['password_check']) || !isset($data['phone_number']) || empty($data['username']) || empty($data['first_name']) || empty($data['email']) || empty($data['password']) || empty($data['user_type']) || empty($data['password_check']) || empty($data['phone_number'])) {
          $error = json_encode(array('successful'=>false, 'error'=>'fields are empty'));
            return $error;
        }
    
        //unique username check
        $u_sql = "SELECT * FROM Users WHERE username =:username";
        $u_query = $this->db->prepare($u_sql);
        $u_query->bindParam("username", $data['username']);
        $u_query->execute();
        $u_result = $u_query->fetchAll();
        //if there exists one, return ivalid and print error
        if($u_query->rowCount() > 0) {
            $invalid = json_encode(array("successful" => false, 'error' => 'username is already used'));
            return $invalid;
        }
        else {
            //unique email check
            $e_sql = "SELECT * FROM Users WHERE email =:email";
            $e_query = $this->db->prepare($e_sql);
            $e_query->bindParam("email", $data['email']);
            $e_query->execute();
            $e_result = $e_query->fetchAll();
            if($e_query->rowCount() > 0) {
                $invalid = json_encode(array("successful" => false, 'error' => 'email is already registered'));
                return $invalid;
            }
            else {

                //check if password check is correct
                if($data['password'] != $data['password_check']) {
                    $invalid = json_encode(array('successful' => false, 'error' => 'password does not match the check'));
                    return $invalid;
                }

                else {
                $hashed = password_hash($data['password'], PASSWORD_DEFAULT);

                //add user to table User
                $sql = "insert into Users (first_name, username, user_type, password, email, phone_number, account_created) 
                values (:first_name, :username, :user_type, :password, :email, :phone_number, NOW())";
                $sth = $this->db->prepare($sql);
                $sth->bindParam("first_name", $data['first_name']);
                $sth->bindParam("username", $data['username']);
                $sth->bindParam("user_type", $data['user_type']);
                $sth->bindParam("password", $hashed);
                $sth->bindParam("email", $data['email']);
                $sth->bindParam("phone_number", $data['phone_number']);
                $sth->execute();

                //Session Token -> store in db, check for each page

                $valid = json_encode(array("successful" => true));
                return $valid;
                }
            }
        }
});

?>
