CREATE DATABASE IF NOT EXISTS `dmsoghwg_a2webarebel_survey`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `dmsoghwg_a2webarebel_survey`;

CREATE TABLE IF NOT EXISTS `survey_responses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `respondent_name` VARCHAR(150) NOT NULL,
  `gender_key` VARCHAR(50) NOT NULL,
  `gender_other` VARCHAR(150) DEFAULT NULL,
  `age_years` SMALLINT UNSIGNED NOT NULL,
  `role_key` VARCHAR(80) NOT NULL,
  `role_other` VARCHAR(150) DEFAULT NULL,
  `route_key` VARCHAR(30) NOT NULL,
  `preferred_language` CHAR(2) NOT NULL DEFAULT 'en',
  `submitted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_survey_responses_role` (`role_key`),
  KEY `idx_survey_responses_route` (`route_key`),
  KEY `idx_survey_responses_submitted_at` (`submitted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `survey_answers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `response_id` INT UNSIGNED NOT NULL,
  `question_key` VARCHAR(100) NOT NULL,
  `answer_type` VARCHAR(20) NOT NULL,
  `option_key` VARCHAR(100) DEFAULT NULL,
  `answer_text` TEXT DEFAULT NULL,
  `numeric_value` DECIMAL(10,2) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_survey_answers_response` (`response_id`),
  KEY `idx_survey_answers_question` (`question_key`),
  KEY `idx_survey_answers_question_option` (`question_key`, `option_key`),
  CONSTRAINT `fk_survey_answers_response`
    FOREIGN KEY (`response_id`) REFERENCES `survey_responses` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
