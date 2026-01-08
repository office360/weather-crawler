<?php
namespace Weather;
/**
 * 天气爬虫工具类
 * 数据源：www.weather.com.cn
 * 根据开发文档实现五个步骤的数据获取
 */
class WeatherCrawler
{
    /**
     * 访客数据
     * @var array
     */
    protected $visitorData = [];

    /**
     * 城市代码映射
     * @var array
     */
    protected $cityCodeMap = [];

    /**
     * 构造函数
     */
    public function __construct()
    {
        // 加载城市代码映射
        $this->loadCityCodeMap();
    }

    /**
     * 加载城市代码映射
     */
    protected function loadCityCodeMap()
    {
        // 尝试从resources目录加载城市代码映射文件
        $cityCodeFile = __DIR__ . '/../../resources/city_code_map.json';
        
        // 如果resources目录不存在，尝试从项目根目录加载
        if (!file_exists($cityCodeFile)) {
            $cityCodeFile = __DIR__ . '/../../../resources/city_code_map.json';
        }
        
        if (file_exists($cityCodeFile)) {
            $content = file_get_contents($cityCodeFile);
            $this->cityCodeMap = json_decode($content, true) ?: [];
        }
    }

    /**
     * 获取访客数据
     * 数据源：https://wgeo.weather.com.cn/ip/?_=1767694402775
     * @param string|null $clientIp 可选的客户端IP地址，用于获取真实访客位置
     * @return array
     * @throws \Exception
     */
    public function getVisitorData(string $clientIp = null): array
    {
        // 如果提供了新的客户端IP，或者还没有获取过访客数据，就重新获取
        if (!empty($clientIp) || empty($this->visitorData)) {
            try {
                $url = 'https://wgeo.weather.com.cn/ip/?_=' . (time() * 1000);
                // 如果提供了客户端IP，添加到请求参数中
                if (!empty($clientIp)) {
                    $url .= '&ip=' . urlencode($clientIp);
                }
                
                
                $headers = [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: zh-CN,zh;q=0.9',
                    'Connection: keep-alive',
                    'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1',
                    'Referer: https://www.weather.com.cn/',
                    'Host: wgeo.weather.com.cn'
                ];

                $response = $this->sendRequest($url, $headers);

                // 解析数据
                if (preg_match('/var ip="(\d+\.\d+\.\d+\.\d+)";var id="([^"]+)";var addr="([^"]+)";/', $response, $matches)) {
                    // 解析地址信息（格式：省份,城市,区域）
                    $addrParts = explode(',', $matches[3]);
                    $province = $addrParts[0] ?? '';
                    $city = $addrParts[1] ?? '';
                    $district = $addrParts[2] ?? '';
                    
                    $this->visitorData = [
                        'ip' => $matches[1],
                        'province' => $province,
                        'city' => $city,
                        'district' => $district,
                        'cityCode' => $matches[2]
                    ];
                    return $this->visitorData;
                } else {
                    throw new \Exception('无法解析访客数据');
                }
            } catch (\Exception $e) {
                throw new \Exception('获取访客数据失败: ' . $e->getMessage());
            }
        }
        
        return $this->visitorData;
    }

    /**
     * 获取当前天气数据接口
     * @param string|null $cityCode 城市代码
     * @param string|null $cityName 城市名称
     * @param string|null $clientIp 可选的客户端IP地址，用于获取真实访客位置
     * @return array
     * @throws \Exception
     */
    public function getCurrentWeather(string $cityCode = null, string $cityName = null, string $clientIp = null): array
    {
        try {
            // 确定城市代码
            $cityCode = $this->getCityCode($cityCode, $cityName, $clientIp);
            
            // 获取当前天气基础数据（第二步）
            $basicData = $this->getBasicWeatherData($cityCode);
            
            // 获取当前天气详情数据（第五步）
            $detailData = $this->getWeatherDetailData($cityCode);
            
            // 合并数据
            return array_merge($basicData, $detailData);
        } catch (\Exception $e) {
            throw new \Exception('获取当前天气数据失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取7日天气数据接口
     * @param string|null $cityCode 城市代码
     * @param string|null $cityName 城市名称
     * @param string|null $clientIp 可选的客户端IP地址，用于获取真实访客位置
     * @return array
     * @throws \Exception
     */
    public function get7DayWeatherData(string $cityCode = null, string $cityName = null, string $clientIp = null): array
    {
        try {
            // 确定城市代码
            $cityCode = $this->getCityCode($cityCode, $cityName, $clientIp);
            
            // 获取17日天气数据（第三步）
            $allDayData = $this->getMultiDayWeatherData($cityCode);
            
            // 只返回前7天数据
            return array_slice($allDayData, 0, 7);
        } catch (\Exception $e) {
            throw new \Exception('获取7日天气数据失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取逐小时天气数据接口
     * @param string|null $cityCode 城市代码
     * @param string|null $cityName 城市名称
     * @param string|null $clientIp 可选的客户端IP地址，用于获取真实访客位置
     * @return array
     * @throws \Exception
     */
    public function getHourlyWeatherData(string $cityCode = null, string $cityName = null, string $clientIp = null): array
    {
        try {
            // 确定城市代码
            $cityCode = $this->getCityCode($cityCode, $cityName, $clientIp);
            
            // 获取逐小时天气数据（第四步）
            return $this->getHourlyData($cityCode);
        } catch (\Exception $e) {
            throw new \Exception('获取逐小时天气数据失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取全部数据接口
     * 含当前天气数据详情，15日天气数据及逐小时天气数据
     * @param string|null $cityCode 城市代码
     * @param string|null $cityName 城市名称
     * @param string|null $clientIp 可选的客户端IP地址，用于获取真实访客位置
     * @return array
     * @throws \Exception
     */
    public function getAllWeatherData(string $cityCode = null, string $cityName = null, string $clientIp = null): array
    {
        try {
            // 确定城市代码
            $cityCode = $this->getCityCode($cityCode, $cityName, $clientIp);
            
            // 获取当前天气数据
            $currentWeather = $this->getCurrentWeather($cityCode, null, $clientIp);
            
            // 获取17日天气数据（取前15天）
            $allDayData = $this->getMultiDayWeatherData($cityCode);
            $fifteenDayWeather = array_slice($allDayData, 0, 15);
            
            // 获取逐小时天气数据
            $hourlyWeather = $this->getHourlyData($cityCode);
            
            // 返回合并数据
            return [
                'currentWeather' => $currentWeather,
                'fifteenDayWeather' => $fifteenDayWeather,
                'hourlyWeather' => $hourlyWeather
            ];
        } catch (\Exception $e) {
            throw new \Exception('获取全部天气数据失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取城市代码映射
     * @return array
     */
    public function getCityCodeMap(): array
    {
        return $this->cityCodeMap;
    }

    /**
     * 获取城市代码
     * @param string|null $cityCode 城市代码
     * @param string|null $cityName 城市名称
     * @param string|null $clientIp 可选的客户端IP地址，用于获取真实访客位置
     * @return string
     * @throws \Exception
     */
    protected function getCityCode(string $cityCode = null, string $cityName = null, string $clientIp = null): string
    {
        // 如果提供了城市代码，直接返回
        if (!empty($cityCode)) {
            return $cityCode;
        }
        
        // 如果提供了城市名称，查找城市代码
        if (!empty($cityName)) {
            if (!empty($this->cityCodeMap[$cityName])) {
                return $this->cityCodeMap[$cityName];
            } else {
                throw new \Exception('未找到该城市的代码');
            }
        }
        
        // 否则使用访客所在城市的代码
        $visitorData = $this->getVisitorData($clientIp);
        return $visitorData['cityCode'];
    }

    /**
     * 获取当前天气基础数据
     * 数据源：https://d1.weather.com.cn/sk_2d/101260104.html?_=1767797207385
     * @param string $cityCode 城市代码
     * @return array
     * @throws \Exception
     */
    protected function getBasicWeatherData(string $cityCode): array
    {
        try {
            $url = 'https://d1.weather.com.cn/sk_2d/' . $cityCode . '.html?_=' . (time() * 1000);
            $headers = [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: zh-CN,zh;q=0.9',
                'Connection: keep-alive',
                'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1',
                'Referer: https://www.weather.com.cn/',
                'Host: d1.weather.com.cn'
            ];

            $response = $this->sendRequest($url, $headers);
            
            // 解析数据
            if (preg_match('/var\s+dataSK\s*=\s*(\{[^}]+\})/', $response, $matches)) {
                $data = json_decode($matches[1], true);
                if ($data === null) {
                    throw new \Exception('解析当前天气基础数据失败');
                }
                
                // 调试：查看API返回的完整数据
                // error_log("Current weather API response: " . json_encode($data));
                // error_log("City code: " . $cityCode);
                // error_log("API city field: " . ($data['city'] ?? 'empty'));
                
                // 优先使用API返回的cityname字段
                $cityName = '';
                if (!empty($data['cityname'])) {
                    // API返回的cityname已经是简洁的城市名称，直接使用
                    $cityName = $data['cityname'];
                } else {
                    // 如果是自动定位场景，直接使用访客数据中的城市名称
                    if (!empty($this->visitorData['cityCode']) && $this->visitorData['cityCode'] === $cityCode) {
                        $cityName = $this->visitorData['city'];
                    } else {
                        // 从城市代码映射中查找城市名称（需要反转映射）
                        $reverseCityMap = array_flip($this->cityCodeMap);
                        $cityName = $reverseCityMap[$cityCode] ?? $cityCode;
                    }
                }
                
                return [
                    'cityCode' => $cityCode,
                    'cityName' => $cityName,
                    'temperature' => $data['temp'] ?? '',
                    'weather' => $data['weather'] ?? '',
                    'windDirection' => $data['WD'] ?? '',
                    'windPower' => $data['WS'] ?? '',
                    'humidity' => $data['SD'] ?? '',
                    'time' => $data['time'] ?? date('H:i'),
                    'aqi' => $data['aqi'] ?? ''
                ];
            } else {
                throw new \Exception('未找到当前天气基础数据');
            }
        } catch (\Exception $e) {
            throw new \Exception('获取当前天气基础数据失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取17日天气数据及逐小时天气数据
     * 数据源：https://d1.weather.com.cn/weixinfc/101260104.html?_=1767796061347
     * @param string $cityCode 城市代码
     * @return array
     * @throws \Exception
     */
    protected function getMultiDayWeatherData(string $cityCode): array
    {
        try {
            $url = 'https://d1.weather.com.cn/weixinfc/' . $cityCode . '.html?_=' . (time() * 1000);
            $headers = [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: zh-CN,zh;q=0.9',
                'Connection: keep-alive',
                'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1',
                'Referer: https://www.weather.com.cn/',
                'Host: d1.weather.com.cn'
            ];

            $response = $this->sendRequest($url, $headers);
            
            // 解析数据
            $fcData = $this->extractJsonFromResponse($response, 'fc');
            if ($fcData === null) {
                throw new \Exception('解析多日天气数据失败');
            }
                
                // 天气数据在f数组中
                $data = $fcData['f'] ?? [];
                if (empty($data)) {
                    throw new \Exception('未找到多日天气数据');
                }
                
                // 天气状况代码映射
                $weatherCodes = [
                    '00' => '晴', '01' => '多云', '02' => '阴', '03' => '小雨',
                    '04' => '中雨', '05' => '大雨', '06' => '暴雨', '07' => '雷阵雨',
                    '08' => '阵雨', '09' => '小雪', '10' => '中雪', '11' => '大雪',
                    '12' => '暴雪', '13' => '雾', '14' => '霾', '15' => '沙尘',
                    '16' => '扬沙', '17' => '浮尘', '18' => '强沙尘暴', '19' => '雷阵雨伴有冰雹',
                    '20' => '小雨-中雨', '21' => '中雨-大雨', '22' => '大雨-暴雨', '23' => '暴雨-大暴雨',
                    '24' => '大暴雨-特大暴雨', '25' => '小雪-中雪', '26' => '中雪-大雪', '27' => '大雪-暴雪'
                ];
                
                // 处理数据
                $result = [];
                foreach ($data as $item) {
                    $dayWeather = $weatherCodes[$item['fa']] ?? '未知';
                    $nightWeather = $weatherCodes[$item['fb']] ?? '未知';
                    
                    $weather = $dayWeather;
                    if ($dayWeather !== $nightWeather) {
                        $weather .= '转' . $nightWeather;
                    }
                    
                    $result[] = [
                        'date' => $item['fi'] ?? '',
                        'day' => $item['fj'] ?? '',
                        'weather' => $weather,
                        'tempMax' => $item['fc'] ?? '',
                        'tempMin' => $item['fd'] ?? '',
                        'windDay' => $item['fe'] ?? '',
                        'windNight' => $item['ff'] ?? '',
                        'windPowerDay' => $item['fg'] ?? '',
                        'windPowerNight' => $item['fh'] ?? '',
                        'humidityDay' => $item['fm'] ?? '',
                        'humidityNight' => $item['fn'] ?? ''
                    ];
                }
                
                return $result;
        } catch (\Exception $e) {
            throw new \Exception('获取多日天气数据失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取逐小时天气数据
     * 数据源：https://d1.weather.com.cn/wap_180h/101260104.html?_=1767797207395
     * @param string $cityCode 城市代码
     * @return array
     * @throws \Exception
     */
    protected function getHourlyData(string $cityCode): array
    {
        try {
            $url = 'https://d1.weather.com.cn/wap_180h/' . $cityCode . '.html?_=' . (time() * 1000);
            $headers = [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: zh-CN,zh;q=0.9',
                'Connection: keep-alive',
                'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1',
                'Referer: https://www.weather.com.cn/',
                'Host: d1.weather.com.cn'
            ];

            $response = $this->sendRequest($url, $headers);
            
            // 解析数据
            $fc180 = $this->extractJsonFromResponse($response, 'fc180');
            if ($fc180 === null) {
                throw new \Exception('解析逐小时天气数据失败');
            }
                
                // 逐小时天气数据在jh数组中
                $data = $fc180['jh'] ?? [];
                if (empty($data)) {
                    throw new \Exception('未找到逐小时天气数据');
                }
                
                // 调试：查看原始数据
                // error_log("原始逐小时天气数据: " . json_encode($data, JSON_UNESCAPED_UNICODE));
                
                // 调试：查看当前时间和时间格式
                // error_log("当前时间: " . $currentTime->format('YmdHis'));
                // if (!empty($data)) {
                //     $firstItem = reset($data);
                //     error_log("第一条数据时间: " . ($firstItem['jf'] ?? '无时间信息'));
                // }
                
                // 天气状况代码映射
                $weatherCodes = [
                    '00' => '晴', '01' => '多云', '02' => '阴', '03' => '小雨',
                    '04' => '中雨', '05' => '大雨', '06' => '暴雨', '07' => '雷阵雨',
                    '08' => '阵雨', '09' => '小雪', '10' => '中雪', '11' => '大雪',
                    '12' => '暴雪', '13' => '雾', '14' => '霾', '15' => '沙尘',
                    '16' => '扬沙', '17' => '浮尘', '18' => '强沙尘暴', '19' => '雷阵雨伴有冰雹',
                    '20' => '小雨-中雨', '21' => '中雨-大雨', '22' => '大雨-暴雨', '23' => '暴雨-大暴雨',
                    '24' => '大暴雨-特大暴雨', '25' => '小雪-中雪', '26' => '中雪-大雪', '27' => '大雪-暴雪',
                    '301' => '多云', '302' => '阴'
                ];
                
                // 处理数据
                $result = [];
                $currentTime = new \DateTime();
                
                foreach ($data as $item) {
                    // 保留完整的时间字符串用于比较（格式：202601072000）
                    $fullTime = $item['jf'] ?? '';
                    
                    // 格式化显示时间，从202601072000转换为20:00
                    $displayTime = '';
                    if (strlen($fullTime) >= 4) {
                        $displayTime = substr($fullTime, -4, 2) . ':' . substr($fullTime, -2, 2);
                    }
                    
                    // 如果有完整的时间信息，过滤掉当前时间之前的数据
                    if (strlen($fullTime) == 12) {
                        // 解析为DateTime对象，使用正确的格式（没有秒数）
                        $forecastTime = \DateTime::createFromFormat('YmdHi', $fullTime);
                        if ($forecastTime !== false) {
                            // 只保留当前时间之后的天气数据
                            if ($forecastTime > $currentTime) {
                                $result[] = [
                                    'time' => $displayTime,
                                    'temperature' => $item['jd'] ?? '',
                                    'weather' => $weatherCodes[$item['ja']] ?? '未知',
                                    'windDirection' => $item['jh'] ?? '',
                                    'windPower' => $item['ji'] ?? '',
                                    'humidity' => $item['je'] ?? ''
                                ];
                            }
                        }
                    }
                    // 不保留时间信息不完整的数据，直接跳过
                }
                
                return $result;
        } catch (\Exception $e) {
            throw new \Exception('获取逐小时天气数据失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取当前天气详情数据（第五步）
     * 数据源：https://d1.weather.com.cn/weather_index/101260104.html?_=1767796061348
     * @param string $cityCode 城市代码
     * @return array
     * @throws \Exception
     */
    protected function getWeatherDetailData(string $cityCode): array
    {
        try {
            $url = 'https://d1.weather.com.cn/weather_index/' . $cityCode . '.html?_=' . (time() * 1000);
            $headers = [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: zh-CN,zh;q=0.9',
                'Connection: keep-alive',
                'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1',
                'Referer: https://www.weather.com.cn/',
                'Host: d1.weather.com.cn'
            ];

            $response = $this->sendRequest($url, $headers);
            
            // 解析数据
            $result = [];
            
            // 获取空气质量数据
            if (preg_match('/var\s+aqi\s*=\s*(\{[^}]+\})/', $response, $matches)) {
                $aqiData = json_decode($matches[1], true);
                if ($aqiData !== null) {
                    $result['airQuality'] = [
                        'aqi' => $aqiData['aqi'] ?? '',
                        'quality' => $aqiData['quality'] ?? '',
                        'pm25' => $aqiData['pm25'] ?? '',
                        'pm10' => $aqiData['pm10'] ?? '',
                        'o3' => $aqiData['o3'] ?? '',
                        'no2' => $aqiData['no2'] ?? '',
                        'so2' => $aqiData['so2'] ?? '',
                        'co' => $aqiData['co'] ?? ''
                    ];
                }
            }
            
            // 获取天气指数数据
            if (preg_match('/var\s+index\s*=\s*(\[\{[^\]]+\}\])/', $response, $matches)) {
                $indexData = json_decode($matches[1], true);
                if ($indexData !== null) {
                    $weatherIndex = [];
                    foreach ($indexData as $item) {
                        $weatherIndex[] = [
                            'name' => $item['iname'] ?? '',
                            'value' => $item['ivalue'] ?? '',
                            'level' => $item['level'] ?? '',
                            'desc' => $item['desc'] ?? ''
                        ];
                    }
                    $result['weatherIndex'] = $weatherIndex;
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            // 天气详情数据不是必须的，可以返回空数组
            return [];
        }
    }

    /**
     * 发送HTTP请求
     * @param string $url URL地址
     * @param array $headers 请求头
     * @return string
     * @throws \Exception
     */
    protected function sendRequest(string $url, array $headers = []): string
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('HTTP请求失败: ' . $error);
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode != 200) {
            throw new \Exception('HTTP请求失败，状态码: ' . $httpCode);
        }
        
        return $response;
    }

    /**
     * 从响应中提取JSON数据
     * @param string $response 响应内容
     * @param string $varName 变量名（如 'fc'）
     * @return array|null 解析后的JSON数组或null
     */
    protected function extractJsonFromResponse(string $response, string $varName): ?array
    {
        // 构建变量定义的匹配模式
        $pattern = '/var\s+' . preg_quote($varName, '/') . '\s*=\s*(\{)/';
        
        if (preg_match($pattern, $response, $matches, PREG_OFFSET_CAPTURE)) {
            $startPos = $matches[1][1];
            $length = strlen($response);
            $braceCount = 1;
            $endPos = $startPos + 1;
            
            // 从开始位置遍历字符串，跟踪括号平衡
            while ($braceCount > 0 && $endPos < $length) {
                $char = $response[$endPos];
                
                if ($char === '{') {
                    $braceCount++;
                } elseif ($char === '}') {
                    $braceCount--;
                }
                
                $endPos++;
            }
            
            if ($braceCount === 0) {
                // 提取完整的JSON字符串
                $jsonString = substr($response, $startPos, $endPos - $startPos);
                
                // 解析JSON
                $jsonData = json_decode($jsonString, true);
                if ($jsonData !== null) {
                    return $jsonData;
                }
            }
        }
        
        return null;
    }
}