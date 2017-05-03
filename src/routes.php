<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);

/////MAILGUN/////

//mailgun check
//proof of concept
//DISREGARD
$app->get('/token/test/[{username}]', function($request, $response, $args) {
    $username = $args['username'];
    //$email = "thisisisk@gmail.com";
    $fN = "Kevin";
    $lN = "Queenan";
    sendVerification($username, $email, $fN, $lN);
    print_r("email sent!");
});

/////AUTHENTICATION/////

//mail confirmation endpoint
//listen and decode jwt appended to url
//alter user table accordingly
//if decoded jwt matches valid username -> verified === 1
$app->get('/token/[{token}]', function ($request, $response, $args) {
    $pdo = $this->db;

    try {
        $jwt = $args['token'];
        $payload = decodeJWT($jwt);
    } catch (Exception $e) {
        echo "Exception: ".$e->getMessage();
        return $response->withStatus(400);
    }

    $username = $payload->username;
    $exp = $payload->exp;
    $now = time();
    if($now > $exp) {
        $false = array("successful" => false);
        return $response->withJson($false, 403);
    } else {
        $sth = $pdo->prepare("UPDATE Users Set verified=1 Where username=:username");
        $sth->bindParam("username", $username);
        $sth->execute();
        $reply = array("successful" => true);
        return $response->withJson($reply, 200);
    }
});

/////CHANNELS/////

//get all channels
$app->get('/channels', function ($request, $response, $args) {
    //Luke-added join sql
    $sth = $this->db->prepare("SELECT * FROM Channels left join (SELECT debate_channel, COUNT(*) AS debate_count FROM Debates GROUP BY debate_channel) as dc ON Channels.channel_id=dc.debate_channel ORDER BY debate_count DESC;");
    $sth->execute();
    $obj = $sth->fetchAll();
    $channels = array("channels" => $obj);
    return $this->response->withJson($channels);
});

// adding auth is as EASY as:
// })->add(new AuthMiddleware());

//get channel by id
//list all debates in channel by id
$app->get('/channels/[{id}]', function ($request, $response, $args) {
    //Luke-added joins to get proponent/opponent usernames 
    $sth = $this->db->prepare("SELECT debate_id,debate_name,debate_channel,debate_created,debate_updated,proponent_id,opponent_id,debate_state,proponent_username,opponent_username FROM Debates as D left join (select username as proponent_username,user_id from Users) as Up on D.proponent_id=Up.user_id left join (select username as opponent_username,user_id from Users) as Uo on D.opponent_id=Uo.user_id where D.debate_channel=:id");
    $sth->bindParam("id", $args['id']);
    $sth->execute();
    $chan = $sth->fetchAll();
    $debates = array($chan)[0];
    
    // get point count
    foreach($debates as &$debate) {
        $debate_id = $debate['debate_id'];
        $sth = $this->db->prepare("SELECT * from Points where point_debate=:debate_id");
        $sth->bindParam("debate_id", $debate_id);
        $sth->execute();
        $results = $sth->fetchAll();
        $debate['point_count'] = count($results);
    }

    return $this->response->withJson(array($debates));
});

//search channels by keyword
$app->get('/channels/search/[{query}]', function ($request, $response, $args) {
    $sth = $this->db->prepare("SELECT * FROM Channels WHERE UPPER(channel_name) LIKE :query ORDER BY channel_name");
    $query = "%".$args['query']."%";
    $sth->bindParam("query", $query);
    $sth->execute();
    $chan = $sth->fetchAll();
    $reply = array("successful" => true, "channels" => $chan);
	return $response->withJson($reply, 200);
});

/////DEBATES/////

//get all debates
$app->get('/debates', function ($request, $response, $args) {
    //Luke-addded join to retrieve point count
    $sth = $this->db->prepare("SELECT * FROM Debates left join (SELECT point_debate, COUNT(*) AS point_count FROM Points GROUP BY point_debate) as n ON Debates.debate_id=n.point_debate ORDER BY point_count DESC;");
	$sth->execute();
	$debates = $sth->fetchAll();
    $reply = array("successful" => true, "debates" => $debates);
	return $response->withJson($reply, 200);
});

//return all debates from specified channel name
//@param: [{channel}] == string == channel_name
$app->get('/debates/channel/[{channel}]', function ($request, $response, $args) {
    // select * from Debates natural join Channels where Channels.channel_name = "CS" and debate_channel = channel_id;
    // SELECT * FROM Channels NATURAL JOIN Debates WHERE Channels.channel_name=:channel");
    $sth = $this->db->prepare("SELECT * from Debates NATURAL JOIN Channels where Channels.channel_name=:channel and debate_channel = channel_id");
    $sth->bindParam("channel", $args['channel']);
    $sth->execute();
    $debates = $sth->fetchAll();
    return $this->response->withJson($debates);
});

//return all debates from specified channel id
//@param: [{id}] == integer == channel_id
// * possible change: rewrite in inner SQL to have it by channel name
$app->get('/debates/channel/id/[{id}]', function ($request, $response, $args) {
    $sth = $this->db->prepare("SELECT * FROM Debates WHERE debate_channel=:id");
    $sth->bindParam("channel", $args['channel']);
    $sth->execute();
    $debates = $sth->fetchAll();
    return $this->response->withJson($debates);
});

//get debates by state
$app->get('/debates/state/[{state}]', function ($request, $response, $args) {
    $sth = $this->db->prepare("SELECT * FROM Debates WHERE debate_state=:state");
    $sth->bindParam("state", $args['state']);
    $sth->execute();
    $debates = $sth->fetchObject();
    return $this->response->withJson($debates);
});

//get debates by id
$app->get('/debates/[{id}]', function ($request, $response, $args)  {   
    //Luke - added joins to retrieve usernames for proponent/opponent
    $sth = $this->db->prepare("SELECT debate_id,debate_name,debate_channel,debate_created,debate_updated,proponent_id,opponent_id,debate_state,proponent_username,opponent_username, channel_name FROM Debates as D left join (select username as proponent_username, user_id from Users) as Up on D.proponent_id=Up.user_id left join (select username as opponent_username, user_id from Users) as Uo on D.opponent_id=Uo.user_id left join (select channel_id, channel_name from Channels) as C on D.debate_channel=C.channel_id where D.debate_id=:id");
    $sth->bindParam("id", $args['id']);
    $sth->execute();
    $debates = $sth->fetchAll();
    //return every point
    $sth = $this->db->prepare("SELECT point_id, poster_id, username, point_text, point_created FROM Points JOIN Users JOIN Debates WHERE point_debate=:id AND debate_id=point_debate AND user_id=poster_id");
    $sth->bindParam("id", $args['id']);
    $sth->execute();
    $points = $sth->fetchAll();
    $final = array("debate" => $debates[0], "points" => $points);
    return $this->response->withJson($final);
});

//search for debates with search term in the name
$app->get('/debates/search/[{query}]', function($request, $response, $args) {
	$sth = $this->db->prepare("SELECT * FROM Debates WHERE UPPER(debate_name) LIKE :query ORDER BY debate_name");
    if(!isset($args['query'])||empty($args['query'])) {
        return $response->withStatus(418);
    }
    else {
        $query = "%".$args['query']."%";
        $sth->bindParam("query", $query);
	    $sth->execute();
	    $debates = $sth->fetchAll();
        return $this->response->withJson($debates);
    }
});

//get debates by user
// this shouldn't be a POST but we are passing JWT in body
$app->post('/user/debates', function ($request, $response, $args) {
    $input = $request->getParsedBody();

    $token = $input['jwt'];
    try {
        $decodedJWT = decodeJWT($token);
    } catch (\Exception $e) {
        return $this->response->withJson(array('msg'=>'error: could not decode'))->withStatus(403);
    }

    $user_id = json_decode($decodedJWT->username)->user_id;

    $sth = $this->db->prepare(
        "SELECT * FROM Debates
            left join (SELECT point_debate, COUNT(*) AS point_count FROM Points GROUP BY point_debate) as n
            ON Debates.debate_id=n.point_debate
            where Debates.proponent_id=:user_id or Debates.opponent_id=:user_id
            ORDER BY point_count DESC;");

    $sth->bindParam("user_id", $user_id);
	$sth->execute();
	$debates = $sth->fetchAll();
    $reply = array("successful" => true, "debates" => $debates);
	return $response->withJson($reply, 200);
});


////USERS/////

//get all users -> change phone number param to varchar(10) from int(10)
$app->get('/users', function ($request, $response, $args) {
	$sth = $this->db->prepare("SELECT * FROM Users ORDER BY user_id ASC");
	$sth->execute();
	$users = $sth->fetchAll();
	return $this->response->withJson($users);
});

//get users by id
$app->get('/users/[{id}]', function ($request, $response, $args) {
    $sth = $this->db->prepare("SELECT * FROM Users WHERE user_id=:id");
    $sth->bindParam("id", $args['id']);
    $sth->execute();
    $users = $sth->fetchObject();
    return $this->response->withJson($users);
});

//search users by username
$app->get('/users/search/username/[{query}]', function ($request, $response, $args) {
    $sth = $this->db->prepare("SELECT * FROM Users WHERE UPPER(username) LIKE :query ORDER BY username");
    $query = "%".$args['query']."%";
    $sth->bindParam("query", $query);
    $sth->execute();
    $users = $sth->fetchAll();
    return $this->response->withJson($users);
});

//search users by email
$app->get('/users/search/email/[{query}]', function ($request, $response, $args) {
    $sth = $this->db->prepare("SELECT * FROM Users WHERE UPPER(email) LIKE :query ORDER BY email");
    $query = "%".$args['query']."%";
    $sth->bindParam("query", $query);
    $sth->execute();
    $users = $sth->fetchAll();
    return $this->response->withJson($users);
});

//delete a user with username
$app->delete('/users/delete/[{username}]', function ($request, $response, $args) {
        $sth = $this->db->prepare("DELETE FROM Users WHERE username=:username");
    $sth->bindParam("username", $args['username']);
    $sth->execute();
    $delete = $sth->fetchAll();
    return $this->response->withJson($delete);
});

/////POINTS/////
//get all points
$app->get('/points', function ($request, $response,$args) {
	$sth = $this->db->prepare("SELECT * FROM Points ORDER BY point_id ASC");
	$sth->execute();
	$users = $sth->fetchAll();
	return $this->response->withJson($users);
});


