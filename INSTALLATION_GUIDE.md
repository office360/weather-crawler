# office360/weather-crawler 安装指南

## 问题说明

当尝试执行 `composer require office360/weather-crawler` 时，可能会遇到以下错误：

```
Could not find a matching version of package office360/weather-crawler. Check
    the package spelling, your version constraint and that the package is availa
   ble in a stability which matches your minimum-stability (stable).
```

这是因为该包尚未发布到Packagist（Composer的官方包仓库）。

## 解决方案

### 方案一：从GitHub仓库直接安装

您可以直接从GitHub仓库安装该包：

```bash
composer require office360/weather-crawler:dev-main
```

如果上述命令仍然失败，可以在`composer.json`中手动添加仓库配置：

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

### 方案二：手动将包发布到Packagist（需要Packagist账号）

1. **创建Packagist账号**：
   - 访问 https://packagist.org/ 并注册账号

2. **登录并提交包**：
   - 点击右上角的"Submit"按钮
   - 输入GitHub仓库地址：`https://github.com/office360/weather-crawler.git`
   - 点击"Check"按钮，然后点击"Submit"完成发布

3. **配置GitHub Webhook（可选但推荐）**：
   - 在GitHub仓库的"Settings" -> "Webhooks"中
   - 添加新的Webhook，Payload URL设置为`https://packagist.org/api/github`
   - 选择"Just the push event"
   - 点击"Add webhook"完成配置

4. **发布新版本（可选）**：
   - 在本地仓库创建标签：`git tag v1.0.0`
   - 推送标签：`git push origin v1.0.0`

完成上述步骤后，用户就可以使用标准的Composer命令安装该包了：

```bash
composer require office360/weather-crawler
```

## 验证安装

安装完成后，可以使用以下代码验证包是否正常工作：

```php
<?php

require 'vendor/autoload.php';

use Weather\WeatherCrawler;

// 创建实例
$weatherCrawler = new WeatherCrawler();

try {
    // 根据访客所在城市获取当前天气数据
    $currentWeather = $weatherCrawler->getCurrentWeather();
    print_r($currentWeather);
} catch (Exception $e) {
    echo '错误: ' . $e->getMessage();
}
```
