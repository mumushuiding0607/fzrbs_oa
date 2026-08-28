-- 考评系统数据库安装脚本
-- 执行前请备份数据库

-- 1. 季度考评任务表
CREATE TABLE IF NOT EXISTS `fzrbs_evaluation_task` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `year` INT(11) NOT NULL COMMENT '年份',
  `quarter` INT(11) NOT NULL COMMENT '季度(1-4)',
  `start_date` DATE NOT NULL COMMENT '开始日期',
  `end_date` DATE NOT NULL COMMENT '截止日期',
  `scorer_id` VARCHAR(50) NOT NULL COMMENT '评分人ID',
  `dept_ids` VARCHAR(500) DEFAULT NULL COMMENT '被考评部门IDs（逗号分隔，新字段）',
  `dept_id` INT(11) DEFAULT NULL COMMENT '被考评部门ID（旧字段，兼容保留）',
  `status` TINYINT(1) DEFAULT 0 COMMENT '0未打分 1已打分 2超时默认95分',
  `submitted_at` DATETIME DEFAULT NULL COMMENT '提交时间',
  `created_at` DATETIME DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_year_quarter_scorer` (`year`, `quarter`, `scorer_id`),
  KEY `idx_scorer_status` (`scorer_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='季度考评任务表';

-- 如果表已存在，执行以下 SQL 更新结构
-- ALTER TABLE `fzrbs_evaluation_task`
--   ADD COLUMN `dept_ids` VARCHAR(500) DEFAULT NULL COMMENT '被考评部门IDs（逗号分隔）' AFTER `scorer_id`,
--   MODIFY COLUMN `dept_id` INT(11) DEFAULT NULL COMMENT '被考评部门ID（旧字段，兼容保留）',
--   DROP INDEX `uk_year_quarter_scorer_dept`,
--   ADD UNIQUE INDEX `uk_year_quarter_scorer` (`year`, `quarter`, `scorer_id`);

-- 2. 主评分记录表（部门评分）
CREATE TABLE IF NOT EXISTS `fzrbs_evaluation_score` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `task_id` INT(11) NOT NULL COMMENT '考评任务ID',
  `dept_id` INT(11) NOT NULL COMMENT '被考评部门ID',
  `score` INT(11) NOT NULL COMMENT '分数 95/85/70/60/50',
  `opinion` TEXT COMMENT '意见建议',
  `created_at` DATETIME DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_task_dept` (`task_id`, `dept_id`),
  KEY `idx_task` (`task_id`),
  KEY `idx_dept` (`dept_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='主评分记录表';

-- 如果表已存在且有旧结构，执行以下 ALTER（注意：这会删除所有个人评分数据）
-- ALTER TABLE `fzrbs_evaluation_score`
--   DROP COLUMN `is_personal`,
--   DROP COLUMN `target_user_id`,
--   DROP COLUMN `target_opinion`,
--   DROP INDEX `idx_dept`,
--   ADD UNIQUE KEY `uk_task_dept` (`task_id`, `dept_id`);

-- 2.1 个人评分记录表（独立表）
CREATE TABLE IF NOT EXISTS `fzrbs_evaluation_personal_score` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `task_id` INT(11) NOT NULL COMMENT '任务ID',
  `dept_id` INT(11) NOT NULL COMMENT '部门ID',
  `target_user_id` VARCHAR(64) NOT NULL COMMENT '被评价人ID',
  `score` INT(3) NOT NULL COMMENT '评分',
  `opinion` TEXT COMMENT '意见建议',
  `created_at` DATETIME DEFAULT NULL COMMENT '创建时间',
  `updated_at` DATETIME DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_task_dept_user` (`task_id`, `dept_id`, `target_user_id`),
  KEY `idx_task` (`task_id`),
  KEY `idx_dept` (`dept_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='个人评分记录表';

-- 3. 扣款计算结果表
CREATE TABLE IF NOT EXISTS `fzrbs_evaluation_penalty` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `year` INT(11) NOT NULL COMMENT '年份',
  `quarter` INT(11) NOT NULL COMMENT '季度',
  `dept_id` INT(11) NOT NULL COMMENT '部门ID',
  `dept_name` VARCHAR(100) DEFAULT NULL COMMENT '部门名称',
  `avg_score` DECIMAL(10,2) DEFAULT NULL COMMENT '部门平均分',
  `total_deduct` DECIMAL(10,2) DEFAULT NULL COMMENT '部门总扣款',
  `created_at` DATETIME DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_year_quarter` (`year`, `quarter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='扣款计算结果表';

-- 4. 50分预警表
CREATE TABLE IF NOT EXISTS `fzrbs_evaluation_warning` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `year` INT(11) NOT NULL COMMENT '年份',
  `user_id` VARCHAR(50) NOT NULL COMMENT '被预警员工ID',
  `user_name` VARCHAR(100) DEFAULT NULL COMMENT '员工姓名',
  `cycle_ids` VARCHAR(200) DEFAULT NULL COMMENT '触发预警的周期（如2024Q1,2024Q2）',
  `score_50_count` INT(11) DEFAULT 1 COMMENT '累计50分次数',
  `warning_level` TINYINT(1) DEFAULT 1 COMMENT '1预警 2调离',
  `created_at` DATETIME DEFAULT NULL COMMENT '创建时间',
  `updated_at` DATETIME DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_year_user` (`year`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='50分预警表';

-- 5. 扣罚录入表（管理员录入每个部门的扣罚金额）
CREATE TABLE IF NOT EXISTS `fzrbs_evaluation_penalty_input` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `year` INT(11) NOT NULL COMMENT '年份',
  `quarter` INT(11) NOT NULL COMMENT '季度',
  `dept_id` INT(11) NOT NULL COMMENT '部门ID',
  `dept_name` VARCHAR(100) DEFAULT NULL COMMENT '部门名称',
  `total_deduct` DECIMAL(10,2) NOT NULL COMMENT '部门总扣罚金额',
  `dept_person_count` INT(11) NOT NULL COMMENT '部门总人数',
  `input_by` VARCHAR(64) DEFAULT NULL COMMENT '录入人',
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_year_quarter_dept` (`year`, `quarter`, `dept_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='扣罚录入表';

-- 6. 个人扣罚表（被批评员工信息）
CREATE TABLE IF NOT EXISTS `fzrbs_evaluation_penalty_person` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `input_id` INT(11) NOT NULL COMMENT '录入ID(fzrbs_evaluation_penalty_input)',
  `user_id` VARCHAR(64) NOT NULL COMMENT '员工ID',
  `user_name` VARCHAR(100) DEFAULT NULL COMMENT '员工姓名',
  `deduct_type` TINYINT(1) DEFAULT 0 COMMENT '扣罚方式: 0=岗位绩效×30%, 1=固定金额',
  `post_performance` DECIMAL(10,2) DEFAULT NULL COMMENT '岗位绩效(方式0用)',
  `fixed_deduct` DECIMAL(10,2) DEFAULT NULL COMMENT '固定扣罚金额(方式1用)',
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_input` (`input_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='个人扣罚表';

-- 7. 扣款结果表（最终每人分摊金额）
CREATE TABLE IF NOT EXISTS `fzrbs_evaluation_penalty_result` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `year` INT(11) NOT NULL COMMENT '年份',
  `quarter` INT(11) NOT NULL COMMENT '季度',
  `dept_id` INT(11) NOT NULL COMMENT '部门ID',
  `user_id` VARCHAR(64) NOT NULL COMMENT '员工ID',
  `user_name` VARCHAR(100) DEFAULT NULL COMMENT '员工姓名',
  `deduct_amount` DECIMAL(10,2) NOT NULL COMMENT '扣罚金额',
  `is_personal` TINYINT(1) DEFAULT 0 COMMENT '是否个人追责(1=是)',
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_dept_user_quarter` (`year`, `quarter`, `dept_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='扣款结果表';

-- 5. 初始化dict表配置数据
-- 注意：以下SQL会检查数据是否存在，避免重复插入

-- 被考评部门配置（dept字段存储部门IDs，label='社直'匹配代码查询）
INSERT IGNORE INTO `fzrbs_budget_dict` (`type`, `label`, `value`, `dept`) VALUES ('被考评部门', '被考评部门', '1', '36,67,87');

-- 评分档次配置
INSERT IGNORE INTO `fzrbs_budget_dict` (`type`, `label`, `value`) VALUES ('评分档次', '非常满意', '95');
INSERT IGNORE INTO `fzrbs_budget_dict` (`type`, `label`, `value`) VALUES ('评分档次', '满意', '85');
INSERT IGNORE INTO `fzrbs_budget_dict` (`type`, `label`, `value`) VALUES ('评分档次', '基本满意', '70');
INSERT IGNORE INTO `fzrbs_budget_dict` (`type`, `label`, `value`) VALUES ('评分档次', '不满意', '60');
INSERT IGNORE INTO `fzrbs_budget_dict` (`type`, `label`, `value`) VALUES ('评分档次', '非常不满意', '50');

-- 扣款阈值和比例配置
INSERT IGNORE INTO `fzrbs_budget_dict` (`type`, `label`, `value`) VALUES ('扣款阈值', '触发扣款阈值', '80');
INSERT IGNORE INTO `fzrbs_budget_dict` (`type`, `label`, `value`) VALUES ('扣款比例', '70-80分区间', '5');
INSERT IGNORE INTO `fzrbs_budget_dict` (`type`, `label`, `value`) VALUES ('扣款比例', '60-70分区间', '10');
INSERT IGNORE INTO `fzrbs_budget_dict` (`type`, `label`, `value`) VALUES ('扣款比例', '<60分区间', '30');
