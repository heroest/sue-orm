-- ----------------------------
-- Table structure for user
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `age` int(10) NOT NULL DEFAULT 0,
  `created_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_name`(`name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_bin ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of user
-- ----------------------------
INSERT INTO `user` VALUES (1, 'aaa', 11, '2021-08-24 11:56:53', '2021-08-24 11:56:53');
INSERT INTO `user` VALUES (2, 'bbb', 21, '2021-08-24 11:56:53', '2021-08-24 11:56:53');
INSERT INTO `user` VALUES (3, 'ccc', 31, '2021-08-24 11:56:53', '2021-08-24 11:56:53');
INSERT INTO `user` VALUES (4, 'ddd', 41, '2021-08-24 11:56:53', '2021-08-24 11:56:53');
INSERT INTO `user` VALUES (5, '99', 99, '2021-08-24 11:56:53', '2021-08-24 11:56:53');


