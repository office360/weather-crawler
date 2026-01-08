<?php

require 'vendor/autoload.php';

use Weather\WeatherCrawler;

// 创建实例
$weatherCrawler = new WeatherCrawler();

echo "=== 测试自动定位当前城市天气 ===\n\n";

try {
    // 1. 测试自动获取IP（在命令行环境下会失败，因为没有HTTP请求头）
    echo "1. 测试自动获取IP（命令行环境）：\n";
    try {
        $visitorData = $weatherCrawler->getVisitorData();
        echo "   IP地址：" . $visitorData['ip'] . "\n";
        echo "   所在城市：" . $visitorData['province'] . $visitorData['city'] . "\n";
        echo "   城市代码：" . $visitorData['cityCode'] . "\n\n";
    } catch (Exception $e) {
        echo "   注意：在命令行环境下无法自动获取IP地址\n";
        echo "   错误信息：" . $e->getMessage() . "\n\n";
    }
    
    // 2. 使用默认IP地址测试定位功能
    echo "2. 使用默认IP地址测试定位功能：\n";
    // 使用北京的IP地址作为示例
    $testIp = '114.247.50.2'; // 北京的IP地址
    $visitorData = $weatherCrawler->getVisitorData($testIp);
    echo "   测试IP：" . $visitorData['ip'] . "\n";
    echo "   所在城市：" . $visitorData['province'] . $visitorData['city'] . "\n";
    echo "   城市代码：" . $visitorData['cityCode'] . "\n\n";
    
    // 3. 使用默认IP获取当前天气（模拟自动定位）
    echo "3. 使用IP自动定位获取当前天气：\n";
    $currentWeather = $weatherCrawler->getCurrentWeather(null, null, $testIp);
    echo "   城市：" . $currentWeather['cityName'] . "\n";
    echo "   当前温度：" . $currentWeather['temperature'] . "℃\n";
    echo "   天气状况：" . $currentWeather['weather'] . "\n";
    echo "   风向：" . $currentWeather['windDirection'] . "\n";
    echo "   风力：" . $currentWeather['windPower'] . "\n";
    echo "   湿度：" . $currentWeather['humidity'] . "%\n";
    echo "   更新时间：" . $currentWeather['time'] . "\n\n";
    
    // 4. 使用默认IP获取7日天气预报（模拟自动定位）
    echo "4. 使用IP自动定位获取7日天气预报：\n";
    $sevenDayWeather = $weatherCrawler->get7DayWeather(null, null, $testIp);
    foreach ($sevenDayWeather as $day) {
        echo "   " . $day['date'] . "（" . $day['day'] . "）：" . $day['weather'] . "，" . 
             $day['tempMin'] . "℃~" . $day['tempMax'] . "℃，" . 
             $day['windDay'] . " " . $day['windPowerDay'] . "\n";
    }
    
    echo "\n✓ 自动定位功能测试成功！\n";
    echo "\n注意：在实际Web环境中，系统会自动获取访客的真实IP地址并进行定位，无需手动传入IP参数。\n";
    echo "在命令行环境下测试时，由于没有HTTP请求头，需要手动提供IP地址。\n";
    
} catch (Exception $e) {
    echo "✗ 测试失败：" . $e->getMessage() . "\n";
}

