CREATE TABLE `svip` (
    `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '账号ID',
    `name` varchar(255) NOT NULL COMMENT '用户名',
    `state` enum('0','-1') NOT NULL COMMENT '状态 0 正常 -1 不可使用',
    `cookie` varchar(2048) NOT NULL COMMENT '身份信息',
    `add_time` int(11) NOT NULL COMMENT '添加时间',
    `svip_end_time` int(11) NOT NULL COMMENT 'SVIP过期时间',
    `vip_type` enum('普通用户','普通会员','超级会员') NOT NULL COMMENT 'VIP类型',
    PRIMARY KEY (`id`),
    INDEX `idx_state` (`state`)
) ENGINE=InnoDB AUTO_INCREMENT=172 DEFAULT CHARSET=utf8mb4;

CREATE TABLE `system` (
    `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '系统ID',
    `admin_password` VARCHAR(255) NOT NULL COMMENT '管理员密码',
    `requires_key` enum('fixed','dynamic','none') NOT NULL DEFAULT 'dynamic' COMMENT '是否需要密钥（动态或固定）',
    `notice_id` INT DEFAULT 0 COMMENT '使用的公告ID',
    `key_last_time` INT DEFAULT 300 COMMENT '动态密钥有效时长（秒）',
    `fixed_key` VARCHAR(255) NULL COMMENT '固定的密钥值（如果动态密钥禁用）',
    `real_url_last_time` INT DEFAULT 1800 COMMENT '真实链接存储时间（秒）',
    `is_active` BOOLEAN NOT NULL DEFAULT FALSE COMMENT '是否为当前活动配置',
    PRIMARY KEY (`id`)
);

CREATE TABLE `notice` (
    `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '公告ID',
    `title` varchar(255) NOT NULL COMMENT '公告标题',
    `content` varchar(2048) NOT NULL COMMENT '公告内容',
    `add_time` int(11) NOT NULL COMMENT '添加时间',
    PRIMARY KEY (`id`)
);

CREATE TABLE `daily_stats` (
    `stat_id` INT AUTO_INCREMENT PRIMARY KEY,
    `stat_date` DATE NOT NULL COMMENT '统计日期',
    `total_parsing_traffic` BIGINT NOT NULL DEFAULT 0 COMMENT '总解析流量',
    `unique_ips_count` INT NOT NULL DEFAULT 0 COMMENT '唯一IP地址数量',
    `spent_svip_count` INT NOT NULL DEFAULT 0 COMMENT '今日已消耗SVIP数量',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '统计记录创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '统计记录最后更新时间'
);

CREATE TABLE `api_keys`(
    `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '主键ID',
    `key` VARCHAR(255) NOT NULL COMMENT 'API Key',
    `use_count` INT NOT NULL DEFAULT 0 COMMENT '使用次数',
    UNIQUE (`key`)
)




