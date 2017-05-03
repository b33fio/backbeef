<?php

/* utility function used to get user_id of next user to post in a debate
 *
 * row number - 0 indexed (number of points/2  - 1)
 *
 * cases:
 * 1) length 0 = proponent
 * 2) length odd and row number odd = proponent
 * 3) length odd and row number even = opponent
 * 4) length even and row number odd = opponent
 * 5) length even and row number even = proponent
 *
 *
 */
function getNextPoster($debate_id, $db) {
    $sql = "select * from Points where point_debate=:point_debate";
    $query = $db->prepare($sql);
    $query->bindParam("point_debate", $debate_id);
    $query->execute();
    $results = $query->fetchAll();
    $length = count($results);

    $sql = "select proponent_id, opponent_id from Debates where debate_id=:debate_id";
    $query = $db->prepare($sql);
    $query->bindParam("debate_id", $debate_id);
    $query->execute();
    $result = $query->fetchObject();
    $proponent_id = $result->proponent_id;
    $opponent_id = $result->opponent_id;

    if ($length == 0) {
        return $proponent_id;
    } else if ($length % 2 != 0) {
        if (floor($length / 2) % 2 != 0) {
            return $proponent_id;
        } else {
            return $opponent_id;
        }
    } else {
        if (floor($length / 2) % 2 != 0) {
            return $opponent_id;
        } else {
            return $proponent_id;
        }
    }
}

//add a Channel -> only for admins
$app->post('/channel', function ($request, $response) {
    $input = $request->getParsedBody();
    $sql = "INSERT INTO Channels (channel_name, channel_created) VALUES (:channel_name, NOW())";
    $sth = $this->db->prepare($sql);
    $sth->bindParam("channel_name", $input['channel_name']);
    $sth->execute();
    $done = array('successful'=>true, 'channel_name'=>$input['channel_name']);
    return $this->response->withJson($done);
});

//add a Debate
$app->post('/debate', function ($request, $response) {
    $input = $request->getParsedBody();

    $token = $input['jwt'];
    try {
        $decodedJWT = decodeJWT($token);
    } catch (\Exception $e) {
        return $this->response->withJson(array('msg'=>'error: could not decode'))->withStatus(403);
    }

    $proponent_id = json_decode($decodedJWT->username)->user_id;
    $proponent_name = json_decode($decodedJWT->username)->username;

    // validate debate ID
    $sql = "select * from Channels where channel_id=:channel_id";
    $sth = $this->db->prepare($sql);
    $sth->bindParam("channel_id", $input['debate_channel']);
    $sth->execute();
    $result = $sth->fetchAll();

    if (!$result) {
        return $this->response->withJson(array('msg'=>'error: invalid channel'))->withStatus(403);
    }

    $sql = "INSERT INTO Debates (debate_name, debate_channel, debate_created, proponent_id, proponent_name ) VALUES (:debate_name, :debate_channel, NOW(), :proponent_id, :proponent_name)";
    $sth = $this->db->prepare($sql);
    $sth->bindParam("debate_name", $input['debate_name']);
    $sth->bindParam("debate_channel", $input['debate_channel']);
    $sth->bindParam("proponent_id", $proponent_id);
    $sth->bindParam("proponent_name", $proponent_name);
    
    $sth->execute();

    $debateId = $this->db->lastInsertId();

    $done = array('successful'=>true, 'debate_name'=>$input['debate_name'], 'debate_channel'=>$input['debate_channel'],
    'proponent_id'=>$proponent_id, 'proponent_name'=>$proponent_name, 'debate_id'=>$debateId);
    return $this->response->withJson($done);
});



// join debate
$app->post('/debate/join/[{id}]', function ($request, $response, $args) {
    $input = $request->getParsedBody();

    $token = $input['jwt'];
    try {
        $decodedJWT = decodeJWT($token);
    } catch (\Exception $e) {
        return $this->response->withJson(array('msg'=>'error: could not decode'))->withStatus(403);
    }

    $opponent_id = json_decode($decodedJWT->username)->user_id;
    $opponent_name = json_decode($decodedJWT->username)->username;

    // validate that proponent is not opponent ID
    $sql = "select proponent_id from Debates where debate_id=:debate_id";
    $sth = $this->db->prepare($sql);
    $sth->bindParam("debate_id", $args['id']);
    $sth->execute();
    $result = $sth->fetchObject();

    if ($result->proponent_id == $opponent_id) {
        return $this->response->withJson(array('msg'=>'error: opponent is proponent'))->withStatus(403);
    }

    // TODO: validate that there is no opponent

    $sql = "UPDATE Debates set opponent_name=:opponent_name, opponent_id=:opponent_id where debate_id=:debate_id";
    $sth = $this->db->prepare($sql);
    $sth->bindParam("opponent_name", $opponent_name);
    $sth->bindParam("opponent_id", $opponent_id);
    $sth->bindParam("debate_id", $args['id']);
    $sth->execute();


    /*
    $done = array('successful'=>true, 'debate_name'=>$input['debate_name'], 'debate_channel'=>$input['debate_channel'],
    'proponent_id'=>$proponent_id, 'proponent_name'=>$proponent_name, 'debate_id'=>$debateId);
     */
    $done = array('successful'=>true);

    return $this->response->withJson($done);
});


//add a point
//if Poster id is of proponent, auto type to claim
$app->post('/point', function ($request, $response) {
    $input = $request->getParsedBody();

    $token = $input['jwt'];
    try {
        $decodedJWT = decodeJWT($token);
    } catch (\Exception $e) {
        return $this->response->withJson(array('msg'=>'error: could not decode'))->withStatus(403);
    }

    $poster_id = json_decode($decodedJWT->username)->user_id;

    // return 403 if current user isn't the next poster
    if (getNextPoster($input['debate_id'], $this->db) != $poster_id) {
        return $this->response->withJson(array('msg'=>'error: not your turn'))->withStatus(403);
    }

    //TODO: update point type
    $sql = "INSERT INTO Points (point_text, point_debate, point_created, point_type, poster_id) VALUES (:point_text, :point_debate, NOW(), 'claim', :poster_id)";
    $sth = $this->db->prepare($sql);
    $sth->bindParam("point_text", $input['point_text']);
    $sth->bindParam("point_debate", $input['debate_id']);

    //$sth->bindParam("point_type", "claim");
    $sth->bindParam("poster_id", $poster_id);

    $sth->execute();
    $done = array('successful'=>true, 'point_text'=>$input['point_text'], 'point_debate'=>$input['debate_id'], 'point_type'=>'claim', 'poster_id'=>$poster_id);

    return $this->response->withJson($done);
});



