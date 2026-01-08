<?php

require 'vendor/autoload.php';

use Weather\WeatherCrawler;

// 创建实例
$weatherCrawler = new WeatherCrawler();

echo "=== office360/weather-crawler 示例 ===\n\n";

try {
    echo "1. 获取访客数据：\n";
    $visitorData = $weatherCrawler->getVisitorData();
    echo "   IP: {$visitorData['ip']}\n";
    echo "   所在城市: {$visitorData['province']} {$visitorData['city']} {$visitorData['district']}\n\n";
} catch (Exception $e) {
    echo "1. 获取访客数据失败: {$e->getMessage()}\n\n";
}

try {
    echo "2. 根据城市代码获取当前天气（北京）：\n";
    $currentWeather = $weatherCrawler->getCurrentWeather('101010100');
    echo "   城市: {$currentWeather['cityCode']}\n";
    echo "   温度: {$currentWeather['temperature']}℃\n";
    echo "   天气: {$currentWeather['weather']}\n";
    echo "   风向: {$currentWeather['windDirection']}\n";
    echo "   风力: {$currentWeather['windPower']}\n";
    echo "   湿度: {$currentWeather['humidity']}\n";
    echo "   更新时间: {$currentWeather['time']}\n\n";
} catch (Exception $e) {
    echo "2. 获取当前天气失败: {$e->getMessage()}\n\n";
}

try {
    echo "3. 根据城市名称获取7日天气（上海）：\n";
    $sevenDayWeather = $weatherCrawler->get7DayWeatherData(null, '上海');
    foreach ($sevenDayWeather as $day) {
        echo "   {$day['date']} ({$day['day']}): {$day['weather']}, {$day['tempMin']}℃~{$day['tempMax']}℃\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "3. 获取7日天气失败: {$e->getMessage()}\n\n";
}

try {
    echo "4. 获取逐小时天气数据（广州）：\n";
    $hourlyWeather = $weatherCrawler->getHourlyWeatherData(null, '广州');
    // 只显示前5个小时
    for ($i = 0; $i < min(5, count($hourlyWeather)); $i++) {
        $hour = $hourlyWeather[$i];
        echo "   {$hour['time']}: {$hour['temperature']}℃, {$hour['weather']}\n";
    }
    if (count($hourlyWeather) > 5) {
        echo "   ... 共显示前5个小时，总共有" . count($hourlyWeather) . "个小时数据\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "4. 获取逐小时天气失败: {$e->getMessage()}\n\n";
}

try {
    echo "5. 获取综合天气数据（深圳）：\n";
    $allWeather = $weatherCrawler->getAllWeatherData(null, '深圳');
    echo "   当前温度: {$allWeather['currentWeather']['temperature']}℃, {$allWeather['currentWeather']['weather']}\n";
    echo "   7日预报数量: " . count($allWeather['fifteenDayWeather']) . "天\n";
    echo "   逐小时预报数量: " . count($allWeather['hourlyWeather']) . "小时\n\n";
} catch (Exception $e) {
    echo "5. 获取综合天气失败: {$e->getMessage()}\n\n";
}

echo "=== 示例结束 ===\n";