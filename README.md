# office360/weather-crawler

一个PHP天气爬虫工具，用于从中国天气网（https://www.weather.com.cn/）获取天气数据。

## 功能特性

- **基于ip2region的访客IP定位**：完全本地化的IP地址定位，无需依赖外部API
- **自动获取天气数据**：根据访客所在城市自动获取天气数据
- **灵活的城市ID获取方式**：支持三种方式获取城市ID：
  - 通过城市名称获取城市ID
  - 通过经纬度坐标获取城市ID
  - 通过IP地址获取城市ID
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

### 使用流式接口获取天气数据（推荐）

```php
<?php

require 'vendor/autoload.php';

use Weather\WeatherCrawler;

// 创建实例
$weatherCrawler = WeatherCrawler::create();

try {
    // 通过城市名称获取当前天气
    $currentWeather = $weatherCrawler->forCity('北京')
                                    ->getWeather('current');
    print_r($currentWeather);
} catch (Exception $e) {
    echo '错误: ' . $e->getMessage();
}
```

### 通过城市名称获取天气数据

```php
<?php

require 'vendor/autoload.php';

use Weather\WeatherCrawler;

// 创建实例
$weatherCrawler = WeatherCrawler::create();

try {
    // 1. 首先通过城市名称获取城市ID
    $cityCode = $weatherCrawler->getCityCodeByName('北京');
    echo "城市代码: {$cityCode}\n";
    
    // 2. 然后使用城市ID获取当前天气数据
    $currentWeather = $weatherCrawler->getCurrentWeather($cityCode);
    print_r($currentWeather);
} catch (Exception $e) {
    echo '错误: ' . $e->getMessage();
}
```

### 通过经纬度获取天气数据

```php
<?php

require 'vendor/autoload.php';

use Weather\WeatherCrawler;

// 创建实例
$weatherCrawler = WeatherCrawler::create();

try {
    // 使用流式接口通过经纬度获取当前天气（北京的经纬度）
    $currentWeather = $weatherCrawler->forCoordinates(39.9042, 116.4074)
                                     ->getWeather('current');
    print_r($currentWeather);
} catch (Exception $e) {
    echo '错误: ' . $e->getMessage();
}
```

### 通过IP地址获取天气数据

```php
<?php

require 'vendor/autoload.php';

use Weather\WeatherCrawler;

// 创建实例
$weatherCrawler = WeatherCrawler::create();

try {
    // 使用流式接口通过IP地址获取当前天气（北京的IP地址）
    $currentWeather = $weatherCrawler->forIp('114.247.50.2')
                                     ->getWeather('current');
    print_r($currentWeather);
} catch (Exception $e) {
    echo '错误: ' . $e->getMessage();
}
```

运行上面的代码，您将看到对应城市的天气数据。

## 使用方法

### 基本用法（自动定位）

```php
<?php

require 'vendor/autoload.php';

use Weather\WeatherCrawler;

// 创建实例
$weatherCrawler = WeatherCrawler::create();

// 根据访客所在城市获取当前天气数据
// forCurrentLocation() 方法会自动获取访客IP并定位城市

try {
    $currentWeather = $weatherCrawler->forCurrentLocation()
                                     ->getWeather('current');
    print_r($currentWeather);
} catch (Exception $e) {
    echo '错误: ' . $e->getMessage();
}
```

### 根据城市代码直接获取天气数据

```php
<?php

require 'vendor/autoload.php';

use Weather\WeatherCrawler;

// 创建实例
$weatherCrawler = WeatherCrawler::create();

// 已知城市代码的情况下，可以直接使用城市代码获取天气数据

try {
    // 使用流式接口获取7日天气数据（北京）
    $sevenDayWeather = $weatherCrawler->forCityCode('101010100')
                                     ->getWeather('7day');
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
$weatherCrawler = WeatherCrawler::create();

// 通过城市名称获取天气数据

try {
    // 使用流式接口获取逐小时天气数据
    $hourlyWeather = $weatherCrawler->forCity('上海')
                                    ->getWeather('hourly');
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
$weatherCrawler = WeatherCrawler::create();

// 手动指定IP地址获取天气数据（适用于代理环境或需要模拟不同地区的情况）

try {
    // 使用流式接口通过指定IP获取当前天气（模拟北京IP）
    $currentWeather = $weatherCrawler->forIp('114.247.50.2')
                                     ->getWeather('current');
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
$weatherCrawler = WeatherCrawler::create();

// 获取综合天气数据

try {
    // 使用流式接口获取综合天气数据（广州）
    $allWeather = $weatherCrawler->forCity('广州')
                                ->getWeather('all');
    print_r($allWeather);
} catch (Exception $e) {
    echo '错误: ' . $e->getMessage();
}
```

## API参考

### WeatherCrawler类

#### 工厂方法

##### create()

创建WeatherCrawler实例的静态工厂方法。

返回值：
- `WeatherCrawler`：WeatherCrawler实例

```php
$weatherCrawler = WeatherCrawler::create();
```

#### 流式接口方法

##### forCity(string $cityName)

通过城市名称设置城市代码。

参数：
- `$cityName`：城市名称（如"北京"、"上海"）

返回值：
- `WeatherCrawler`：返回当前实例，用于方法链式调用

##### forCoordinates(float $latitude, float $longitude)

通过经纬度坐标设置城市代码。

参数：
- `$latitude`：纬度（如北京的纬度39.9042）
- `$longitude`：经度（如北京的经度116.4074）

返回值：
- `WeatherCrawler`：返回当前实例，用于方法链式调用

##### forIp(?string $clientIp)

通过IP地址设置城市代码。

参数：
- `$clientIp`：访客IP地址（可选，不提供则自动获取）

返回值：
- `WeatherCrawler`：返回当前实例，用于方法链式调用

##### forCurrentLocation()

通过当前访问者IP地址自动设置城市代码。

返回值：
- `WeatherCrawler`：返回当前实例，用于方法链式调用

##### forCityCode(string $cityCode)

直接设置城市代码。

参数：
- `$cityCode`：城市代码（如"101010100"）

返回值：
- `WeatherCrawler`：返回当前实例，用于方法链式调用

##### getWeather(string $type = 'all')

获取指定类型的天气数据。

参数：
- `$type`：天气数据类型（可选，默认为'all'）
  - 'current'：当前天气数据
  - '7day'：7日天气预报
  - '15day'：15日天气预报
  - 'hourly'：逐小时天气预报
  - 'all'：综合天气数据（包含上述所有数据）

返回值：
- `array`：天气数据数组，格式根据$type参数而定

#### 获取城市ID的方法

##### getCityCodeByName(string $cityName)

通过城市名称获取城市代码。

参数：
- `$cityName`：城市名称（如"北京"、"上海"）

返回值：
- `string`：城市代码（如"101010100"）

##### getCityCodeByCoordinates(float $latitude, float $longitude)

通过经纬度坐标获取城市代码。

参数：
- `$latitude`：纬度（如北京的纬度39.9042）
- `$longitude`：经度（如北京的经度116.4074）

返回值：
- `string`：城市代码（如"101010100"）

##### getCityCodeByIp(?string $clientIp)

通过IP地址获取城市代码。

参数：
- `$clientIp`：访客IP地址（可选，不提供则自动获取）

返回值：
- `string`：城市代码（如"101010100"）

#### getVisitorData(?string $clientIp)

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

#### 获取天气数据的方法

##### getCurrentWeather(string $cityCode)

获取当前天气数据。

参数：
- `$cityCode`：城市代码（必需）

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

##### get7DayWeather(string $cityCode)

获取7日天气预报。

参数：
- `$cityCode`：城市代码（必需）

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

##### getHourlyWeather(string $cityCode)

获取逐小时天气预报。

参数：
- `$cityCode`：城市代码（必需）

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
    // ... 共24小时（实际返回约109小时数据）
]
```

##### getAllWeather(string $cityCode)

获取综合天气数据，包含当前天气、15日预报和逐小时预报。

参数：
- `$cityCode`：城市代码（必需）

返回值：
```php
[
    'currentWeather' => array, // 当前天气数据，同getCurrentWeather返回值
    'fifteenDayWeather' => array, // 15天天气预报数据，同get7DayWeather返回值格式
    'hourlyWeather' => array // 逐小时天气预报数据，同getHourlyWeather返回值格式
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