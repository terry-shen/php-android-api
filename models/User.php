<?php
class User {
    // 数据库连接和表名
    private $conn;
    private $table_name = "users";

    // 对象属性
    public $id;
    public $username;
    public $password;
    public $email;
    public $created_at;
    public $updated_at;

    // 构造函数，传入数据库连接
    public function __construct($db) {
        $this->conn = $db;
    }

    // 通过ID获取单个用户信息
    public function readOne() {
        try {
            // 验证ID
            if(empty($this->id) || $this->id <= 0) {
                return false;
            }
            
            $query = "SELECT id, username, email, password, created_at, updated_at 
                      FROM " . $this->table_name . " 
                      WHERE id = :id 
                      LIMIT 0,1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
            $stmt->execute();

            // 获取记录数量
            $num = $stmt->rowCount();

            if($num > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // 设置对象属性值
                $this->id = $row['id'];
                $this->username = $row['username'];
                $this->email = $row['email'];
                $this->password = $row['password'];
                $this->created_at = $row['created_at'];
                $this->updated_at = $row['updated_at'];
                
                return true;
            }
            
            return false;
            
        } catch(PDOException $exception) {
            error_log("readOne错误: " . $exception->getMessage());
            return false;
        }
    }

    // 通过用户名获取用户
    public function readByUsername($username) {
        try {
            // 清理和验证用户名
            $username = htmlspecialchars(strip_tags(trim($username)));
            if(empty($username)) {
                return false;
            }
            
            $query = "SELECT id, username, email, password, created_at, updated_at 
                      FROM " . $this->table_name . " 
                      WHERE username = :username 
                      LIMIT 0,1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":username", $username);
            $stmt->execute();

            $num = $stmt->rowCount();

            if($num > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $this->id = $row['id'];
                $this->username = $row['username'];
                $this->email = $row['email'];
                $this->password = $row['password'];
                $this->created_at = $row['created_at'];
                $this->updated_at = $row['updated_at'];
                
                return true;
            }
            
            return false;
            
        } catch(PDOException $exception) {
            error_log("readByUsername错误: " . $exception->getMessage());
            return false;
        }
    }

    // 通过邮箱获取用户
    public function readByEmail($email) {
        try {
            // 清理和验证邮箱
            $email = htmlspecialchars(strip_tags(trim($email)));
            if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return false;
            }
            
            $query = "SELECT id, username, email, password, created_at, updated_at 
                      FROM " . $this->table_name . " 
                      WHERE email = :email 
                      LIMIT 0,1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->execute();

            $num = $stmt->rowCount();

            if($num > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $this->id = $row['id'];
                $this->username = $row['username'];
                $this->email = $row['email'];
                $this->password = $row['password'];
                $this->created_at = $row['created_at'];
                $this->updated_at = $row['updated_at'];
                
                return true;
            }
            
            return false;
            
        } catch(PDOException $exception) {
            error_log("readByEmail错误: " . $exception->getMessage());
            return false;
        }
    }

    // 获取所有用户（分页支持）
    public function readAll($page = 1, $records_per_page = 10) {
        try {
            // 计算分页
            $from_record_num = ($records_per_page * $page) - $records_per_page;
            
            $query = "SELECT id, username, email, created_at, updated_at 
                      FROM " . $this->table_name . " 
                      ORDER BY created_at DESC 
                      LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":limit", $records_per_page, PDO::PARAM_INT);
            $stmt->bindParam(":offset", $from_record_num, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt;
            
        } catch(PDOException $exception) {
            error_log("readAll错误: " . $exception->getMessage());
            return false;
        }
    }

    // 创建新用户
    public function create() {
        try {
            // 清理数据
            $this->username = htmlspecialchars(strip_tags($this->username));
            $this->email = htmlspecialchars(strip_tags($this->email));
            $this->password = htmlspecialchars(strip_tags($this->password));
            
            // 验证必填字段
            if(empty($this->username) || empty($this->email) || empty($this->password)) {
                return false;
            }
            
            // 检查用户名和邮箱是否已存在
            if($this->usernameExists() || $this->emailExists()) {
                return false;
            }
            
            // 加密密码
            $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);
            
            $query = "INSERT INTO " . $this->table_name . " 
                      SET username=:username, email=:email, password=:password, 
                          created_at=NOW(), updated_at=NOW()";

            $stmt = $this->conn->prepare($query);

            // 绑定参数
            $stmt->bindParam(":username", $this->username);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":password", $hashed_password);

            if($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
            
            return false;
            
        } catch(PDOException $exception) {
            error_log("create错误: " . $exception->getMessage());
            return false;
        }
    }

    function writeLog($message) {
        $logFile = '/tmp/logs/php_debug.log';        
        try {            
            // 检查目录
            $logDir = dirname($logFile);
            
            // 如果目录不存在，尝试创建
            if (!is_dir($logDir)) {
                if (mkdir($logDir, 0755, true)) {
                    echo "✓ 目录创建成功<br>";
                } else {
                    return false;
                }
            }            
            
            // 检查文件是否存在            
            if (file_exists($logFile)) {
                echo "文件可写: " . (is_writable($logFile) ? '是' : '否') . "<br>";
            }
            
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[$timestamp] $message\n";
                        
            // 尝试写入
            $result = file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
            
            if ($result === false) {                
                return false;
            } else {
                return true;
            }
            
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 更新用户信息 - 支持根据ID或username更新
     * @param string $identifierType 标识符类型：'id' 或 'username'
     * @return bool
     */
    public function update($identifierType = 'id') {
        try {
            // // 清理数据
            // $this->username = htmlspecialchars(strip_tags($this->username));
            // $this->email = "noemail@noemail.com";
            writeLog("清理前email: " . $this->email);
            $this->email = htmlspecialchars(strip_tags($this->email));
            writeLog("清理后email: " . $this->email);
            
            // 根据标识符类型验证
            if ($identifierType === 'id') {
                $this->id = htmlspecialchars(strip_tags($this->id));
                if (empty($this->id) || $this->id <= 0) {
                    return false;
                }
                $identifier = $this->id;
            } elseif ($identifierType === 'username') {
                $this->username = htmlspecialchars(strip_tags($this->username));
                if (empty($this->username)) {
                    return false;
                }
                $identifier = $this->username;
            } else {
                return false; // 无效的标识符类型
            }
            
            // 构建更新查询
            $query = "UPDATE " . $this->table_name . " 
                      SET email = :email, updated_at = NOW()";
            
             // 如果提供了新密码，则更新密码
            if (!empty($this->password)) {
                $this->password = htmlspecialchars(strip_tags($this->password));
                $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);
                $query .= ", password = :password";
            }
            
            // 根据标识符类型添加WHERE条件
            if ($identifierType === 'id') {
                $query .= " WHERE id = :identifier";
            } else {
                $query .= " WHERE username = :identifier";
            }

            writeLog("连接后的query语句: " . $query);
            $stmt = $this->conn->prepare($query);

            // 绑定参数
            $stmt->bindParam(":email", $this->email);
            writeLog("绑定的email: " . $this->email);
            
            if (!empty($this->password)) {
                $stmt->bindParam(":password", $hashed_password);
            }
            
            // 绑定标识符参数
            $stmt->bindParam(":identifier", $identifier);

            return $stmt->execute();
            
        } catch(PDOException $exception) {
            error_log("update错误: " . $exception->getMessage());
            return false;
        }
    }

    /**
     * 检查邮箱是否被其他用户使用（排除当前用户）
     * @return bool
     */
    public function isEmailUsedByOtherUser() {
        try {
            $query = "SELECT id FROM " . $this->table_name . " 
                    WHERE email = :email AND username != :username 
                    LIMIT 0,1";
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":username", $this->username);
            $stmt->execute();
            
            $num = $stmt->rowCount();
            return $num > 0;
            
        } catch(PDOException $exception) {
            error_log("isEmailUsedByOtherUser错误: " . $exception->getMessage());
            return false;
        }
}

    /**
     * 根据用户名更新用户信息（便捷方法）
     * @return bool
     */
    public function updateByUsername() {
        return $this->update('username');
    }

    /**
     * 根据ID更新用户信息（便捷方法）
     * @return bool
     */
    public function updateById() {
        return $this->update('id');
    }

    // 删除用户
    public function delete() {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            
            $this->id = htmlspecialchars(strip_tags($this->id));
            $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
            
            return $stmt->execute();
            
        } catch(PDOException $exception) {
            error_log("delete错误: " . $exception->getMessage());
            return false;
        }
    }

    // 检查用户名是否存在
    public function usernameExists() {
        try {
            $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username LIMIT 0,1";
            $stmt = $this->conn->prepare($query);
            
            $this->username = htmlspecialchars(strip_tags($this->username));
            $stmt->bindParam(":username", $this->username);
            $stmt->execute();
            
            $num = $stmt->rowCount();
            return $num > 0;
            
        } catch(PDOException $exception) {
            error_log("usernameExists错误: " . $exception->getMessage());
            return false;
        }
    }

    // 检查邮箱是否存在
    public function emailExists() {
        try {
            $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email LIMIT 0,1";
            $stmt = $this->conn->prepare($query);
            
            $this->email = htmlspecialchars(strip_tags($this->email));
            $stmt->bindParam(":email", $this->email);
            $stmt->execute();
            
            $num = $stmt->rowCount();
            return $num > 0;
            
        } catch(PDOException $exception) {
            error_log("emailExists错误: " . $exception->getMessage());
            return false;
        }
    }

    // 验证用户登录
    public function login() {
        try {
            $query = "SELECT id, username, email, password FROM " . $this->table_name . " 
                      WHERE username = :username LIMIT 0,1";
            $stmt = $this->conn->prepare($query);
            
            $this->username = htmlspecialchars(strip_tags($this->username));
            $stmt->bindParam(":username", $this->username);
            $stmt->execute();
            
            $num = $stmt->rowCount();
            
            if($num > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // 验证密码
                if(password_verify($this->password, $row['password'])) {
                    $this->id = $row['id'];
                    $this->email = $row['email'];
                    return true;
                }
            }
            
            return false;
            
        } catch(PDOException $exception) {
            error_log("login错误: " . $exception->getMessage());
            return false;
        }
    }

    // 获取用户总数
    public function countAll() {
        try {
            $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['total'];
            
        } catch(PDOException $exception) {
            error_log("countAll错误: " . $exception->getMessage());
            return 0;
        }
    }
}
?>