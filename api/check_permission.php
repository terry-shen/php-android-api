<?php
// check_permission.php
header('Content-Type: text/plain');
echo "=== 权限检查 ===\n";

$logFile = __DIR__ . '/debug.log';
$logDir = dirname($logFile);

echo "当前目录: " . __DIR__ . "\n";
echo "日志文件路径: " . $logFile . "\n";
echo "日志目录: " . $logDir . "\n\n";

// 检查目录权限
echo "目录存在: " . (file_exists($logDir) ? '是' : '否') . "\n";
echo "目录可读: " . (is_readable($logDir) ? '是' : '否') . "\n";
echo "目录可写: " . (is_writable($logDir) ? '是' : '否') . "\n";
echo "目录可执行: " . (is_executable($logDir) ? '是' : '否') . "\n\n";

// 检查文件权限（如果存在）
if (file_exists($logFile)) {
    echo "文件存在: 是\n";
    echo "文件可读: " . (is_readable($logFile) ? '是' : '否') . "\n";
    echo "文件可写: " . (is_writable($logFile) ? '是' : '否') . "\n";
} else {
    echo "文件存在: 否\n";
    echo "尝试创建文件...\n";
    
    $testContent = "权限测试 " . date('Y-m-d H:i:s') . "\n";
    $result = file_put_contents($logFile, $testContent);
    
    if ($result !== false) {
        echo "✓ 文件创建成功\n";
        echo "文件大小: " . filesize($logFile) . " 字节\n";
        unlink($logFile); // 删除测试文件
    } else {
        echo "✗ 文件创建失败\n";
    }
}

// 检查运行用户
echo "\n运行用户: " . (function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : '未知') . "\n";

// 临时目录权限
$tempDir = sys_get_temp_dir();
echo "系统临时目录: " . $tempDir . "\n";
echo "临时目录可写: " . (is_writable($tempDir) ? '是' : '否') . "\n";
?>