<?php

namespace app\model;
use think\Model;

class StatsModel extends Model
{
    protected $name = 'stats';

    function addTraffic(int $traffic){
        //递增计数
        $total_parsing_traffic = $this->value('total_parsing_traffic');
        $total_parsing_traffic += $traffic;
        $this->where(1)->update(['total_parsing_traffic' => $total_parsing_traffic]);
    }

    function addParsingCount(){
        //递增计数
        $total_parsing_count = $this->value('total_parsing_count');
        $total_parsing_count += 1;
        $this->where(1)->update(['total_parsing_count' => $total_parsing_count]);
    }

    function addSpentSvipCount(){
        $spent_svip_count = $this->value('spent_svip_count');
        $spent_svip_count += 1;
        $this->where(1)->update(['spent_svip_count' => $spent_svip_count]);
    }
}