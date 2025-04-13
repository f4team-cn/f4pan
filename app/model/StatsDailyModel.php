<?php

namespace app\model;

use think\Model;
use think\facade\Db;

class StatsDailyModel extends Model
{
    protected $name = 'stats_daily';
    protected $pk = 'stat_date';

    /**
     * 增加当日流量
     * @param int $traffic 增加流量
     */
    public function addTraffic(int $traffic)
    {
        $date = date('Y-m-d');
        $this->ensureRecordExists($date);
        
        $this->where('stat_date', $date)
            ->inc('parsing_traffic', $traffic)
            ->update();
    }

    /**
     * 增加当日解析文件数
     * @param int $count
     */
    public function addParsingCount(int $count = 1)
    {
        $date = date('Y-m-d');
        $this->ensureRecordExists($date);

        $this->where('stat_date', $date)
            ->inc('parsing_count', $count)
            ->update();
    }

    /**
     * 确保指定日期的记录存在
     * @param string $date 日期（格式：Y-m-d）
     */
    private function ensureRecordExists(string $date)
    {
        $today = $this->find($date);
        if(!$today){
            Db::name($this->name)
                ->insert([
                    'stat_date' => $date,
                    'parsing_traffic' => 0,
                    'parsing_count' => 0,
            ]);
        }
    }

    /**
     * 获取某日统计数据
     * @param string $date
     */
    public function getByDate(string $date)
    {
        $date = date('Y-m-d');
        $this->ensureRecordExists($date);
        return $this->find($date);
    }
    
    
    public function getPastDaysData(int $days)
    {
        // 参数验证
        $days = max(1, $days); // 至少获取1天数据
        
        // 计算日期范围
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime("-" . ($days - 1) . " days"));

        // 执行查询
        $data = $this->whereBetween('stat_date', [$startDate, $endDate])
            ->order('stat_date', 'DESC')
            ->select()
            ->toArray();
        
        //添加format
        foreach ($data as $k=>$va){
            $data[$k] += ['format_parsing_traffic' => formatSize($data[$k]["parsing_traffic"])];
        }
        // 处理可能存在的日期间隙（补全缺失日期）
        return $this->fillDateGaps($data, $startDate, $endDate);
    }

    /**
     * 补全日期间隙数据
     */
    private function fillDateGaps(array $data, string $startDate, string $endDate)
    {
        $filled = [];
        $current = $endDate;
        
        // 生成完整日期范围
        while ($current >= $startDate) {
            $found = array_filter($data, function($item) use ($current) {
                return $item['stat_date'] === $current;
            });
            
            if (!empty($found)) {
                $filled[] = array_shift($found);
            } else {
                $filled[] = [
                    'stat_date' => $current,
                    'parsing_traffic' => 0,
                    'parsing_count' => 0,
                    'format_parsing_traffic' => formatSize(0)
                ];
            }
            
            $current = date('Y-m-d', strtotime($current . " -1 day"));
        }

        return $filled;
    }
}