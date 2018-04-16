<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Response-Type');
require 'vendor/autoload.php';
require_once 'user/user.php';
require_once 'conn/connection.php';
require_once 'config/credentials.php';
$app = new Slim\App();
$app->get('/hello/{name}', function ($request, $response, $args) {
    return $response->getBody()->write("Hello, " . $args['name']);
});
$app->get('/clients', function ($request, $response, $args) {
    $userList = [];
    array_push($userList,new User('foo', 'bar'));
    array_push($userList, new User('James Doe', 'Encinitas'));
    return $response->getBody()->write(json_encode($userList));
});
$app->get('/clients/{id}', function ($request, $response, $args) {
    $userList = [];
    array_push($userList,new User('foo', 'bar'));
    array_push($userList, new User('James Doe', 'Encinitas'));
    if(is_numeric($args['id'])) {
        //return $response->getBody()->write(print_r($userList));
        $idNum = intval($args['id']);
        if(count($userList) > $idNum) {
            return $response->getBody()->write(json_encode($userList[$idNum]));
        } else {
            return "Error: specified user ID exceeds the number of users";
        }
    } else {
        return "Error: specified user ID was not a number";
    }
});
$app->get('/users', function ($request, $response, $args) {
    $connObj = new Connection($credentials['username'],$credentials['password']);
    $connObj->connectToDb();
    return $response->getBody()->write($connObj->getUserList());
    $connObj->disconnect();
});
// $app->get('/refOrg/{org}/{prog}', function ($request, $response, $args) {
//     var_dump($args['org']);
//     var_dump($args['prog']);
// });
$app->get('/refOrg', function ($request, $response, $args) {
    $reqParams = $request->getQueryParams();
    $connObj = new Connection($credentials['username'],$credentials['password']);
    $connObj->connectToDb();
    $params = array(
        'org' => (isset($reqParams['organization']) ? $reqParams['organization'] : ""),
        'prog' => (isset($reqParams['program']) ? $reqParams['program'] : "")
    );
    $res = $connObj->getRefOrg($params);
    $status = $response->withStatus(200);
    $connObj->disconnect();
    return json_encode($res);
});
$app->patch('/refOrg/update', function ($request, $response, $args) {
    $jsonData = $request->getParsedBody($request);
    $connObj = new Connection($credentials['username'],$credentials['password']);
    $connObj->connectToDb();
    //check if entry exists
    $params = array(
        'org' => (isset($jsonData['referringOrganization']) ? $jsonData['referringOrganization'] : ""),
        'prog' => (isset($jsonData['referringProgOrLocation']) ? $jsonData['referringProgOrLocation'] : ""),
        'orgAddressOne' => (isset($jsonData['orgAddressOne']) ? $jsonData['orgAddressOne'] : ""),
        'orgAddressTwo' => (isset($jsonData['orgAddressTwo']) ? $jsonData['orgAddressTwo'] : ""),
        'orgAddressCity' => (isset($jsonData['orgAddressCity']) ? $jsonData['orgAddressCity'] : ""),
        'orgAddressState' => (isset($jsonData['orgAddressState']) ? $jsonData['orgAddressState'] : ""),
        'orgAddressZip' => (isset($jsonData['orgAddressZip']) ? $jsonData['orgAddressZip'] : "")
    );

    if($connObj->entryExists($params)) {
        //entry exists, call update
        if($connObj->updateRefOrg($params)) {
            $status = $response->withStatus(200);
        } else {
            //send 400
            $status = $response->withStatus(400);
        }
    } else {
        //entry does not exists, call insert
    }
    
});
$app->patch('/login', function ($request, $response, $args) {
    $jsonData = $request->getParsedBody($request);
    $obj = $jsonData;
    //var_dump($obj);
    $uname = $obj['username'];
    $pwdHash = $obj['password'];
    $connObj = new Connection($credentials['username'],$credentials['password']);
    $connObj->connectToDb();
    $params = [
        'username' => $uname,
        'password' => $pwdHash
    ];
    $route = $connObj->executeLoginTimestampUpdate($params);
    if($route == 'user') {
        //send OK 200
        $status = $response->withStatus(200);
        return $status->getBody()->write(json_encode('userLanding'));
    } else if($route == 'admin') {
        $status = $response->withStatus(200);
        return $status->getBody()->write(json_encode('adminLanding'));        
    } else {
        //send error 400
        return $response->withStatus(400);
    }
});

$app->run();