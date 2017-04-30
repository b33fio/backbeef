<?php

//add a Channel
$app->post('/channel', function ($request, $response) {
    $input = $request->getParsedBody();
    $sql = "INSERT INTO Channels (channel_name, channel_created) VALUES (:channel_name, NOW())";
    $sth = $this->db->prepare($sql);
    $sth->bindParam("channel_name", $input['channel_name']);
    $sth->execute();
    $done = json_encode(array('successful'=>true, 'channel_name'=>$input['channel_name']));
    return $done;
});

//add a Debate
$app->post('/debate', function ($request, $response) {
    $input = $request->getParsedBody();
    $sql = "INSERT INTO Debates (debate_name, debate_channel, debate_created, proponent_id, opponent_id, debate_state) VALUES (:debate_name, :debate_channel, NOW(), :proponent_id, null, 'pending')";
    $sth = $this->db->prepare($sql);
    $sth->bindParam("debate_name", $input['debate_name']);
    $sth->bindParam("debate_channel", $input['debate_channel']);
    $sth->bindParam("proponent_id", $input['proponent_id']);
    
    $sth->execute();
    $done = json_encode(array('successful'=>true, 'debate_name'=>$input['debate_name'], 'debate_channel'=>$input['debate_channel'],
    'proponent_id'=>$input['proponent_id']));
    return $done;
});

//add a point
//if Poster id is of proponent, auto type to claim
$app->post('/point', function ($request, $response) {
    $input = $request->getParsedBody();
    $sql = "INSERT INTO Points (point_name, point_debate, point_created, point_type, poster_id) VALUES (:point_name, :point_debate, NOW(), :point_type, :poster_id)";
    $sth = $this->db->prepare($sql);
    $sth->bindParam("point_name", $input['point_name']);
    $sth->bindParam("point_debate", $input['point_debate']);
    $sth->bindParam("point_type", $input['point_type']);
    $sth->bindParam("poster_id", $input['poster_id']);

    $sth->execute();
    $done = json_encode(array('successful'=>true, 'point_name'=>$input['point_name'], 'point_debate'=>$input['point_debate'], 'point_type'=>$input['point_type'], 'poster_id'=>$input['poster_id']));
    return $done;
});

?>