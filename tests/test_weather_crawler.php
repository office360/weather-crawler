<?php

require 'vendor/autoload.php';

use Weather\WeatherCrawler;
use Weather\Exception\WeatherException;

// 创建日志目录
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// 日志函数
function logMessage($message, $type = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[$timestamp] [$type] $message\n";
    echo $logLine;
    file_put_contents(__DIR__ . '/logs/weather_test.log', $logLine, FILE_APPEND);
}

// 测试结果记录
$testResults = [
    'passed' => 0,
    'failed' => 0,
    'errors' => []
];

// 测试辅助函数
function runTest($testName, $testFunction) {
    global $testResults;
    
    try {
        logMessage("开始测试: $testName");
        
        $result = $testFunction();
        
        logMessage("测试通过: $testName");
        $testResults['passed']++;
        
        return $result;
    } catch (Exception $e) {
        logMessage("测试失败: $testName - " . $e->getMessage(), 'error');
        $testResults['failed']++;
        $testResults['errors'][] = [
            'test' => $testName,
            'message' => $e->getMessage()
        ];
        return false;
    }
}

// 创建WeatherCrawler实例
$weatherCrawler = WeatherCrawler::create();

logMessage('开始WeatherCrawler功能测试');
logMessage('=============================');

// 1. 测试静态工厂方法
runTest('静态工厂方法 create()', function() {
    $instance = WeatherCrawler::create();
    return $instance instanceof WeatherCrawler;
});

// 2. 测试getCityCodeByName方法
runTest('getCityCodeByName方法 - 北京', function() use ($weatherCrawler) {
    $cityCode = $weatherCrawler->getCityCodeByName('北京');
    return $cityCode === '101010100';
});

runTest('getCityCodeByName方法 - 上海', function() use ($weatherCrawler) {
    $cityCode = $weatherCrawler->getCityCodeByName('上海');
    return $cityCode === '101020100';
});

runTest('getCityCodeByName方法 - 广州', function() use ($weatherCrawler) {
    $cityCode = $weatherCrawler->getCityCodeByName('广州');
    return $cityCode === '101280101';
});

runTest('getCityCodeByName方法 - 深圳', function() use ($weatherCrawler) {
    $cityCode = $weatherCrawler->getCityCodeByName('深圳');
    return $cityCode === '101280601';
});

runTest('getCityCodeByName方法 - 成都', function() use ($weatherCrawler) {
    $cityCode = $weatherCrawler->getCityCodeByName('成都');
    return $cityCode === '101270101';
});

// 3. 测试城市名称标准化处理
runTest('城市名称标准化处理 - 北京市', function() use ($weatherCrawler) {
    $cityCode = $weatherCrawler->getCityCodeByName('北京市');
    return $cityCode === '101010100';
});

runTest('城市名称标准化处理 - 上海市区', function() use ($weatherCrawler) {
    $cityCode = $weatherCrawler->getCityCodeByName('上海市区');
    return $cityCode === '101020100';
});

// 4. 测试流式接口 - forCity
runTest('流式接口 forCity - 北京当前天气', function() use ($weatherCrawler) {
    $weather = $weatherCrawler->forCity('北京')->getWeather('current');
    return isset($weather['cityCode']) && $weather['cityCode'] === '101010100';
});

// 5. 测试流式接口 - forCoordinates
runTest('流式接口 forCoordinates - 北京当前天气', function() use ($weatherCrawler) {
    $weather = $weatherCrawler->forCoordinates(39.9042, 116.4074)->getWeather('current');
    return isset($weather['cityCode']);
});

// 6. 测试get7DayWeather方法
runTest('get7DayWeather方法 - 北京', function() use ($weatherCrawler) {
    $weather = $weatherCrawler->forCity('北京')->getWeather('7day');
    return is_array($weather) && count($weather) >= 7;
});

// 7. 测试get15DayWeather方法
runTest('get15DayWeather方法 - 北京', function() use ($weatherCrawler) {
    $weather = $weatherCrawler->forCity('北京')->getWeather('15day');
    return is_array($weather) && count($weather) >= 15;
});

// 8. 测试getHourlyWeather方法
runTest('getHourlyWeather方法 - 北京', function() use ($weatherCrawler) {
    $weather = $weatherCrawler->forCity('北京')->getWeather('hourly');
    return is_array($weather) && count($weather) > 0;
});

// 9. 测试getAllWeather方法
runTest('getAllWeather方法 - 北京', function() use ($weatherCrawler) {
    $weather = $weatherCrawler->forCity('北京')->getWeather('all');
    return isset($weather['currentWeather']) && isset($weather['fifteenDayWeather']) && isset($weather['hourlyWeather']);
});

// 10. 测试forCityCode方法
runTest('forCityCode方法 - 北京', function() use ($weatherCrawler) {
    $weather = $weatherCrawler->forCityCode('101010100')->getWeather('current');
    return isset($weather['cityCode']) && $weather['cityCode'] === '101010100';
});

// 11. 测试getVisitorData方法
runTest('getVisitorData方法 - 本地IP', function() use ($weatherCrawler) {
    $visitorData = $weatherCrawler->getVisitorData('127.0.0.1');
    return isset($visitorData['ip']) && $visitorData['ip'] === '127.0.0.1';
});

// 12. 测试城市ID获取方法
runTest('getCityCodeByIp方法 - 本地IP', function() use ($weatherCrawler) {
    $cityCode = $weatherCrawler->getCityCodeByIp('127.0.0.1');
    return !empty($cityCode);
});

// 输出测试结果
logMessage('');
logMessage('测试结果统计');
logMessage('=============');
logMessage("通过测试: {$testResults['passed']}");
logMessage("失败测试: {$testResults['failed']}");
logMessage("总测试数: " . ($testResults['passed'] + $testResults['failed']));

if (!empty($testResults['errors'])) {
    logMessage('错误详情:', 'error');
    foreach ($testResults['errors'] as $error) {
        logMessage("- {$error['test']}: {$error['message']}", 'error');
    }
}

logMessage('');
logMessage('测试完成!');