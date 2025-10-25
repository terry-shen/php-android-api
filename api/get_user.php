<?php
// 设置响应头
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// 包含必要的文件
include_once '../config/database.php';
include_once '../models/User.php';

// 初始化数据库连接
$database = new Database();
$db = $database->getConnection();

// 创建User对象
$user = new User($db);

// 初始化响应数组
$response = array();

try {
    // 检查查询参数
    if(isset($_GET['id']) && !empty($_GET['id'])) {
        // 通过ID查询
        $user->id = intval($_GET['id']);
        $result = $user->readOne();
        $query_type = "id";
        $query_value = $user->id;
        
    } elseif(isset($_GET['username']) && !empty($_GET['username'])) {
        // 通过用户名查询
        $result = $user->readByUsername($_GET['username']);
        $query_type = "username";
        $query_value = $_GET['username'];
        
    } elseif(isset($_GET['email']) && !empty($_GET['email'])) {
        // 通过邮箱查询
        $result = $user->readByEmail($_GET['email']);
        $query_type = "email";
        $query_value = $_GET['email'];
        
    } else {
        // 没有提供查询参数
        http_response_code(400);
        $response = array(
            "status" => "error",
            "message" => "请提供查询参数：id、username 或 email",
            "available_parameters" => array("id", "username", "email")
        );
        echo json_encode($response);
        exit();
    }

    if($result) {
        // 查询成功，返回用户信息
        $response = array(
            "status" => "success",
            "message" => "用户查询成功",
            "query" => array(
                "type" => $query_type,
                "value" => $query_value
            ),
            "data" => array(
                "id" => $user->id,
                "username" => $user->username,
                "email" => $user->email,
                "created_at" => $user->created_at,
                "updated_at" => $user->updated_at
            ),
            "timestamp" => date("Y-m-d H:i:s")
        );
        http_response_code(200);
    } else {
        // 用户不存在
        $response = array(
            "status" => "error",
            "message" => "用户不存在",
            "query" => array(
                "type" => $query_type,
                "value" => $query_value
            ),
            "timestamp" => date("Y-m-d H:i:s")
        );
        http_response_code(404);
    }

} catch (Exception $e) {
    // 服务器错误
    http_response_code(500);
    $response = array(
        "status" => "error",
        "message" => "服务器内部错误: " . $e->getMessage(),
        "timestamp" => date("Y-m-d H:i:s")
    );
}

// 输出JSON响应
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>