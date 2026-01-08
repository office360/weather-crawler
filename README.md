# office360/weather-crawler

一个PHP天气爬虫工具，用于从中国天气网（https://www.weather.com.cn/）获取天气数据。

## 功能特性

- **基于ip2region的访客IP定位**：完全本地化的IP地址定位，无需依赖外部API
- **自动获取天气数据**：根据访客所在城市自动获取天气数据
- **灵活的城市查询方式**：支持按城市代码或城市名称查询天气
- **提供四种API接口**：
  - 当前天气数据
  - 7日天气预报
  - 逐小时天气预报
  - 综合天气数据（包含上述所有数据）
- **支持PS-4自动加载**：符合现代PHP开发标准
- **模拟移动设备请求**：避免网站反爬限制
- **完善的错误处理机制**：确保应用稳定运行
- **可更新的IP数据库**：支持定期更新ip2region数据库以提高定位准确性

## 安装

### 方法一：使用Composer安装（推荐）

```bash
composer require office360/weather-crawler
```

### 方法二：从GitHub仓库安装开发版本

```bash
composer require office360/weather-crawler:dev-main
```

如果遇到网络问题，可以在`composer.json`中手动添加仓库配置：

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/office360/weather-crawler.git"
        }
    ],
    "require": {
        "office360/weather-crawler": "dev-main"
    }
}
```

然后运行：

```bash
composer install
```

## 快速开始

创建一个简单的PHP文件来测试天气爬虫：

```php
<?php

require 'vendor/autoload.php';

use Weather\WeatherCrawler;

// 创建实例
$weatherCrawler = new WeatherCrawler();

// 通过城市名称获取当前天气数据（推荐使用城市名称）
try {
    $currentWeather = $weatherCrawler->getCurrentWeather(null, '北京');
    print_r($currentWeather);
} catch (Exception $e) {
    echo '错误: ' . $e->getMessage();
}
```

运行上面的代码，您将看到北京的当前天气数据。

## 使用方法

### 基本用法

```php
<?php

require 'vendor/autoload.php';

use Weather\WeatherCrawler;

// 创建实例
$weatherCrawler = new WeatherCrawler();

// 根据访客所在城市获取当前天气数据
try {
    $currentWeather = $weatherCrawler->getCurrentWeather();
    print_r($currentWeather);
} catch (Exception $e) {
    echo '错误: ' . $e->getMessage();
}
```

### 根据城市代码获取天气数据

```php
<?php

require 'vendor/autoload.php';

use Weather\WeatherCrawler;

// 创建实例
$weatherCrawler = new WeatherCrawler();

// 使用城市代码获取7日天气数据
try {
    $sevenDayWeather = $weatherCrawler->get7DayWeatherData('101010100'); // 北京
    print_r($sevenDayWeather);
} catch (Exception $e) {
    echo '错误: ' . $e->getMessage();
}
```

### 根据城市名称获取天气数据

```php
<?php

require 'vendor/autoload.php';

use Weather\WeatherCrawler;

// 创建实例
$weatherCrawler = new WeatherCrawler();

// 使用城市名称获取逐小时天气数据
try {
    $hourlyWeather = $weatherCrawler->getHourlyWeatherData(null, '上海');
    print_r($hourlyWeather);
} catch (Exception $e) {
    echo '错误: ' . $e->getMessage();
}
```

### 手动指定IP地址获取天气数据

```php
<?php

require 'vendor/autoload.php';

use Weather\WeatherCrawler;

// 创建实例
$weatherCrawler = new WeatherCrawler();

// 手动指定IP地址获取天气数据（适用于代理环境或需要模拟不同地区的情况）
try {
    // 模拟北京IP获取当前天气
    $currentWeather = $weatherCrawler->getCurrentWeather(null, null, '114.247.50.2');
    print_r($currentWeather);
} catch (Exception $e) {
    echo '错误: ' . $e->getMessage();
}
```

### 获取综合天气数据

```php
<?php

require 'vendor/autoload.php';

use Weather\WeatherCrawler;

// 创建实例
$weatherCrawler = new WeatherCrawler();

// 获取综合天气数据
try {
    $allWeather = $weatherCrawler->getAllWeatherData();
    print_r($allWeather);
} catch (Exception $e) {
    echo '错误: ' . $e->getMessage();
}
```

## API参考

### WeatherCrawler类

#### getVisitorData(string $clientIp = null)

获取访客的IP、所在城市、区域等信息。

参数：
- `$clientIp`：访客IP地址（可选，不提供则自动获取）

返回值：
```php
[
    'ip' => string, // 访客IP地址
    'province' => string, // 省份
    'city' => string, // 城市
    'district' => string, // 区域
    'cityCode' => string // 城市代码
]
```

#### getCurrentWeather(string $cityCode = null, string $cityName = null, string $clientIp = null)

获取当前天气数据。

参数：
- `$cityCode`：城市代码（可选）
- `$cityName`：城市名称（可选）
- `$clientIp`：访客IP地址（可选，不提供则自动获取）

返回值：
```php
[
    'cityCode' => string, // 城市代码
    'cityName' => string, // 城市名称
    'temperature' => string, // 当前温度
    'weather' => string, // 天气状况
    'windDirection' => string, // 风向
    'windPower' => string, // 风力
    'humidity' => string, // 湿度
    'time' => string, // 更新时间
    'aqi' => string, // 空气质量指数
    'airQuality' => [ // 空气质量详情
        'aqi' => string,
        'quality' => string,
        'pm25' => string,
        'pm10' => string,
        'o3' => string,
        'no2' => string,
        'so2' => string,
        'co' => string
    ],
    'weatherIndex' => [ // 天气指数
        [
            'name' => string, // 指数名称
            'value' => string, // 指数值
            'level' => string, // 指数等级
            'desc' => string // 指数描述
        ],
        // ...
    ]
]
```

#### get7DayWeatherData(string $cityCode = null, string $cityName = null, string $clientIp = null)

获取7日天气预报。

参数：
- `$cityCode`：城市代码（可选）
- `$cityName`：城市名称（可选）
- `$clientIp`：访客IP地址（可选，不提供则自动获取）

返回值：
```php
[
    [
        'date' => string, // 日期
        'day' => string, // 星期
        'weather' => string, // 天气状况
        'tempMax' => string, // 最高温度
        'tempMin' => string, // 最低温度
        'windDay' => string, // 白天风向
        'windNight' => string, // 夜间风向
        'windPowerDay' => string, // 白天风力
        'windPowerNight' => string, // 夜间风力
        'humidityDay' => string, // 白天湿度
        'humidityNight' => string // 夜间湿度
    ],
    // ... 共7天
]
```

#### getHourlyWeatherData(string $cityCode = null, string $cityName = null, string $clientIp = null)

获取逐小时天气预报。

参数：
- `$cityCode`：城市代码（可选）
- `$cityName`：城市名称（可选）
- `$clientIp`：访客IP地址（可选，不提供则自动获取）

返回值：
```php
[
    [
        'time' => string, // 时间
        'temperature' => string, // 温度
        'weather' => string, // 天气状况
        'windDirection' => string, // 风向
        'windPower' => string, // 风力
        'humidity' => string // 湿度
    ],
    // ... 共24小时
]
```

#### getAllWeatherData(string $cityCode = null, string $cityName = null, string $clientIp = null)

获取综合天气数据，包含当前天气、15日预报和逐小时预报。

参数：
- `$cityCode`：城市代码（可选）
- `$cityName`：城市名称（可选）
- `$clientIp`：访客IP地址（可选，不提供则自动获取）

返回值：
```php
[
    'currentWeather' => array, // 当前天气数据，同getCurrentWeather返回值
    'fifteenDayWeather' => array, // 15天天气预报数据，同get7DayWeatherData返回值格式
    'hourlyWeather' => array // 逐小时天气预报数据，同getHourlyWeatherData返回值格式（实际返回约109小时数据）
]
```

## 注意事项

1. 本工具依赖于中国天气网的网页结构和API，若网站结构发生变化，可能导致数据获取失败。
2. 使用时请遵守网站的使用条款，避免过于频繁的请求。
3. 建议在生产环境中添加缓存机制，减少API请求次数。

## 开发

### 运行测试

```bash
composer test
```

## 许可证

MIT License