<?php

require 'vendor/autoload.php';

use Weather\WeatherCrawler;

// 创建实例
$weatherCrawler = WeatherCrawler::create();

echo "=== office360/weather-crawler 示例 ===\n\n";

try {
    echo "1. 获取访客数据：
";
    $visitorData = $weatherCrawler->getVisitorData(null);
    echo "   IP: {$visitorData['ip']}
";
    echo "   所在城市: {$visitorData['province']} {$visitorData['city']} {$visitorData['district']}

";
} catch (Exception $e) {
    echo "1. 获取访客数据失败: {$e->getMessage()}\n\n";
}

try {
    echo "2. 根据城市代码获取基础天气（北京）：\n";
    $basicWeather = $weatherCrawler->forCityCode('101010100')->getWeather('basic');
    echo "   城市: {$basicWeather['cityName']}\n";
    echo "   温度: {$basicWeather['temperature']}℃\n";
    echo "   天气: {$basicWeather['weather']}\n";
    echo "   风向: {$basicWeather['windDirection']}\n";
    echo "   风力: {$basicWeather['windPower']}\n\n";
} catch (Exception $e) {
    echo "2. 获取基础天气失败: {$e->getMessage()}\n\n";
}

try {
    echo "3. 根据城市名称获取7日天气（上海）：\n";
    $sevenDayWeather = $weatherCrawler->forCity('上海')->getWeather('7day');
    foreach ($sevenDayWeather as $day) {
        echo "   {$day['date']} ({$day['day']}): {$day['weather']}, {$day['tempMin']}℃~{$day['tempMax']}℃\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "3. 获取7日天气失败: {$e->getMessage()}\n\n";
}

try {
    echo "4. 获取逐小时天气数据（广州）：\n";
    $hourlyWeather = $weatherCrawler->forCity('广州')->getWeather('hourly', 5);
    // 显示前5个小时
    foreach ($hourlyWeather as $hour) {
        echo "   {$hour['time']}: {$hour['temperature']}℃, {$hour['weather']}\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "4. 获取逐小时天气失败: {$e->getMessage()}\n\n";
}

try {
    echo "5. 获取综合天气数据（深圳）：\n";
    $comprehensiveWeather = $weatherCrawler->forCity('深圳')->getWeather('comprehensive', 5);
    echo "   当前天气: {$comprehensiveWeather['detail']['airQuality']['temperature']}℃, {$comprehensiveWeather['detail']['airQuality']['weather']}\n";
    echo "   7日预报数量: " . count($comprehensiveWeather['7day']) . "天\n";
    echo "   15日预报数量: " . count($comprehensiveWeather['15day']) . "天\n";
    echo "   逐小时预报数量: " . count($comprehensiveWeather['hourly']) . "小时\n\n";
} catch (Exception $e) {
    echo "5. 获取综合天气失败: {$e->getMessage()}\n\n";
}

try {
    echo "6. 根据经纬度获取天气数据（北京坐标）：\n";
    $weatherByCoords = $weatherCrawler->forCoordinates(39.9042, 116.4074)->getWeather('basic');
    echo "   城市: {$weatherByCoords['cityName']}\n";
    echo "   温度: {$weatherByCoords['temperature']}℃\n";
    echo "   天气: {$weatherByCoords['weather']}\n\n";
} catch (Exception $e) {
    echo "6. 根据经纬度获取天气失败: {$e->getMessage()}\n\n";
}

echo "=== 示例结束 ===\n";