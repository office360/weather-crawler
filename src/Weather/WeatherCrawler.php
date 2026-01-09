<?php
namespace Weather;

use Ip2Region\Ip2Region;
use Weather\Exception\WeatherException;
/**
 * 天气爬虫工具类
 * 数据源：www.weather.com.cn
 * 根据开发文档实现五个步骤的数据获取
 */
class WeatherCrawler
{
    /**
     * 天气状况代码映射
     */
    const WEATHER_CODES = [
        '00' => '晴', '01' => '多云', '02' => '阴', '03' => '小雨',
        '04' => '中雨', '05' => '大雨', '06' => '暴雨', '07' => '雷阵雨',
        '08' => '阵雨', '09' => '小雪', '10' => '中雪', '11' => '大雪',
        '12' => '暴雪', '13' => '雾', '14' => '霾', '15' => '沙尘',
        '16' => '扬沙', '17' => '浮尘', '18' => '强沙尘暴', '19' => '雷阵雨伴有冰雹',
        '20' => '小雨-中雨', '21' => '中雨-大雨', '22' => '大雨-暴雨', '23' => '暴雨-大暴雨',
        '24' => '大暴雨-特大暴雨', '25' => '小雪-中雪', '26' => '中雪-大雪', '27' => '大雪-暴雪',
        '301' => '多云', '302' => '阴'
    ];
    
    /**
     * 风力等级映射
     */
    const WIND_POWER_CODES = [
        '1' => '1级', '2' => '2级', '3' => '3级', '4' => '4级',
        '5' => '5级', '6' => '6级', '7' => '7级', '8' => '8级',
        '9' => '9级', '10' => '10级', '11' => '11级', '12' => '12级及以上',
        '0' => '微风', '00' => '微风', '01' => '1级', '02' => '2级',
        '03' => '3级', '04' => '4级', '05' => '5级', '06' => '6级',
        '07' => '7级', '08' => '8级', '09' => '9级', '10' => '10级'
    ];
 

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
     * 当前城市代码
     * @var string|null
     */
    protected $currentCityCode = null;
    
    /**
     * 位置信息
     * @var array
     */
    protected $locationInfo = [
        'cityName' => null,
        'latitude' => null,
        'longitude' => null,
        'clientIp' => null
    ];

    /**
     * 构造函数
     */
    public function __construct()
    {
        // 延迟加载城市代码映射，仅在需要时加载
    }

    /**
     * 加载城市代码映射
     */
    protected function loadCityCodeMap()
    {
        // 如果已经加载过，直接返回
        if (!empty($this->cityCodeMap)) {
            return;
        }
        
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
     * 设置城市名称（流畅接口）
     * @param string $cityName 城市名称
     * @return self
     */
    public function forCity(string $cityName): self
    {
        $this->locationInfo['cityName'] = $cityName;
        $this->locationInfo['latitude'] = null;
        $this->locationInfo['longitude'] = null;
        $this->locationInfo['clientIp'] = null;
        $this->currentCityCode = $this->getCityCodeByName($cityName);
        
        return $this;
    }
    
    /**
     * 设置经纬度（流畅接口）
     * @param float $latitude 纬度
     * @param float $longitude 经度
     * @return self
     */
    public function forCoordinates(float $latitude, float $longitude): self
    {
        $this->locationInfo['latitude'] = $latitude;
        $this->locationInfo['longitude'] = $longitude;
        $this->locationInfo['cityName'] = null;
        $this->locationInfo['clientIp'] = null;
        $this->currentCityCode = $this->getCityCodeByCoordinates($latitude, $longitude);
        
        return $this;
    }
    
    /**
     * 设置客户端IP（流畅接口）
     * @param string|null $clientIp 客户端IP地址，null表示自动获取
     * @return self
     */
    public function forIp(?string $clientIp): self
    {
        $this->locationInfo['clientIp'] = $clientIp;
        $this->locationInfo['cityName'] = null;
        $this->locationInfo['latitude'] = null;
        $this->locationInfo['longitude'] = null;
        $this->currentCityCode = $this->getCityCodeByIp($clientIp);
        
        return $this;
    }
    
    /**
     * 使用当前位置（流畅接口）
     * @return self
     */
    public function forCurrentLocation(): self
    {
        $this->locationInfo['clientIp'] = $this->getRealClientIp();
        $this->locationInfo['cityName'] = null;
        $this->locationInfo['latitude'] = null;
        $this->locationInfo['longitude'] = null;
        $this->currentCityCode = $this->getCityCodeByIp($this->locationInfo['clientIp']);
        
        return $this;
    }
    
    /**
     * 设置城市代码（流畅接口）
     * @param string $cityCode 城市代码
     * @return self
     */
    public function forCityCode(string $cityCode): self
    {
        $this->locationInfo['cityName'] = null;
        $this->locationInfo['latitude'] = null;
        $this->locationInfo['longitude'] = null;
        $this->locationInfo['clientIp'] = null;
        $this->currentCityCode = $cityCode;
        
        return $this;
    }
    
    /**
     * 统一获取天气数据（流畅接口）
     * @param string $type 获取类型：
     *   - basic: 当天基础天气数据
     *   - detail: 当天详细天气数据
     *   - 7day: 7天预报
     *   - hourly: 逐小时预报
     *   - 15day: 15天预报
     *   - comprehensive: 同时获取detail、7day、hourly、15day的数据
     * @param int $hourlyLimit 逐小时天气数据的条数限制，仅在type为hourly或comprehensive时有效，默认24条
     * @return array
     * @throws WeatherException
     */
    public function getWeather(string $type = 'comprehensive', int $hourlyLimit = 24): array
    {
        if ($this->currentCityCode === null) {
            throw WeatherException::locationFailed('未设置位置信息');
        }
        
        switch ($type) {
            case 'basic':
                return $this->getBasicWeatherData($this->currentCityCode);
            case 'detail':
                return $this->getWeatherDetailData($this->currentCityCode);
            case '7day':
                return $this->get7DayWeather($this->currentCityCode);
            case 'hourly':
                return $this->getHourlyWeather($this->currentCityCode, $hourlyLimit);
            case '15day':
                return $this->get15DayWeather($this->currentCityCode);
            case 'comprehensive':
                // 同时获取detail、7day、hourly、15day的数据
                return [
                    'detail' => $this->getWeatherDetailData($this->currentCityCode),
                    '7day' => $this->get7DayWeather($this->currentCityCode),
                    'hourly' => $this->getHourlyWeather($this->currentCityCode, $hourlyLimit),
                    '15day' => $this->get15DayWeather($this->currentCityCode)
                ];
            default:
                throw WeatherException::invalidParameter('type', $type);
        }
    }
    
    /**
     * 静态工厂方法（流畅接口）
     * @return self
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * 获取真实客户端IP地址
     * 处理代理和CDN的情况
     * @return string|null
     */
    protected function getRealClientIp(): ?string
    {
        // 检查常见的代理头
        $ipHeaders = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipHeaders as $header) {
            if (isset($_SERVER[$header])) {
                // 对于X-Forwarded-For，取第一个IP
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                // 验证IP格式
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return null;
    }
    

    /**
     * 获取访客数据
     * 使用Ip2Region获取IP对应的城市信息
     * @param string|null $clientIp 可选的客户端IP地址，用于获取真实访客位置
     * @return array
     * @throws WeatherException
     */
    public function getVisitorData(?string $clientIp): array
    {
        // 如果提供了新的客户端IP，或者还没有获取过访客数据，就重新获取
        if ($clientIp !== null || empty($this->visitorData)) {
            try {
                // 如果没有提供IP，尝试从服务器获取真实客户端IP
                if ($clientIp === null) {
                    $clientIp = $this->getRealClientIp();
                    if ($clientIp === null) {
                        throw WeatherException::locationFailed('无法获取客户端IP地址');
                    }
                }
                
                // 使用Ip2Region获取IP定位信息
                $ip2region = new Ip2Region();
                $ipData = $ip2region->memorySearch($clientIp);
                
                if (empty($ipData['region'])) {
                    throw WeatherException::locationFailed('ip');
                }
                
                // 解析Ip2Region返回的数据（格式：国家|区域|省份|城市|ISP）
                $regionParts = explode('|', $ipData['region']);
                $province = $regionParts[2] ?? '';
                $city = $regionParts[3] ?? '';
                
                // 处理Ip2Region数据格式问题：第4部分可能是ISP而非城市
                // 检查第4部分是否为ISP名称（如电信、联通、移动等）
                $ispKeywords = ['电信', '联通', '移动', '铁通', '网通', '卫通'];
                $isIsp = false;
                foreach ($ispKeywords as $keyword) {
                    if (strpos($city, $keyword) !== false) {
                        $isIsp = true;
                        break;
                    }
                }
                
                // 如果第4部分是ISP或为空，使用第3部分作为城市
                if ($isIsp || empty($city)) {
                    $city = $province;
                }
                
                // 去除城市名中的"市"字，以匹配城市代码映射
                $city = str_replace('市', '', $city);
                
                $location = [
                    'ip' => $clientIp,
                    'province' => $province,
                    'city' => $city,
                    'district' => '',
                    'cityCode' => '' // Ip2Region不直接提供城市代码
                ];
                
                // 如果获取到了城市名称但没有城市代码，尝试从映射中查找
                if (!empty($location['city'])) {
                    // 加载城市代码映射
                    $this->loadCityCodeMap();
                    // 尝试从城市代码映射中查找
                    if (isset($this->cityCodeMap[$location['city']])) {
                        $location['cityCode'] = $this->cityCodeMap[$location['city']];
                    }
                }
                
                $this->visitorData = $location;
            } catch (WeatherException $e) {
                throw $e;
            } catch (\Exception $e) {
                throw WeatherException::locationFailed('visitor_data');
            }
        }
        
        return $this->visitorData;
    }
    /**
     * 获取aqi值对应的文字描述
     * @param int $aqi aqi值
     * @return string 文字描述
     */
    public function getAqiDescription(int $aqi): string
    {
        if ($aqi === 0) {
            return '未知';
        }
        if ($aqi <= 50) {
            return '优';
        } elseif ($aqi <= 100) {
            return '良';
        } elseif ($aqi <= 150) {
            return '轻度污染';
        } elseif ($aqi <= 200) {
            return '中度污染';
        } elseif ($aqi <= 300) {
            return '重度污染';
        } else {
            return '严重污染';
        }
    }

    /**
     * 获取当前天气数据接口
     * @param string $cityCode 城市代码
     * @return array
     * @throws WeatherException
     */
    public function getCurrentWeather(string $cityCode): array
    {
        try {
            // 获取当前天气基础数据（第二步）
            $basicData = $this->getBasicWeatherData($cityCode);
            
            // 获取当前天气详情数据（第五步）
            $detailData = $this->getWeatherDetailData($cityCode);
            
            // 合并数据
            return array_merge($basicData, $detailData);
        } catch (\Exception $e) {
            if ($e instanceof WeatherException) {
                throw $e;
            }
            throw new WeatherException('获取当前天气数据失败', 1002, $e);
        }
    }

    /**
     * 获取7日天气数据接口
     * @param string $cityCode 城市代码
     * @return array
     * @throws WeatherException
     */
    public function get7DayWeather(string $cityCode): array
    {
        try {
            // 获取15日天气数据（第三步）
            $allDayData = $this->getMultiDayWeatherData($cityCode);
            
            // 只返回前7天数据
            return array_slice($allDayData, 0, 7);
        } catch (\Exception $e) {
            if ($e instanceof WeatherException) {
                throw $e;
            }
            throw new WeatherException('获取7日天气数据失败', 1002, $e);
        }
    }
    
    /**
     * 获取15日天气数据接口
     * @param string $cityCode 城市代码
     * @return array
     * @throws WeatherException
     */
    public function get15DayWeather(string $cityCode): array
    {
        try {
            // 获取15日天气数据（第三步）
            $allDayData = $this->getMultiDayWeatherData($cityCode);
            
            // 返回前15天数据
            return array_slice($allDayData, 0, 15);
        } catch (\Exception $e) {
            if ($e instanceof WeatherException) {
                throw $e;
            }
            throw new WeatherException('获取15日天气数据失败', 1002, $e);
        }
    }

    /**
     * 获取逐小时天气数据接口
     * @param string $cityCode 城市代码
     * @param int $limit 返回条目数量，默认24条
     * @return array
     * @throws WeatherException
     */
    public function getHourlyWeather(string $cityCode, int $limit = 24): array
    {
        try {
            // 获取逐小时天气数据（第四步）
            $hourlyData = $this->getHourlyData($cityCode);
            
            // 根据限制数量返回数据
            return array_slice($hourlyData, 0, $limit);
        } catch (\Exception $e) {
            if ($e instanceof WeatherException) {
                throw $e;
            }
            throw new WeatherException('获取逐小时天气数据失败', 1002, $e);
        }
    }

    /**
     * 获取全部数据接口
     * 含当前天气数据详情，15日天气数据及逐小时天气数据
     * @param string $cityCode 城市代码
     * @return array
     * @throws WeatherException
     */
    public function getAllWeather(string $cityCode): array
    {
        try {
            // 获取当前天气数据
            $currentWeather = $this->getCurrentWeather($cityCode);
            
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
            if ($e instanceof WeatherException) {
                throw $e;
            }
            throw new WeatherException('获取全部天气数据失败', 1002, $e);
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
     * 通过经纬度获取城市ID
     * @param float $latitude 纬度
     * @param float $longitude 经度
     * @return string 城市代码
     * @throws \Exception
     */
    public function getCityCodeByCoordinates(float $latitude, float $longitude): string
    {
        try {
            // 构建请求URL
            $params = [
                'method' => 'stationinfo',
                'callback' => 'getData',
                'lat' => $latitude,
                'lng' => $longitude
            ];
            $encodedParams = urlencode(json_encode($params));
            $timestamp = time() * 1000;
            $url = "https://d7.weather.com.cn/geong/v1/api?params={$encodedParams}&callback=getData&_={$timestamp}";
            
            // 设置请求头
            $headers = [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: zh-CN,zh;q=0.9',
                'Connection: keep-alive',
                'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1',
                'Referer: https://www.weather.com.cn/',
                'Host: d7.weather.com.cn'
            ];
            
            // 发送请求
            $response = $this->sendRequest($url, $headers);
            
            // 解析JSONP响应
            // 移除函数包装器 getData(...)
            $jsonString = preg_replace('/^getData\((.*)\)$/', '$1', $response);
            $data = json_decode($jsonString, true);
            
            if ($data === null) {
                throw WeatherException::dataParseFailed('经纬度城市ID数据');
            }
            
            // 提取城市代码 - 检查不同的响应结构
            $cityCode = '';
            if (isset($data['stationInfo']['station_id'])) {
                $cityCode = $data['stationInfo']['station_id'];
            } elseif (isset($data['result']['station_id'])) {
                $cityCode = $data['result']['station_id'];
            } elseif (isset($data['data']['station_id'])) {
                $cityCode = $data['data']['station_id'];
            } elseif (isset($data['data']['station']['areaid'])) {
                // 修正：根据实际API响应，城市代码在data.station.areaid字段中
                $cityCode = $data['data']['station']['areaid'];
            } else {
                // 添加调试信息查看完整响应结构
                throw WeatherException::locationFailed('未找到该经纬度对应的城市代码');
            }
            
            if (empty($cityCode)) {
                throw WeatherException::locationFailed('未找到该经纬度对应的城市代码');
            }
            
            return $cityCode;
        } catch (WeatherException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw WeatherException::locationFailed('通过经纬度获取城市ID失败: ' . $e->getMessage());
        }
    }

    /**
     * 通过城市名称获取城市ID
     * @param string $cityName 城市名称
     * @return string 城市代码
     * @throws \Exception
     */
    public function getCityCodeByName(string $cityName): string
    {
        // 加载城市代码映射
        $this->loadCityCodeMap();
        
        // 标准化城市名称
        $normalizedCityName = $this->normalizeCityName($cityName);
        
        // 首先尝试直接匹配
        if (!empty($this->cityCodeMap[$cityName])) {
            return $this->cityCodeMap[$cityName];
        }
        
        // 尝试使用标准化的城市名称匹配
        if (!empty($this->cityCodeMap[$normalizedCityName])) {
            return $this->cityCodeMap[$normalizedCityName];
        }
        
        // 尝试不区分大小写的匹配
        foreach ($this->cityCodeMap as $name => $code) {
            if (strtolower($this->normalizeCityName($name)) === strtolower($normalizedCityName)) {
                return $code;
            }
        }
        
        throw WeatherException::cityNotFound($cityName);
    }
    
    /**
     * 标准化城市名称
     * @param string $cityName 城市名称
     * @return string 标准化后的城市名称
     */
    protected function normalizeCityName(string $cityName): string
    {
        // 去除城市名称中的"市"字
        $cityName = str_replace('市', '', $cityName);
        
        // 去除"区"、"县"等后缀
        $cityName = str_replace(['区', '县', '省'], '', $cityName);
        
        // 去除空格和特殊字符
        $cityName = preg_replace('/[^\p{Han}a-zA-Z0-9]/u', '', $cityName);
        
        return $cityName;
    }

    /**
     * 通过IP地址获取城市ID
     * @param string|null $clientIp 可选的客户端IP地址，用于获取真实访客位置
     * @return string 城市代码
     * @throws \Exception
     */
    public function getCityCodeByIp(?string $clientIp): string
    {
        // 使用访客数据获取城市代码
        $visitorData = $this->getVisitorData($clientIp);
        if (!empty($visitorData['cityCode'])) {
            return $visitorData['cityCode'];
        } else {
            throw WeatherException::locationFailed('无法获取该IP对应的城市代码');
        }
    }

    /**
     * 获取城市代码
     * @param string|null $cityCode 城市代码
     * @param string|null $cityName 城市名称
     * @param string|null $clientIp 可选的客户端IP地址，用于获取真实访客位置
     * @return string
     * @throws \Exception
     */
    protected function getCityCode(?string $cityCode, ?string $cityName, ?string $clientIp): string
    {
        // 如果提供了城市代码，直接返回
        if (!empty($cityCode)) {
            return $cityCode;
        }
        
        // 如果提供了城市名称，查找城市代码
        if (!empty($cityName)) {
            // 加载城市代码映射
            $this->loadCityCodeMap();
            
            if (!empty($this->cityCodeMap[$cityName])) {
                return $this->cityCodeMap[$cityName];
            } else {
                throw WeatherException::cityNotFound($cityName);
            }
        }
        
        // 否则使用访客所在城市的代码
        $visitorData = $this->getVisitorData($clientIp);
        if (!empty($visitorData['cityCode'])) {
            return $visitorData['cityCode'];
        } else {
            throw WeatherException::locationFailed('无法获取城市代码');
        }
    }

    /**
     * 获取当前天气基础数据
     * 数据源：https://d1.weather.com.cn/sk_2d/{cityCode}.html
     * API返回的完整字段包括：cityname(城市名), temp(温度), weather(天气), WD(风向), WS(风力等级), SD(湿度), time(时间), date(日期),
     * aqi(空气质量指数), aqi_pm25(PM2.5), rain(降水量), rain24h(24小时降水量), qy(气压), njd(能见度), nameen(英文城市名),
     * tempf(华氏温度), wde(风向英文), wse(风速, km/h), weathere(天气英文), weathercode(天气代码), limitnumber(限行信息)
     * 
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
                    throw WeatherException::dataParseFailed('当前天气基础数据');
                }
                
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
                        $this->loadCityCodeMap();
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
                    'windPower' => self::WIND_POWER_CODES[$data['WS']] ?? $data['WS'] ?? '',
                    'humidity' => $data['SD'] ?? '',
                    'time' => $data['time'] ?? date('H:i'),
                    'date' => $data['date'] ?? date('Y-m-d'),
                    'aqi' => $data['aqi'] ?? '',
                    'air' => $this->getAqiDescription($data['aqi'] ?? 0),
                    'pm25' => $data['aqi_pm25'] ?? '',
                    'rain' => $data['rain'] ?? '',
                    'rain24h' => $data['rain24h'] ?? '',
                    'atmosphericPressure' => $data['qy'] ?? '',
                    'visibility' => $data['njd'] ?? '',
                    'cityNameEn' => $data['nameen'] ?? '',
                    'temperatureF' => $data['tempf'] ?? '',
                    'windDirectionEn' => $data['wde'] ?? '',
                    'windSpeed' => $data['wse'] ?? '',
                    'weatherEn' => $data['weathere'] ?? '',
                    'weatherCode' => $data['weathercode'] ?? '',
                    'limitNumber' => $data['limitnumber'] ?? ''
                ];
            } else {
                throw WeatherException::dataParseFailed('未找到当前天气数据');
            }
        } catch (WeatherException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw WeatherException::apiRequestFailed('获取当前天气数据', $e->getMessage());
        }
    }

    /**
     * 获取15日天气数据
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
                throw WeatherException::dataParseFailed('多日天气数据');
            }
                
                // 天气数据在f数组中
                $data = $fcData['f'] ?? [];
                if (empty($data)) {
                    throw WeatherException::dataParseFailed('未找到多日天气数据');
                }
                
                // 处理数据
                $result = [];
                foreach ($data as $item) {
                    $dayWeather = self::WEATHER_CODES[$item['fa']] ?? '未知';
                    $nightWeather = self::WEATHER_CODES[$item['fb']] ?? '未知';
                    
                    $weather = $dayWeather;
                    if ($dayWeather !== $nightWeather) {
                        $weather .= '转' . $nightWeather;
                    }
                    
                    $result[] = [
                        'date' => $item['fi'] ?? '',
                        'day' => $item['fj'] ?? '',
                        'weather' => $weather,
                        'weatherDay' => $dayWeather,
                        'weatherNight' => $nightWeather,
                        'tempMax' => $item['fc'] ?? '',
                        'tempMin' => $item['fd'] ?? '',
                        'windDay' => $item['fe'] ?? '',
                        'windNight' => $item['ff'] ?? '',
                        'windPowerDay' => self::WIND_POWER_CODES[$item['fg']] ?? $item['fg'] ?? '',
                        'windPowerNight' => self::WIND_POWER_CODES[$item['fh']] ?? $item['fh'] ?? '',
                        'humidityDay' => $item['fm'] ?? '',
                        'humidityNight' => $item['fn'] ?? ''
                    ];
                }
                
                return $result;
        } catch (WeatherException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw WeatherException::apiRequestFailed('获取多日天气数据', $e->getMessage());
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
            throw WeatherException::dataParseFailed('逐小时天气数据');
        }
            
                // 逐小时天气数据在jh数组中
                $data = $fc180['jh'] ?? [];
                if (empty($data)) {
                    throw WeatherException::dataParseFailed('未找到逐小时天气数据');
                }
                
                // 使用类常量的天气状况代码映射
                
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
                                    'dataTime' => $fullTime,    
                                    'time' => $displayTime,
                                    'temperature' => $item['jd'] ?? '',
                                    'weather' => self::WEATHER_CODES[$item['ja']] ?? '未知',
                                    'windDirection' => $item['jh'] ?? '',
                                    'windPower' => self::WIND_POWER_CODES[$item['ji']] ?? $item['ji'] ?? '',
                                    'humidity' => $item['je'] ?? ''
                                ];
                            }
                        }
                    }
                    // 不保留时间信息不完整的数据，直接跳过
                }
                
                return $result;
        } catch (WeatherException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw WeatherException::apiRequestFailed('获取逐小时天气数据', $e->getMessage());
        }
    }

    /**
     * 获取当前天气详情数据
     * 数据源：https://d1.weather.com.cn/weather_index/{cityCode}.html?_={timestamp}
     * 
     * 该方法获取空气质量数据和详细天气指数数据，包括以下信息：
     * - 空气质量数据：AQI指数、空气质量等级、PM2.5、PM10、O3、NO2、SO2、CO等污染物浓度
     * - 天气指数数据：包含多个天气指数（如穿衣指数、紫外线指数、舒适度指数等）
     *   每个指数包含：指数名称(iname)、指数值(ivalue)、指数等级(level)、详细描述(desc)
     * 
     * @param string $cityCode 城市代码，格式如"101260104"（贵阳）
     * @return array 包含空气质量数据和天气指数数据的数组
     * - airQuality: 空气质量数据数组
     *   - aqi: AQI指数
     *   - quality: 空气质量等级（如优、良、轻度污染等）
     *   - pm25: PM2.5浓度（μg/m³）
     *   - pm10: PM10浓度（μg/m³）
     *   - o3: 臭氧浓度（μg/m³）
     *   - no2: 二氧化氮浓度（μg/m³）
     *   - so2: 二氧化硫浓度（μg/m³）
     *   - co: 一氧化碳浓度（mg/m³）
     * - weatherIndex: 天气指数数组，每个元素包含：
     *   - name: 指数名称
     *   - value: 指数值
     *   - level: 指数等级
     *   - desc: 详细描述
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
            
            // 获取空气质量数据 (从 dataSK 变量中获取)
            if (preg_match('/var\s+dataSK\s*=\s*(\{[^}]+\})/', $response, $matches)) {
                $dataSK = json_decode($matches[1], true);
                if ($dataSK !== null) {
                    // 提取空气质量相关字段
                    $airQualityData = [
                        'aqi' => $dataSK['aqi'] ?? '',
                        'aqi_pm25' => $dataSK['aqi_pm25'] ?? '',
                        'air' => $this->getAqiDescription($dataSK['aqi'] ?? 0),
                        'temperature' => $dataSK['temp'] ?? '',
                        'humidity' => $dataSK['sd'] ?? '',
                        'weather' => $dataSK['weather'] ?? '',
                        'windDirection' => $dataSK['WD'] ?? '',
                        'windSpeed' => $dataSK['WS'] ?? '',
                        'windSpeedExplicit' => $dataSK['wse'] ?? '',
                        'visibility' => $dataSK['njd'] ?? '',
                        'pressure' => $dataSK['qy'] ?? '',
                        'rain' => $dataSK['rain'] ?? '',
                        'rain24h' => $dataSK['rain24h'] ?? '',
                        'time' => $dataSK['time'] ?? '',
                        'date' => $dataSK['date'] ?? ''
                    ];
                    $result['airQuality'] = $airQualityData;
                }
            }
            
            // 获取天气指数数据 (从 dataZS 变量中获取)
            // 使用更健壮的正则表达式匹配完整的dataZS JSON结构
            if (preg_match('/var\s+dataZS\s*=\s*(\{.*\});/', $response, $matches)) {
                // 修复JSON格式 - 移除可能存在的无效字符
                $jsonStr = $matches[1];
                
                // 尝试解析JSON
                $dataZS = json_decode($jsonStr, true);
                if ($dataZS === null) {
                    // 如果解析失败，尝试移除尾部多余内容
                    $jsonStr = preg_replace('/\},\s*\}/', '}}', $jsonStr);
                    $dataZS = json_decode($jsonStr, true);
                }
                
                if ($dataZS !== null && isset($dataZS['zs'])) {
                    // 提取所有天气指数 - 实际结构是直接的属性名（如 ct_name, ct_hint）
                    $weatherIndex = [];
                    $zsData = $dataZS['zs'];
                    
                    // 定义所有可能的指数前缀和对应的名称
                    $indexPrefixes = [
                        'ct' => '穿衣指数',
                        'lk' => '路况指数',
                        'dy' => '钓鱼指数',
                        'cl' => '晨练指数',
                        'nl' => '夜生活指数',
                        'uv' => '紫外线强度指数',
                        'gm' => '感冒指数',
                        'gj' => '逛街指数',
                        'pl' => '空气污染扩散条件指数',
                        'tr' => '旅游指数',
                        'co' => '舒适度指数',
                        'pj' => '啤酒指数',
                        'hc' => '划船指数',
                        'gl' => '太阳镜指数',
                        'wc' => '风寒指数',
                        'pk' => '放风筝指数',
                        'ac' => '空调开启指数',
                        'ls' => '晾晒指数',
                        'xc' => '洗车指数',
                        'xq' => '心情指数',
                        'zs' => '中暑指数',
                        'jt' => '交通指数',
                        'yh' => '约会指数',
                        'yd' => '运动指数',
                        'ag' => '过敏指数',
                        'mf' => '美发指数',
                        'ys' => '雨伞指数',
                        'fs' => '防晒指数',
                        'pp' => '化妆指数',
                        'gz' => '干燥指数'
                    ];
                    
                    // 遍历所有指数前缀，提取对应的数据
                    foreach ($indexPrefixes as $prefix => $fullName) {
                        // 检查是否存在该指数的任何属性
                        if (isset($zsData[$prefix . '_name']) || isset($zsData[$prefix . '_hint']) || isset($zsData[$prefix . '_des_s'])) {
                            $weatherIndex[] = [
                                'name' => $zsData[$prefix . '_name'] ?? $fullName,
                                'shortName' => $prefix,
                                'level' => $zsData[$prefix . '_hint'] ?? '',
                                'description' => $zsData[$prefix . '_des_s'] ?? '',
                                'fullDescription' => $zsData[$prefix . '_des'] ?? ''
                            ];
                        }
                    }
                    
                    // 只在有指数数据时添加到结果
                    if (!empty($weatherIndex)) {
                        $result['weatherIndex'] = $weatherIndex;
                    }
                }
            }
            
            // 获取城市基础信息 (从 cityDZ 变量中获取)
            if (preg_match('/var\s+cityDZ\s*=\s*(\{[^}]+\})/', $response, $matches)) {
                $cityDZ = json_decode($matches[1], true);
                if ($cityDZ !== null && isset($cityDZ['weatherinfo'])) {
                    $result['cityInfo'] = $cityDZ['weatherinfo'];
                }
            }
            
            // 获取预警信息 (从 alarmDZ 变量中获取)
            if (preg_match('/var\s+alarmDZ\s*=\s*(\{[^}]+\})/', $response, $matches)) {
                $alarmDZ = json_decode($matches[1], true);
                if ($alarmDZ !== null) {
                    $result['alarmInfo'] = $alarmDZ;
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            // 记录错误但不中断程序
            error_log('获取天气详情数据失败: ' . $e->getMessage());
            // 天气详情数据不是必须的，可以返回空数组
            return [];
        }
    }

    /**
     * 发送HTTP请求
     * @param string $url URL地址
     * @param array $headers 请求头
     * @return string
     * @throws WeatherException
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
            throw WeatherException::apiRequestFailed($url, $error);
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode != 200) {
            throw WeatherException::apiRequestFailed($url, "HTTP状态码: {$httpCode}");
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