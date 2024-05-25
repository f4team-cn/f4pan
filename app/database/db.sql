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
    `parse_ua` VARCHAR(255) NULL COMMENT '解析时使用的UA',
    `normal_cookie` VARCHAR(2048) NULL COMMENT '普通Cookie',
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

CREATE TABLE `stats` (
   `total_parsing_traffic` BIGINT NOT NULL DEFAULT 0 COMMENT '总解析流量',
   `total_parsing_count` INT NOT NULL DEFAULT 0 COMMENT '总解析文件数',
   `spent_svip_count` INT NOT NULL DEFAULT 0 COMMENT '已消耗SVIP数量',
   `last_updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后更新时间',
   UNIQUE INDEX(`total_parsing_traffic`, `total_parsing_count`, `spent_svip_count`)
) ENGINE=InnoDB;
-- 初始化统计数据
INSERT INTO `stats` (`total_parsing_traffic`, `total_parsing_count`, `spent_svip_count`) VALUES (0, 0, 0);

CREATE TABLE `visits` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    route VARCHAR(255) NOT NULL,
    visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `api_keys`(
    `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '主键ID',
    `key` VARCHAR(32) NOT NULL COMMENT 'API Key',
    `use_count` INT NOT NULL DEFAULT 0 COMMENT '使用次数',
    UNIQUE (`key`)
)