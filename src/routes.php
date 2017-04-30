<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);

/////MAILGUN/////

$app->get('/token/test/[{username}]', function($request, $response, $args) {
    $username = $args['username'];
    //$email = "thisisisk@gmail.com";
    $fN = "Kevin";
    $lN = "Queenan";
    sendVerification($username, $email, $fN, $lN);
    print_r("email sent!");
});

//mail confirmation endpoint
//listen and decode jwt appended to url
//alter user table accordingly
//if decoded jwt matches valid username -> verified === 1
$app->get('/token/[{token}]', function ($request, $response, $args) {
    $pdo = $this->db;
    $payload = decodeJWT($args['token']);
    $username = $payload->username;
    print_r($username);
    //need to update db schema & tables
    //$sth = $pdo->prepare("UPDATE Users Set verified=1 Where username=:username");
    //$sth->bindParam("username", $username);
    //$sth->execute();
    //return success code
    //return $response->withStatus(200);
});

/////CHANNELS/////

//get all channels
$app->get('/channels', function ($request, $response, $args) {
    $sth = $this->db->prepare("SELECT * FROM Channels ORDER BY channel_id");
    $sth->execute();
    $chan = $sth->fetchAll();
    return $this->response->withJson($chan);
});

//get channel by id
//list all debates in channel by id
$app->get('/channels/[{id}]', function ($request, $response, $args) {
    $sth = $this->db->prepare("SELECT debate_id,debate_name,debate_channel,debate_created,debate_updated,proponent_id,opponent_id,debate_state,proponent_username,opponent_username FROM Debates as D left join (select username as proponent_username,user_id from Users) as Up on D.proponent_id=Up.user_id left join (select username as opponent_username,user_id from Users) as Uo on D.opponent_id=Uo.user_id where D.debate_channel=:id");
    $sth->bindParam("id", $args['id']);
    $sth->execute();
    $chan = $sth->fetchObject();
    return $this->response->withJson(array($chan));
});

//search channels by keyword
$app->get('/channels/search/[{query}]', function ($request, $response, $args) {
    $sth = $this->db->prepare("SELECT * FROM Channels WHERE UPPER(channel_name) LIKE :query ORDER BY channel_name");
    $query = "%".$args['query']."%";
    $sth->bindParam("query", $query);
    $sth->execute();
    $chan = $sth->fetchAll();
    return $this->response->withJson($chan);
});

/////DEBATES/////

//get all debates
$app->get('/debates', function ($request, $response, $args) {
	$sth = $this->db->prepare("SELECT * FROM Debates ORDER BY debate_name ASC");
	$sth->execute();
	$debates = $sth->fetchAll();
	return $this->response->withJson($debates);
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
    // SELECT Debates.*, Users.username, Users.user_id from Debates NATURAL JOIN Users Where debate_id = 1 and proponent_id = user_id or opponent_id = user_id;
	// previous SQL query:
    // $sth = $this->db->prepare("SELECT * FROM Debates WHERE debate_id=:id");
	// $sth->bindParam("id", $args['id']);
	// $sth->execute();
	// $debates = $sth->fetchObject();
    // $sth = $this->db->prepare("SELECT * From Points WHERE point_debate=:id");
    // $sth->bindParam("id", $args['id']);
    // $sth->execute();
    // $points = $sth->fetchAll();
    // $data = array("debate" => $debates, "points" => $points);
    $sth = $this->db->prepare("SELECT Debates.*, Users.username, Users.user_id from Debates NATURAL JOIN Users Where debate_id =:id and proponent_id = user_id or opponent_id = user_id");
    $sth->bindParam("id", $args['id']);
    $sth->execute();
    $debates = $sth->fetchAll();
	return $this->response->withJson($debates);
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

?>
