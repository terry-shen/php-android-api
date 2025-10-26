<?php
// 设置响应头
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT, POST");
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

// 获取请求数据
$request_method = $_SERVER["REQUEST_METHOD"];
$input_data = json_decode(file_get_contents("php://input"));

// 初始化响应数组
$response = array();

try {
    // 检查请求方法
    if ($request_method == 'PUT' || $request_method == 'POST') {
        
        // 验证必需的数据
        if (!empty($input_data->username) && !empty($input_data->email)) {
            
            // 设置用户属性
            $user->username = $input_data->username;
            $user->email = $input_data->email;
            
            // 如果有密码，也设置密码
            if (!empty($input_data->password)) {
                $user->password = $input_data->password;
            }
            
            // 检查要更新的用户是否存在
            if ($user->readByUsername($user->username)) {
                
                // 检查邮箱是否被其他用户使用（排除当前用户）
                if ($user->isEmailUsedByOtherUser()) {
                    http_response_code(400);
                    $response = array(
                        "status" => "error",
                        "message" => "邮箱已被其他用户使用",
                        "timestamp" => date("Y-m-d H:i:s")
                    );
                } else {
                    // 执行更新操作
                    if ($user->updateByUsername()) {
                        http_response_code(200);
                        $response = array(
                            "status" => "success",
                            "message" => "用户信息更新成功，我做的标记",
                            "data" => array(
                                "username" => $user->username,
                                "email" => $user->email,
                                "updated_at" => date("Y-m-d H:i:s")
                            ),
                            "timestamp" => date("Y-m-d H:i:s")
                        );
                    } else {
                        http_response_code(500);
                        $response = array(
                            "status" => "error",
                            "message" => "用户信息更新失败",
                            "timestamp" => date("Y-m-d H:i:s")
                        );
                    }
                }
            } else {
                http_response_code(404);
                $response = array(
                    "status" => "error",
                    "message" => "用户不存在，无法更新",
                    "timestamp" => date("Y-m-d H:i:s")
                );
            }
        } else {
            http_response_code(400);
            $response = array(
                "status" => "error",
                "message" => "缺少必需的数据：username 和 email",
                "required_fields" => array("username", "email"),
                "timestamp" => date("Y-m-d H:i:s")
            );
        }
    } else {
        http_response_code(405);
        $response = array(
            "status" => "error",
            "message" => "不支持的请求方法",
            "allowed_methods" => array("PUT", "POST"),
            "timestamp" => date("Y-m-d H:i:s")
        );
    }

} catch (Exception $e) {
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