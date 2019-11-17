SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for eas_admin
-- ----------------------------
DROP TABLE IF EXISTS `eas_admin`;
CREATE TABLE `eas_admin` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '管理员id',
  `role_id` int(11) NOT NULL COMMENT '角色id',
  `admin_code` varchar(30) NOT NULL COMMENT '管理员账号',
  `admin_name` varchar(50) NOT NULL COMMENT '管理员名称',
  `admin_pwd` varchar(100) NOT NULL COMMENT '管理员密码',
  `admin_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态（1-有效；2-无效）',
  `admin_head` varchar(255) DEFAULT NULL COMMENT '管理员头像',
  `admin_mobile` varchar(20) DEFAULT NULL COMMENT '管理员手机',
  `admin_email` varchar(100) DEFAULT NULL COMMENT '管理员邮箱',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `last_login_time` int(11) DEFAULT NULL COMMENT '最后登录时间',
  PRIMARY KEY (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of eas_admin
-- ----------------------------
INSERT INTO `eas_admin` VALUES ('12', '1', 'zhouyixian', '周奕先', '6ef7b81ae4d2903941c27cbacfbd72eb', '1', '', '13610103562', '1125662823@qq.com', '1573897894', '1573958146', null);
INSERT INTO `eas_admin` VALUES ('20', '1', 'admin', '超级管理员', '2f000553fdd6787c6bb25b4c05a74ea2', '1', null, '', '', '1573991538', '1573991538', null);

-- ----------------------------
-- Table structure for eas_menu
-- ----------------------------
DROP TABLE IF EXISTS `eas_menu`;
CREATE TABLE `eas_menu` (
  `menu_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '菜单id',
  `menu_code` varchar(30) NOT NULL COMMENT '菜单编码',
  `menu_name` varchar(30) NOT NULL COMMENT '菜单名称',
  `menu_url` varchar(255) DEFAULT NULL COMMENT '菜单地址',
  `menu_icon` varchar(30) DEFAULT NULL COMMENT '菜单图标',
  `menu_level` int(11) DEFAULT '1' COMMENT '菜单级别（1-一级菜单；2-二级菜单）',
  `parent_id` int(11) DEFAULT '0' COMMENT '父级菜单',
  `order_no` int(11) DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`menu_id`),
  KEY `parent_id_index` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of eas_menu
-- ----------------------------
INSERT INTO `eas_menu` VALUES ('2', 'auth_mgr', '权限管理', '', 'fa fa-home', '1', '0', '1');
INSERT INTO `eas_menu` VALUES ('3', 'menu_mgr', '菜单管理', 'menu/index', 'fa fa-home', '2', '2', '1');
INSERT INTO `eas_menu` VALUES ('12', 'role_mgr', '角色管理', 'role/index', 'fa fa-home', '2', '2', '2');
INSERT INTO `eas_menu` VALUES ('14', 'admin_mgr', '成员管理', 'admin/index', 'fa fa-home', '2', '2', '3');
INSERT INTO `eas_menu` VALUES ('16', 'order_mgr', '订单管理', '', 'fa fa-home', '1', '0', '2');
INSERT INTO `eas_menu` VALUES ('18', 'order_list', '订单列表', 'order/index', 'fa fa-home', '2', '16', '1');
INSERT INTO `eas_menu` VALUES ('20', 'system_mgr', '系统管理', '', 'fa fa-home', '1', '0', '3');
INSERT INTO `eas_menu` VALUES ('22', 'system_config', '子系统配置', 'system/index', 'fa fa-home', '2', '20', '1');
INSERT INTO `eas_menu` VALUES ('24', 'info', '个人资料', 'admin/updateinfo', 'fa fa-home', '1', '0', '4');

-- ----------------------------
-- Table structure for eas_role
-- ----------------------------
DROP TABLE IF EXISTS `eas_role`;
CREATE TABLE `eas_role` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '角色id',
  `role_code` varchar(30) NOT NULL COMMENT '角色编码',
  `role_name` varchar(50) NOT NULL COMMENT '角色名称',
  `role_remark` varchar(100) NOT NULL COMMENT '角色描述',
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of eas_role
-- ----------------------------
INSERT INTO `eas_role` VALUES ('1', 'system_manage', '系统管理员', '系统管理员');
INSERT INTO `eas_role` VALUES ('2', 'order_manage', '订单管理员', '订单管理员');

-- ----------------------------
-- Table structure for eas_role_menu
-- ----------------------------
DROP TABLE IF EXISTS `eas_role_menu`;
CREATE TABLE `eas_role_menu` (
  `role_id` int(11) NOT NULL COMMENT '角色id',
  `menu_id` int(11) NOT NULL COMMENT '菜单id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of eas_role_menu
-- ----------------------------
INSERT INTO `eas_role_menu` VALUES ('2', '16');
INSERT INTO `eas_role_menu` VALUES ('2', '20');
INSERT INTO `eas_role_menu` VALUES ('2', '24');
INSERT INTO `eas_role_menu` VALUES ('2', '18');
INSERT INTO `eas_role_menu` VALUES ('2', '22');
INSERT INTO `eas_role_menu` VALUES ('1', '2');
INSERT INTO `eas_role_menu` VALUES ('1', '16');
INSERT INTO `eas_role_menu` VALUES ('1', '20');
INSERT INTO `eas_role_menu` VALUES ('1', '24');
INSERT INTO `eas_role_menu` VALUES ('1', '3');
INSERT INTO `eas_role_menu` VALUES ('1', '12');
INSERT INTO `eas_role_menu` VALUES ('1', '14');
INSERT INTO `eas_role_menu` VALUES ('1', '18');
INSERT INTO `eas_role_menu` VALUES ('1', '22');
