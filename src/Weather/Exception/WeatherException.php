<?php
namespace Weather\Exception;

/**
 * 天气服务异常类
 * 提供更具体的天气服务错误信息
 */
class WeatherException extends \Exception
{
    // 错误码常量
    const ERROR_CITY_NOT_FOUND = 1001;        // 城市未找到
    const ERROR_API_REQUEST_FAILED = 1002;    // API请求失败
    const ERROR_DATA_PARSE_FAILED = 1003;     // 数据解析失败
    const ERROR_INVALID_PARAMETER = 1004;     // 参数无效
    const ERROR_LOCATION_FAILED = 1005;       // 位置获取失败
    const ERROR_UNKNOWN = 9999;               // 未知错误
    
    /**
     * 错误信息映射
     * @var array
     */
    protected static $errorMessages = [
        self::ERROR_CITY_NOT_FOUND => '未找到指定城市',
        self::ERROR_API_REQUEST_FAILED => 'API请求失败',
        self::ERROR_DATA_PARSE_FAILED => '数据解析失败',
        self::ERROR_INVALID_PARAMETER => '无效的参数',
        self::ERROR_LOCATION_FAILED => '位置获取失败',
        self::ERROR_UNKNOWN => '未知错误'
    ];
    
    /**
     * 构造函数
     * @param string $message 错误信息
     * @param int $code 错误码
     * @param \Throwable|null $previous 前一个异常
     */
    public function __construct(string $message = '', int $code = self::ERROR_UNKNOWN, \Throwable $previous = null)
    {
        // 如果没有提供错误信息，使用默认错误信息
        if (empty($message)) {
            $message = self::$errorMessages[$code] ?? self::$errorMessages[self::ERROR_UNKNOWN];
        }
        
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * 创建城市未找到异常
     * @param string $cityName 城市名称
     * @return self
     */
    public static function cityNotFound(string $cityName): self
    {
        return new self("未找到城市: {$cityName}", self::ERROR_CITY_NOT_FOUND);
    }
    
    /**
     * 创建API请求失败异常
     * @param string $url 请求URL
     * @param string $error 错误信息
     * @return self
     */
    public static function apiRequestFailed(string $url, string $error): self
    {
        return new self("API请求失败 ({$url}): {$error}", self::ERROR_API_REQUEST_FAILED);
    }
    
    /**
     * 创建数据解析失败异常
     * @param string $dataType 数据类型
     * @return self
     */
    public static function dataParseFailed(string $dataType): self
    {
        return new self("数据解析失败 ({$dataType})", self::ERROR_DATA_PARSE_FAILED);
    }
    
    /**
     * 创建无效参数异常
     * @param string $parameter 参数名称
     * @param string $value 参数值
     * @return self
     */
    public static function invalidParameter(string $parameter, string $value): self
    {
        return new self("无效的参数 {$parameter}: {$value}", self::ERROR_INVALID_PARAMETER);
    }
    
    /**
     * 创建位置获取失败异常
     * @param string $type 位置获取类型（ip、coordinates等）
     * @return self
     */
    public static function locationFailed(string $type): self
    {
        return new self("位置获取失败 ({$type})", self::ERROR_LOCATION_FAILED);
    }
}