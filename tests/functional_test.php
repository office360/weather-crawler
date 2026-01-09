<?php
/**
 * 天气爬虫功能测试脚本
 * 测试所有天气数据获取方式的实际功能
 */

// 直接包含所需文件
require_once __DIR__ . '/../src/Weather/WeatherCrawler.php';
require_once __DIR__ . '/../src/Weather/Exception/WeatherException.php';

echo "开始功能测试 - 天气数据获取\n";
echo "================================\n\n";

// 创建WeatherCrawler实例
$weatherCrawler = Weather\WeatherCrawler::create();

// 测试城市：北京
$city = '北京';
$cityCode = '101010100';

// 测试1：获取当天基础天气数据
echo "1. 测试获取当天基础天气数据 - $city\n";
try {
    $basicWeather = $weatherCrawler->forCity($city)->getWeather('basic');
    echo "   ✓ 成功获取基础天气数据\n";
    echo "   温度: {$basicWeather['temperature']}°C\n";
    echo "   天气: {$basicWeather['weather']}\n";
    echo "   风向: {$basicWeather['windDirection']}\n";
    echo "   风力: {$basicWeather['windPower']}\n";
} catch (Exception $e) {
    echo "   ✗ 失败: {$e->getMessage()}\n";
}
echo "\n";

// 测试2：获取当天详细天气数据
echo "2. 测试获取当天详细天气数据 - $city\n";
try {
    $detailWeather = $weatherCrawler->forCity($city)->getWeather('detail');
    echo "   ✓ 成功获取详细天气数据\n";
    if (isset($detailWeather['airQuality'])) {
        echo "   空气质量: {$detailWeather['airQuality']}\n";
    }
    if (isset($detailWeather['weatherIndex'])) {
        echo "   天气指数数量: " . count($detailWeather['weatherIndex']) . " 个\n";
    }
} catch (Exception $e) {
    echo "   ✗ 失败: {$e->getMessage()}\n";
}
echo "\n";

// 测试3：获取7天天气数据
echo "3. 测试获取7天天气数据 - $city\n";
try {
    $sevenDayWeather = $weatherCrawler->forCity($city)->getWeather('7day');
    echo "   ✓ 成功获取7天天气数据\n";
    echo "   天数: " . count($sevenDayWeather) . " 天\n";
    foreach ($sevenDayWeather as $day) {
        echo "   {$day['date']}: {$day['weather']} {$day['tempMax']}°C/{$day['tempMin']}°C\n";
    }
} catch (Exception $e) {
    echo "   ✗ 失败: {$e->getMessage()}\n";
}
echo "\n";

// 测试4：获取逐小时天气数据（默认24条）
echo "4. 测试获取逐小时天气数据 - $city\n";
try {
    $hourlyWeather = $weatherCrawler->forCity($city)->getWeather('hourly');
    echo "   ✓ 成功获取逐小时天气数据\n";
    echo "   条数: " . count($hourlyWeather) . " 条\n";
    for ($i = 0; $i < min(5, count($hourlyWeather)); $i++) {
        echo "   {$hourlyWeather[$i]['time']}: {$hourlyWeather[$i]['weather']} {$hourlyWeather[$i]['temperature']}°C\n";
    }
} catch (Exception $e) {
    echo "   ✗ 失败: {$e->getMessage()}\n";
}
echo "\n";

// 测试5：获取自定义条数的逐小时天气数据（12条）
echo "5. 测试获取自定义条数的逐小时天气数据（12条） - $city\n";
try {
    $hourlyWeather12 = $weatherCrawler->forCity($city)->getWeather('hourly', 12);
    echo "   ✓ 成功获取12条逐小时天气数据\n";
    echo "   条数: " . count($hourlyWeather12) . " 条\n";
} catch (Exception $e) {
    echo "   ✗ 失败: {$e->getMessage()}\n";
}
echo "\n";

// 测试6：获取15天天气数据
echo "6. 测试获取15天天气数据 - $city\n";
try {
    $fifteenDayWeather = $weatherCrawler->forCity($city)->getWeather('15day');
    echo "   ✓ 成功获取15天天气数据\n";
    echo "   天数: " . count($fifteenDayWeather) . " 天\n";
    for ($i = 0; $i < min(5, count($fifteenDayWeather)); $i++) {
        echo "   {$fifteenDayWeather[$i]['date']}: {$fifteenDayWeather[$i]['weather']} {$fifteenDayWeather[$i]['tempMax']}°C/{$fifteenDayWeather[$i]['tempMin']}°C\n";
    }
} catch (Exception $e) {
    echo "   ✗ 失败: {$e->getMessage()}\n";
}
echo "\n";

// 测试7：获取综合天气数据
echo "7. 测试获取综合天气数据 - $city\n";
try {
    $comprehensiveWeather = $weatherCrawler->forCity($city)->getWeather('comprehensive');
    echo "   ✓ 成功获取综合天气数据\n";
    echo "   ✓ 包含当天详细数据\n";
    echo "   ✓ 包含7天预报: " . count($comprehensiveWeather['7day']) . " 天\n";
    echo "   ✓ 包含逐小时预报: " . count($comprehensiveWeather['hourly']) . " 条\n";
    echo "   ✓ 包含15天预报: " . count($comprehensiveWeather['15day']) . " 天\n";
} catch (Exception $e) {
    echo "   ✗ 失败: {$e->getMessage()}\n";
}
echo "\n";

// 测试8：通过城市代码获取天气
echo "8. 测试通过城市代码获取天气 - $cityCode\n";
try {
    $weatherByCode = $weatherCrawler->forCityCode($cityCode)->getWeather('basic');
    echo "   ✓ 成功通过城市代码获取天气\n";
    echo "   温度: {$weatherByCode['temperature']}°C\n";
    echo "   天气: {$weatherByCode['weather']}\n";
} catch (Exception $e) {
    echo "   ✗ 失败: {$e->getMessage()}\n";
}
echo "\n";

// 测试9：通过经纬度获取天气
echo "9. 测试通过经纬度获取天气（北京坐标）\n";
try {
    $weatherByCoords = $weatherCrawler->forCoordinates(39.9042, 116.4074)->getWeather('basic');
    echo "   ✓ 成功通过经纬度获取天气\n";
    echo "   城市: {$weatherByCoords['cityName']}\n";
    echo "   温度: {$weatherByCoords['temperature']}°C\n";
} catch (Exception $e) {
    echo "   ✗ 失败: {$e->getMessage()}\n";
}
echo "\n";

echo "功能测试完成!\n";
echo "================================\n";
