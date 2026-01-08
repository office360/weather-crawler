<?php

require_once __DIR__ . '/vendor/autoload.php';

use Weather\WeatherCrawler;

// 创建天气爬虫实例
$weatherCrawler = new WeatherCrawler();

echo "=== 测试通过城市名称获取城市ID ===\n";
try {
    // 测试获取北京的城市ID
    $cityName = '北京';
    $cityCode = $weatherCrawler->getCityCodeByName($cityName);
    echo "城市名称: {$cityName}, 城市ID: {$cityCode}\n";
    
    // 使用城市ID获取天气数据
    echo "正在获取 {$cityName} 的当前天气...\n";
    $currentWeather = $weatherCrawler->getCurrentWeather($cityCode);
    echo "当前天气: {$currentWeather['weather']}, 温度: {$currentWeather['temperature']}°C\n";
    
    echo "正在获取 {$cityName} 的7日天气预报...\n";
    $sevenDayWeather = $weatherCrawler->get7DayWeather($cityCode);
    echo "未来7天天气预报条数: " . count($sevenDayWeather) . "条\n";
    
    echo "正在获取 {$cityName} 的逐小时天气预报...\n";
    $hourlyWeather = $weatherCrawler->getHourlyWeather($cityCode);
    echo "逐小时天气预报条数: " . count($hourlyWeather) . "条\n";
    
    echo "正在获取 {$cityName} 的全部天气数据...\n";
    $allWeather = $weatherCrawler->getAllWeather($cityCode);
    echo "全部天气数据获取成功\n";
    echo "\n";
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n\n";
}

echo "=== 测试通过经纬度获取城市ID ===\n";
try {
    // 测试获取北京的经纬度对应的城市ID（北京大致经纬度：39.9042, 116.4074）
    $latitude = 39.9042;
    $longitude = 116.4074;
    $cityCode = $weatherCrawler->getCityCodeByCoordinates($latitude, $longitude);
    echo "经纬度: {$latitude}, {$longitude}, 城市ID: {$cityCode}\n";
    
    // 使用城市ID获取天气数据
    echo "正在获取该经纬度位置的当前天气...\n";
    $currentWeather = $weatherCrawler->getCurrentWeather($cityCode);
    echo "当前天气: {$currentWeather['weather']}, 温度: {$currentWeather['temperature']}°C\n";
    echo "城市名称: {$currentWeather['cityName']}\n";
    echo "\n";
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n\n";
}

echo "=== 测试通过IP地址获取城市ID ===\n";
try {
    // 测试获取北京IP（114.247.50.2）对应的城市ID
    $ip = '114.247.50.2';
    $cityCode = $weatherCrawler->getCityCodeByIp($ip);
    echo "IP地址: {$ip}, 城市ID: {$cityCode}\n";
    
    // 使用城市ID获取天气数据
    echo "正在获取该IP地址位置的当前天气...\n";
    $currentWeather = $weatherCrawler->getCurrentWeather($cityCode);
    echo "当前天气: {$currentWeather['weather']}, 温度: {$currentWeather['temperature']}°C\n";
    echo "城市名称: {$currentWeather['cityName']}\n";
    echo "\n";
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n\n";
}

echo "=== 测试获取贵阳天气 ===\n";
try {
    // 测试获取贵阳的城市ID
    $cityName = '贵阳';
    $cityCode = $weatherCrawler->getCityCodeByName($cityName);
    echo "城市名称: {$cityName}, 城市ID: {$cityCode}\n";
    
    // 使用城市ID获取天气数据
    echo "正在获取 {$cityName} 的当前天气...\n";
    $currentWeather = $weatherCrawler->getCurrentWeather($cityCode);
    echo "当前天气: {$currentWeather['weather']}, 温度: {$currentWeather['temperature']}°C\n";
    
    echo "正在获取 {$cityName} 的7日天气预报...\n";
    $sevenDayWeather = $weatherCrawler->get7DayWeather($cityCode);
    echo "未来7天天气预报条数: " . count($sevenDayWeather) . "条\n";
    
    echo "\n";
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n\n";
}

echo "=== 测试获取上海天气（使用经纬度） ===\n";
try {
    // 测试获取上海的经纬度对应的城市ID（上海大致经纬度：31.2304, 121.4737）
    $latitude = 31.2304;
    $longitude = 121.4737;
    $cityCode = $weatherCrawler->getCityCodeByCoordinates($latitude, $longitude);
    echo "经纬度: {$latitude}, {$longitude}, 城市ID: {$cityCode}\n";
    
    // 使用城市ID获取天气数据
    echo "正在获取上海的当前天气...\n";
    $currentWeather = $weatherCrawler->getCurrentWeather($cityCode);
    echo "当前天气: {$currentWeather['weather']}, 温度: {$currentWeather['temperature']}°C\n";
    echo "城市名称: {$currentWeather['cityName']}\n";
    echo "\n";
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n\n";
}

echo "=== 所有测试完成 ===\n";
