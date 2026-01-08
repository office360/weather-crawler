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
     * 使用ipapi.co获取IP定位信息
     * @param string $ip
     * @return array|null
     */
    protected function getLocationByIpApi(string $ip): ?array
    {
        try {
            $url = "https://ipapi.co/{$ip}/json/";
            $headers = [
                'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1'
            ];
            
            $response = $this->sendRequest($url, $headers);
            $data = json_decode($response, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($data['city'])) {
                return [
                    'ip' => $data['ip'],
                    'province' => $data['region'] ?? '',
                    'city' => $data['city'],
                    'district' => '',
                    'cityCode' => '' // ipapi.co不提供城市代码
                ];
            }
        } catch (\Exception $e) {
            // 忽略错误，尝试其他API
        }
        
        return null;
    }
    
    /**
     * 使用ipinfo.io获取IP定位信息
     * @param string $ip
     * @return array|null
     */
    protected function getLocationByIpInfo(string $ip): ?array
    {
        try {
            $url = "https://ipinfo.io/{$ip}/json";
            $headers = [
                'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1'
            ];
            
            $response = $this->sendRequest($url, $headers);
            $data = json_decode($response, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($data['city'])) {
                return [
                    'ip' => $data['ip'],
                    'province' => $data['region'] ?? '',
                    'city' => $data['city'],
                    'district' => '',
                    'cityCode' => '' // ipinfo.io不提供城市代码
                ];
            }
        } catch (\Exception $e) {
            // 忽略错误，尝试其他API
        }
        
        return null;
    }
    
    /**
     * 英文城市名称到中文城市名称的映射
     * @var array
     */
    protected $enToZhCityMap = [
        'Beijing' => '北京',
        'Shanghai' => '上海',
        'Guangzhou' => '广州',
        'Shenzhen' => '深圳',
        'Chengdu' => '成都',
        'Tianjin' => '天津',
        'Chongqing' => '重庆',
        'Hangzhou' => '杭州',
        'Wuhan' => '武汉',
        'Xi\'an' => '西安',
        'Nanjing' => '南京',
        'Shenyang' => '沈阳',
        'Qingdao' => '青岛',
        'Dalian' => '大连',
        'Wulumuqi' => '乌鲁木齐',
        'Kunming' => '昆明',
        'Zhengzhou' => '郑州',
        'Changsha' => '长沙',
        'Nanchang' => '南昌',
        'Hefei' => '合肥',
        'Shijiazhuang' => '石家庄',
        'Taiyuan' => '太原',
        'Lanzhou' => '兰州',
        'Nanning' => '南宁',
        'Guiyang' => '贵阳',
        'Harbin' => '哈尔滨',
        'Changchun' => '长春',
        'Jinan' => '济南',
        'Xiamen' => '厦门',
        'Fuzhou' => '福州',
        'Haikou' => '海口',
        'Ningbo' => '宁波',
        'Xuzhou' => '徐州',
        'Tangshan' => '唐山',
        'Wuxi' => '无锡',
        'Suzhou' => '苏州',
        'Changzhou' => '常州',
        'Xiangtan' => '湘潭',
        'Yinchuan' => '银川',
        'Hohhot' => '呼和浩特',
        'Lhasa' => '拉萨',
        'Qiqihar' => '齐齐哈尔',
        'Daqing' => '大庆',
        'Jilin' => '吉林',
        'Dandong' => '丹东',
        'Anshan' => '鞍山',
        'Zhuhai' => '珠海',
        'Dongguan' => '东莞',
        'Foshan' => '佛山',
        'Zhongshan' => '中山',
        'Huizhou' => '惠州',
        'Maoming' => '茂名',
        'Nanchong' => '南充',
        'Leshan' => '乐山',
        'Zigong' => '自贡',
        'Panzhihua' => '攀枝花',
        'Deyang' => '德阳',
        'Suining' => '遂宁',
        'Neijiang' => '内江',
        'Luzhou' => '泸州',
        'Mianyang' => '绵阳',
        'Yibin' => '宜宾',
        'Langfang' => '廊坊',
        'Baoding' => '保定',
        'Qinhuangdao' => '秦皇岛',
        'Zhangjiakou' => '张家口',
        'Chengde' => '承德',
        'Cangzhou' => '沧州',
        'Hengshui' => '衡水',
        'Handan' => '邯郸',
        'Xingtai' => '邢台',
        'Weifang' => '潍坊',
        'Yantai' => '烟台',
        'Dongying' => '东营',
        'Jining' => '济宁',
        'Taian' => '泰安',
        'Weihai' => '威海',
        'Linyi' => '临沂',
        'Dezhou' => '德州',
        'Binzhou' => '滨州',
        'Zibo' => '淄博',
        'Liaocheng' => '聊城',
        'Zaozhuang' => '枣庄',
        'Heze' => '菏泽',
        'Jiaozuo' => '焦作',
        'Kaifeng' => '开封',
        'Anyang' => '安阳',
        'Xinxiang' => '新乡',
        'Pingdingshan' => '平顶山',
        'Luoyang' => '洛阳',
        'Nanyang' => '南阳',
        'Xuchang' => '许昌',
        'Shangqiu' => '商丘',
        'Zhoukou' => '周口',
        'Zhumadian' => '驻马店',
        'Puyang' => '濮阳',
        'Hebi' => '鹤壁',
        'Xinyang' => '信阳',
        'Luohe' => '漯河',
        'Sanmenxia' => '三门峡',
        'Xuzhou' => '徐州',
        'Lianyungang' => '连云港',
        'Suqian' => '宿迁',
        'Huaian' => '淮安',
        'Yancheng' => '盐城',
        'Yangzhou' => '扬州',
        'Taizhou' => '泰州',
        'Nantong' => '南通',
        'Zhenjiang' => '镇江',
        'Changzhou' => '常州',
        'Wuxi' => '无锡',
        'Suzhou' => '苏州',
        'Nanjing' => '南京',
        'Huainan' => '淮南',
        'Bengbu' => '蚌埠',
        'Fuyang' => '阜阳',
        'Anqing' => '安庆',
        'Chuzhou' => '滁州',
        'Luan' => '六安',
        'Maanshan' => '马鞍山',
        'Xuancheng' => '宣城',
        'Tongling' => '铜陵',
        'Wuhu' => '芜湖',
        'Chizhou' => '池州',
        'Huangshan' => '黄山',
        'Hefei' => '合肥',
        'Jiujiang' => '九江',
        'Ganzhou' => '赣州',
        'Yichun' => '宜春',
        'Fuzhou' => '抚州',
        'Xinyu' => '新余',
        'Pingxiang' => '萍乡',
        'Jingdezhen' => '景德镇',
        'Ji\'an' => '吉安',
        'Shangrao' => '上饶',
        'Nanchang' => '南昌',
        'Wenzhou' => '温州',
        'Shaoxing' => '绍兴',
        'Jiaxing' => '嘉兴',
        'Huzhou' => '湖州',
        'Taizhou' => '台州',
        'Jinhua' => '金华',
        'Quzhou' => '衢州',
        'Zhoushan' => '舟山',
        'Lishui' => '丽水',
        'Hangzhou' => '杭州',
        'Yueyang' => '岳阳',
        'Changde' => '常德',
        'Hengyang' => '衡阳',
        'Yiyang' => '益阳',
        'Loudi' => '娄底',
        'Xiangtan' => '湘潭',
        'Changsha' => '长沙',
        'Zhangjiajie' => '张家界',
        'Yueyang' => '岳阳',
        'Huaihua' => '怀化',
        'Chenzhou' => '郴州',
        'Yongzhou' => '永州',
        'Shaoyang' => '邵阳',
        'Guangzhou' => '广州',
        'Shenzhen' => '深圳',
        'Zhuhai' => '珠海',
        'Foshan' => '佛山',
        'Shantou' => '汕头',
        'Jiangmen' => '江门',
        'Zhaoqing' => '肇庆',
        'Huizhou' => '惠州',
        'Zhuhai' => '珠海',
        'Yunfu' => '云浮',
        'Yangjiang' => '阳江',
        'Maoming' => '茂名',
        'Zhanjiang' => '湛江',
        'Chaozhou' => '潮州',
        'Shanwei' => '汕尾',
        'Meizhou' => '梅州',
        'Heyuan' => '河源',
        'Qingyuan' => '清远',
        'Dongguan' => '东莞',
        'Zhongshan' => '中山',
        'Liuzhou' => '柳州',
        'Guilin' => '桂林',
        'Beihai' => '北海',
        'Yulin' => '玉林',
        'Guigang' => '贵港',
        'Laibin' => '来宾',
        'Hezhou' => '贺州',
        'Baise' => '百色',
        'Chongzuo' => '崇左',
        'Nanning' => '南宁',
        'Guiyang' => '贵阳',
        'Zunyi' => '遵义',
        'Liupanshui' => '六盘水',
        'Anshun' => '安顺',
        'Bijie' => '毕节',
        'Qiannan' => '黔南',
        'Qianxinan' => '黔西南',
        'Qianbei' => '黔北',
        'Kaili' => '凯里',
        'Xingyi' => '兴义',
        'Duyun' => '都匀',
        'Zhaotong' => '昭通',
        'Qujing' => '曲靖',
        'Yuxi' => '玉溪',
        'Lijiang' => '丽江',
        'Baoshan' => '保山',
        'Zhaotong' => '昭通',
        'Chuxiong' => '楚雄',
        'Dali' => '大理',
        'Pu\'er' => '普洱',
        'Lincang' => '临沧',
        'Xishuangbanna' => '西双版纳',
        'Dehong' => '德宏',
        'Nujiang' => '怒江',
        'Lijiang' => '丽江',
        'Yunnan' => '云南',
        'Kunming' => '昆明',
        'Xining' => '西宁',
        'Xinjiang' => '新疆',
        'Wulumuqi' => '乌鲁木齐',
        'Karamay' => '克拉玛依',
        'Korla' => '库尔勒',
        'Turpan' => '吐鲁番',
        'Hami' => '哈密',
        'Yinchuan' => '银川',
        'Shizuishan' => '石嘴山',
        'Wuzhong' => '吴忠',
        'Guyuan' => '固原',
        'Zhongwei' => '中卫',
        'Hohhot' => '呼和浩特',
        'Baotou' => '包头',
        'Wuhai' => '乌海',
        'Chifeng' => '赤峰',
        'Xilinhot' => '锡林浩特',
        'Hailar' => '海拉尔',
        'Erdos' => '鄂尔多斯',
        'Ordos' => '鄂尔多斯',
        'Linhe' => '临河',
        'Lhasa' => '拉萨',
        'Shigatse' => '日喀则',
        'Nyingchi' => '林芝',
        'Shannan' => '山南',
        'Naqu' => '那曲',
        'Ali' => '阿里',
        'Changchun' => '长春',
        'Jilin' => '吉林',
        'Siping' => '四平',
        'Liaoyuan' => '辽源',
        'Tonghua' => '通化',
        'Baishan' => '白山',
        'Songyuan' => '松原',
        'Baicheng' => '白城',
        'Yanji' => '延吉',
        'Harbin' => '哈尔滨',
        'Qiqihar' => '齐齐哈尔',
        'Daqing' => '大庆',
        'Heihe' => '黑河',
        'Yichun' => '伊春',
        'Jiamusi' => '佳木斯',
        'Qitaihe' => '七台河',
        'Mudanjiang' => '牡丹江',
        'Hegang' => '鹤岗',
        'Shuangyashan' => '双鸭山',
        'Jixi' => '鸡西',
        'Suihua' => '绥化',
        'Mohe' => '漠河',
        'Shenyang' => '沈阳',
        'Dalian' => '大连',
        'Anshan' => '鞍山',
        'Fushun' => '抚顺',
        'Benxi' => '本溪',
        'Dandong' => '丹东',
        'Jinzhou' => '锦州',
        'Yingkou' => '营口',
        'Fuxin' => '阜新',
        'Liaoyang' => '辽阳',
        'Panjin' => '盘锦',
        'Tieling' => '铁岭',
        'Chaoyang' => '朝阳',
        'Huludao' => '葫芦岛',
        'Xi\'an' => '西安',
        'Xianyang' => '咸阳',
        'Baoji' => '宝鸡',
        'Hanzhong' => '汉中',
        'Weinan' => '渭南',
        'Yan\'an' => '延安',
        'Yulin' => '榆林',
        'Ankang' => '安康',
        'Shangluo' => '商洛',
        'Tongchuan' => '铜川',
        'Xining' => '西宁',
        'Haibei' => '海北',
        'Huangnan' => '黄南',
        'Hainan' => '海南',
        'Guoluo' => '果洛',
        'Yushu' => '玉树',
        'Haixi' => '海西',
        'Xining' => '西宁',
        'Lanzhou' => '兰州',
        'Jiayuguan' => '嘉峪关',
        'Jiuquan' => '酒泉',
        'Zhangye' => '张掖',
        'Wuwei' => '武威',
        'Baiyin' => '白银',
        'Tianshui' => '天水',
        'Pingliang' => '平凉',
        'Qingyang' => '庆阳',
        'Longnan' => '陇南',
        'Linxia' => '临夏',
        'Gannan' => '甘南',
        'Zhengzhou' => '郑州',
        'Kaifeng' => '开封',
        'Luoyang' => '洛阳',
        'Pingdingshan' => '平顶山',
        'Anyang' => '安阳',
        'Hebi' => '鹤壁',
        'Xinxiang' => '新乡',
        'Jiaozuo' => '焦作',
        'Puyang' => '濮阳',
        'Xuchang' => '许昌',
        'Luohe' => '漯河',
        'Sanmenxia' => '三门峡',
        'Nanyang' => '南阳',
        'Shangqiu' => '商丘',
        'Xinyang' => '信阳',
        'Zhoukou' => '周口',
        'Zhumadian' => '驻马店',
        'Wuhan' => '武汉',
        'Huangshi' => '黄石',
        'Shiyan' => '十堰',
        'Yichang' => '宜昌',
        'Xiangyang' => '襄阳',
        'Ezhou' => '鄂州',
        'Jingmen' => '荆门',
        'Xiaogan' => '孝感',
        'Huanggang' => '黄冈',
        'Xianning' => '咸宁',
        'Suizhou' => '随州',
        'Enshi' => '恩施',
        'Huanggang' => '黄冈',
        'Xiantao' => '仙桃',
        'Qianjiang' => '潜江',
        'Tianmen' => '天门',
        'Macau' => '澳门',
        'Hong Kong' => '香港',
        'Taipei' => '台北',
        'New Taipei' => '新北',
        'Taichung' => '台中',
        'Kaohsiung' => '高雄',
        'Tainan' => '台南',
        'Keelung' => '基隆',
        'Hsinchu' => '新竹',
        'Taoyuan' => '桃园'
    ];

    /**
     * 获取访客数据
     * 支持多个IP定位API作为备选
     * @param string|null $clientIp 可选的客户端IP地址，用于获取真实访客位置
     * @return array
     * @throws \Exception
     */
    public function getVisitorData(string $clientIp = null): array
    {
        // 如果提供了新的客户端IP，或者还没有获取过访客数据，就重新获取
        if (!empty($clientIp) || empty($this->visitorData)) {
            try {
                // 如果没有提供IP，尝试从服务器获取真实客户端IP
                if (empty($clientIp)) {
                    $clientIp = $this->getRealClientIp();
                }
                
                $location = null;
                
                // 尝试使用多个IP定位API
                if (!empty($clientIp)) {
                    // 首先尝试使用ipapi.co
                    $location = $this->getLocationByIpApi($clientIp);
                    
                    // 如果失败，尝试使用ipinfo.io
                    if ($location === null) {
                        $location = $this->getLocationByIpInfo($clientIp);
                    }
                }
                
                // 如果所有API都失败，回退到原来的weather.com.cn API
                if ($location === null) {
                    $url = 'https://wgeo.weather.com.cn/ip/?_=' . (time() * 1000);
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
                        
                        $location = [
                            'ip' => $matches[1],
                            'province' => $province,
                            'city' => $city,
                            'district' => $district,
                            'cityCode' => $matches[2]
                        ];
                    } else {
                        throw new \Exception('无法解析访客数据');
                    }
                }
                
                // 如果获取到了城市名称但没有城市代码，尝试从映射中查找
                if (empty($location['cityCode']) && !empty($location['city'])) {
                    // 如果是英文城市名称，转换为中文
                    $cityName = $location['city'];
                    if (isset($this->enToZhCityMap[$cityName])) {
                        $cityName = $this->enToZhCityMap[$cityName];
                        $location['city'] = $cityName; // 更新为中文城市名称
                    }
                    
                    // 尝试从城市代码映射中查找
                    if (!empty($this->cityCodeMap[$cityName])) {
                        $location['cityCode'] = $this->cityCodeMap[$cityName];
                    }
                }
                
                $this->visitorData = $location;
                return $this->visitorData;
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