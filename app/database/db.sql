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