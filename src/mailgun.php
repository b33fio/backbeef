<?php

use \Firebase\JWT\JWT;
use \Mailgun\Mailgun;

//Mail gun verification email function
//@param: $username == username
//@param: $email == email
//@param: $firstName == first name
//@param: $lastName == last name
//void return
function sendVerification($username, $email, $firstName, $lastName) {
    $key = getenv('MAIL_KEY', true) ?: getenv('MAIL_KEY');
    $payload = array("username" => $username, "email" => $email);
    $mailjwt = encodeJWT($payload);
    $link = 'http://b33f.io/api/public/token/'.$mailjwt;
    $domain = "b33f.io";
    $mailClient = new \Mailgun\Mailgun($key, new \Http\Adapter\Guzzle6\Client());
    $html = "<html><p>Click the following link to verify your account:</p><br><a href='".$link."'>CLICK HERE</a></html>";
    $mailClient->sendMessage($domain, array(
        'from'      => 'b33f.io <mailgun@b33f.io>',
        'to'        => $firstName.' '.$lastName.' <'.$email.'>',
        'subject'   => 'Please Verify your b33f.io Account',
        'html'      => $html));
}

?>