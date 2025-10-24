<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/User.php';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

$data = json_decode(file_get_contents("php://input"));

if(
    !empty($data->username) &&
    !empty($data->email) &&
    !empty($data->password)
) {
    $user->username = $data->username;
    $user->email = $data->email;
    $user->password = $data->password;

    if($user->create()) {
        http_response_code(201);
        echo json_encode(array("message" => "用户创建成功"));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "无法创建用户"));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "数据不完整"));
}
?>