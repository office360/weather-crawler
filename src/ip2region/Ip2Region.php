<?php
namespace Ip2Region;
use Ip2Region\XdbSearcher;
use \Exception;
/**
 * class Ip2Region
 * 为兼容老版本调度而创建
 * @author Anyon<zoujingli@qq.com>
 * @datetime 2022/07/18
 */
class Ip2Region
{
    /**
     * 查询实例对象
     * @var XdbSearcher
     */
    private $searcher;

    /**
     * 初始化构造方法
     */
    public function __construct()
    {
        $this->searcher = XdbSearcher::newWithFileOnly(__DIR__ . '/ip2region.xdb');
    }

    /**
     * 兼容原 memorySearch 查询
     * @param string $ip
     * @return array
     */
    public function memorySearch($ip)
    {
        return ['city_id' => 0, 'region' => $this->searcher->search($ip)];
    }

    /**
     * 兼容原 binarySearch 查询
     * @param string $ip
     * @return array
     * @throws Exception
     */
    public function binarySearch($ip)
    {
        return $this->memorySearch($ip);
    }

    /**
     * 兼容原 btreeSearch 查询
     * @param string $ip
     * @return array
     * @throws Exception
     */
    public function btreeSearch($ip)
    {
        return $this->memorySearch($ip);
    }

    /**
     * 直接查询并返回名称
     * @param string $ip
     * @return string
     * @throws \Exception
     */
    public function simple($ip)
    {
        if(!$ip){
            return '';
        }
        $geo = $this->memorySearch($ip);
        $arr = explode('|', str_replace(['0|'], '|', isset($geo['region']) ? $geo['region'] : ''));
        if (($last = array_pop($arr)) === '内网') $last = '';
        return join('', $arr) . (empty($last) ? '' : "【{$last}】");
    }

    public function get($ip)
    {
        if(!$ip){
            return '';
        }
        $geo = $this->memorySearch($ip);
        $arr = explode('|', str_replace(['0|'], '|', isset($geo['region']) ? $geo['region'] : ''));
        unset($arr[1]);
        return array_values($arr);
    }

    /**
     * 更新IP数据库
     * @param string $sourceUrl 源文件URL，默认从GitHub下载最新版本
     * @return array 包含状态和消息的数组
     */
    public function updateDatabase($sourceUrl = 'https://raw.githubusercontent.com/lionsoul2014/ip2region/master/data/ip2region_v4.xdb')
    {
        try {
            // 数据库文件保存路径
            $targetPath = __DIR__ . '/ip2region.xdb';
            $tempPath = $targetPath . '.tmp';
            
            // 使用file_get_contents下载文件（更简单的方式）
            $fileContent = @file_get_contents($sourceUrl);
            
            // 检查下载是否成功
            if ($fileContent === false) {
                throw new Exception('下载数据库文件失败');
            }
            
            // 检查文件大小是否合理（至少10MB）
            if (strlen($fileContent) < 10 * 1024 * 1024) {
                throw new Exception('下载的数据库文件太小，可能不完整');
            }
            
            // 保存到临时文件
            if (file_put_contents($tempPath, $fileContent) === false) {
                throw new Exception('无法保存下载的数据库文件');
            }
            
            // 替换旧文件
            if (file_exists($targetPath)) {
                if (!unlink($targetPath)) {
                    throw new Exception('无法删除旧的数据库文件');
                }
            }
            
            if (!rename($tempPath, $targetPath)) {
                throw new Exception('无法替换数据库文件');
            }
            
            // 重新初始化searcher
            if (isset($this->searcher)) {
                $this->searcher->close();
            }
            $this->searcher = XdbSearcher::newWithFileOnly($targetPath);
            
            return [
                'status' => 'success',
                'message' => 'IP数据库更新成功',
                'fileSize' => filesize($targetPath),
                'updateTime' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            // 清理临时文件
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
            
            return [
                'status' => 'error',
                'message' => 'IP数据库更新失败: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * destruct method
     * resource destroy
     */
    public function __destruct()
    {
        if (isset($this->searcher)) {
            $this->searcher->close();
            unset($this->searcher);
        }
    }
}