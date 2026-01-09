<?php
/**
 * 经纬度测试getWeather所有type类型
 * 使用北京经纬度(39.9042, 116.4074)测试所有天气数据类型
 * 并将数据记录到日志文件
 */

spl_autoload_register(function ($className) {
    $className = str_replace('\\', '/', $className);
    require_once __DIR__ . '/../src/' . $className . '.php';
});

use Weather\WeatherCrawler;
use Weather\Exception\WeatherException;

// 日志目录
$logDir = __DIR__ . '/logs/';

// 创建日志目录（如果不存在）
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// 日志记录函数
function logToFile($filename, $data, $type) {
    global $logDir;
    
    $timestamp = date('Y-m-d H:i:s');
    $logHeader = "========================================\n";
    $logHeader .= "经纬度测试天气数据 - type: {$type}\n";
    $logHeader .= "测试时间: {$timestamp}\n";
    $logHeader .= "========================================\n\n";
    
    $logContent = $logHeader;
    
    // 格式化数据
    if (is_array($data)) {
        ob_start();
        print_r($data);
        $logContent .= ob_get_clean();
    } else {
        $logContent .= $data;
    }
    
    $logContent .= "\n\n========================================\n\n";
    
    // 写入日志文件
    file_put_contents($logDir . $filename, $logContent, FILE_APPEND);
    
    echo "数据已记录到: {$logDir}{$filename}\n";
}

try {
    echo "=== 经纬度测试getWeather所有type类型 ===\n";
    
    // 创建WeatherCrawler实例
    $weatherCrawler = new WeatherCrawler();
    
    // 北京的经纬度
    $lat = 39.9042;  // 纬度
    $lng = 116.4074; // 经度
    
    echo "
测试经纬度: 纬度 {$lat}, 经度 {$lng}\n";
    echo "对应的城市: 北京\n";
    
    // 测试所有type类型
    $types = ['basic', 'detail', '7day', 'hourly', '15day', 'comprehensive'];
    
    foreach ($types as $type) {
        echo "\n\n========================================\n";
        echo "测试type='{$type}'\n";
        echo "========================================\n";
        echo "正在获取{$type}天气数据...\n";
        
        $data = $weatherCrawler->forCoordinates($lat, $lng)->getWeather($type);
        
        echo "\n获取成功！返回数据: " . PHP_EOL;
        
        // 如果是数组，使用print_r展示完整数据
        if (is_array($data)) {
            print_r($data);
        } else {
            echo $data . PHP_EOL;
        }
        
        echo "\n数据条目数: " . count($data) . PHP_EOL;
        
        // 记录到日志文件
        $logFile = "weather_data_{$type}_" . date('Ymd_His') . ".log";
        logToFile($logFile, $data, $type);
    }
    
    echo "\n\n=== 所有测试完成 ===\n";
    echo "所有数据已记录到日志目录: {$logDir}\n";
    
} catch (WeatherException $e) {
    echo "天气获取失败: " . $e->getMessage() . PHP_EOL;
    // 记录错误日志
    file_put_contents($logDir . "error_log_" . date('Ymd_His') . ".log", 
        date('Y-m-d H:i:s') . " - 天气获取失败: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
} catch (Exception $e) {
    echo "测试失败: " . $e->getMessage() . PHP_EOL;
    echo "错误跟踪: " . $e->getTraceAsString() . PHP_EOL;
    // 记录错误日志
    file_put_contents($logDir . "error_log_" . date('Ymd_His') . ".log", 
        date('Y-m-d H:i:s') . " - 测试失败: " . $e->getMessage() . PHP_EOL . 
        "错误跟踪: " . $e->getTraceAsString() . PHP_EOL, FILE_APPEND);
}