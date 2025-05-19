CREATE TABLE `document`
(
    `id`           int                                                            NOT NULL AUTO_INCREMENT,
    `url`          varchar(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `hash`         varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci   NOT NULL,
    `type`         varchar(50) COLLATE utf8mb4_unicode_ci                         NOT NULL,
    `lang`         varchar(10) COLLATE utf8mb4_unicode_ci                         NOT NULL,
    `title`        varchar(512) COLLATE utf8mb4_unicode_ci                        NOT NULL,
    `content`      text COLLATE utf8mb4_unicode_ci,
    `keywords`     varchar(255) COLLATE utf8mb4_unicode_ci                        NOT NULL DEFAULT '',
    `description`  varchar(1024) COLLATE utf8mb4_unicode_ci                       NOT NULL DEFAULT '',
    `crawled_time` datetime                                                       NOT NULL,
    `content_hash` varchar(64) COLLATE utf8mb4_unicode_ci                         NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `hash` (`hash`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;
