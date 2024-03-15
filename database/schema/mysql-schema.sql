/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `adventure_location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `adventure_location` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `adventure_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `adventure_location_adventure_id_foreign` (`adventure_id`),
  KEY `adventure_location_location_id_foreign` (`location_id`),
  CONSTRAINT `adventure_location_adventure_id_foreign` FOREIGN KEY (`adventure_id`) REFERENCES `adventures` (`id`),
  CONSTRAINT `adventure_location_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `adventure_monster`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `adventure_monster` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `adventure_id` bigint unsigned NOT NULL,
  `monster_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `adventure_monster_adventure_id_foreign` (`adventure_id`),
  KEY `adventure_monster_monster_id_foreign` (`monster_id`),
  CONSTRAINT `adventure_monster_adventure_id_foreign` FOREIGN KEY (`adventure_id`) REFERENCES `adventures` (`id`),
  CONSTRAINT `adventure_monster_monster_id_foreign` FOREIGN KEY (`monster_id`) REFERENCES `monsters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `announcements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `event_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `announcements_event_id_foreign` (`event_id`),
  CONSTRAINT `announcements_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `event` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `auditable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `auditable_id` bigint unsigned NOT NULL,
  `old_values` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `new_values` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(1023) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tags` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audits_auditable_type_auditable_id_index` (`auditable_type`,`auditable_id`),
  KEY `audits_user_id_user_type_index` (`user_id`,`user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `building_expansion_queues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `building_expansion_queues` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `kingdom_id` bigint unsigned NOT NULL,
  `building_id` bigint unsigned NOT NULL,
  `completed_at` datetime NOT NULL,
  `started_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `buildings_in_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `buildings_in_queue` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `kingdom_id` bigint unsigned NOT NULL,
  `building_id` bigint unsigned NOT NULL,
  `to_level` int NOT NULL,
  `completed_at` datetime NOT NULL,
  `started_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `paid_with_gold` tinyint(1) NOT NULL DEFAULT '0',
  `paid_amount` bigint DEFAULT '0',
  `type` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `biq_cid` (`character_id`),
  KEY `biq_king_id` (`kingdom_id`),
  KEY `biq_build_id` (`building_id`),
  CONSTRAINT `biq_build_id` FOREIGN KEY (`building_id`) REFERENCES `kingdom_buildings` (`id`),
  CONSTRAINT `biq_cid` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`),
  CONSTRAINT `biq_king_id` FOREIGN KEY (`kingdom_id`) REFERENCES `kingdoms` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `celestial_fights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `celestial_fights` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `monster_id` bigint unsigned DEFAULT NULL,
  `character_id` bigint unsigned DEFAULT NULL,
  `conjured_at` date NOT NULL,
  `x_position` int NOT NULL,
  `y_position` int NOT NULL,
  `damaged_kingdom` tinyint(1) NOT NULL DEFAULT '0',
  `stole_treasury` tinyint(1) NOT NULL DEFAULT '0',
  `weakened_morale` tinyint(1) NOT NULL DEFAULT '0',
  `current_health` bigint NOT NULL,
  `max_health` bigint NOT NULL,
  `type` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `celestial_fights_monster_id_foreign` (`monster_id`),
  KEY `celestial_fights_character_id_foreign` (`character_id`),
  CONSTRAINT `celestial_fights_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`),
  CONSTRAINT `celestial_fights_monster_id_foreign` FOREIGN KEY (`monster_id`) REFERENCES `monsters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `character_automations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `character_automations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `monster_id` bigint unsigned DEFAULT NULL,
  `type` int NOT NULL,
  `started_at` datetime NOT NULL,
  `completed_at` datetime NOT NULL,
  `attack_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `move_down_monster_list_every` int DEFAULT '0',
  `previous_level` int DEFAULT '0',
  `current_level` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ca_cid` (`character_id`),
  KEY `ca_mid` (`monster_id`),
  CONSTRAINT `ca_cid` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`),
  CONSTRAINT `ca_mid` FOREIGN KEY (`monster_id`) REFERENCES `monsters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `character_boons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `character_boons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned DEFAULT NULL,
  `started` datetime NOT NULL,
  `complete` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `item_id` bigint unsigned NOT NULL,
  `last_for_minutes` int NOT NULL,
  `amount_used` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `character_boons_character_id_foreign` (`character_id`),
  KEY `character_boons_item_id_foreign` (`item_id`),
  CONSTRAINT `character_boons_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`),
  CONSTRAINT `character_boons_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `character_class_ranks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `character_class_ranks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `game_class_id` bigint unsigned NOT NULL,
  `current_xp` int NOT NULL,
  `required_xp` int NOT NULL,
  `level` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `character_class_ranks_character_id_foreign` (`character_id`),
  KEY `character_class_ranks_game_class_id_foreign` (`game_class_id`),
  CONSTRAINT `character_class_ranks_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`),
  CONSTRAINT `character_class_ranks_game_class_id_foreign` FOREIGN KEY (`game_class_id`) REFERENCES `game_classes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `character_class_ranks_weapon_masteries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `character_class_ranks_weapon_masteries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_class_rank_id` bigint unsigned NOT NULL,
  `weapon_type` int NOT NULL,
  `current_xp` int NOT NULL,
  `required_xp` int NOT NULL,
  `level` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ccrank_id` (`character_class_rank_id`),
  CONSTRAINT `ccrank_id` FOREIGN KEY (`character_class_rank_id`) REFERENCES `character_class_ranks` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `character_class_specialties_equipped`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `character_class_specialties_equipped` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `game_class_special_id` bigint unsigned NOT NULL,
  `level` int NOT NULL,
  `current_xp` int NOT NULL,
  `required_xp` int NOT NULL,
  `equipped` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cc_se_c_id` (`character_id`),
  KEY `cc_se_gcs_id` (`game_class_special_id`),
  CONSTRAINT `cc_se_c_id` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`),
  CONSTRAINT `cc_se_gcs_id` FOREIGN KEY (`game_class_special_id`) REFERENCES `game_class_specials` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `character_in_celestial_fights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `character_in_celestial_fights` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `celestial_fight_id` bigint unsigned NOT NULL,
  `character_id` bigint unsigned NOT NULL,
  `character_max_health` bigint NOT NULL,
  `character_current_health` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `character_in_celestial_fights_celestial_fight_id_foreign` (`celestial_fight_id`),
  KEY `character_in_celestial_fights_character_id_foreign` (`character_id`),
  CONSTRAINT `character_in_celestial_fights_celestial_fight_id_foreign` FOREIGN KEY (`celestial_fight_id`) REFERENCES `celestial_fights` (`id`),
  CONSTRAINT `character_in_celestial_fights_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `character_mercenaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `character_mercenaries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `mercenary_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `current_level` int NOT NULL,
  `current_xp` int NOT NULL,
  `xp_required` int NOT NULL,
  `reincarnated_bonus` decimal(12,4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `xp_increase` decimal(12,4) DEFAULT NULL,
  `times_reincarnated` int DEFAULT NULL,
  `xp_buff` decimal(8,4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `character_mercenaries_character_id_foreign` (`character_id`),
  CONSTRAINT `character_mercenaries_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `character_passive_skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `character_passive_skills` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `passive_skill_id` bigint unsigned NOT NULL,
  `parent_skill_id` bigint unsigned DEFAULT NULL,
  `unlocks_game_building_id` bigint unsigned DEFAULT NULL,
  `current_level` int DEFAULT '0',
  `hours_to_next` int DEFAULT '0',
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `c_cps` (`character_id`),
  KEY `ps_psk` (`passive_skill_id`),
  KEY `ps_cps` (`parent_skill_id`),
  KEY `ugb_gb` (`unlocks_game_building_id`),
  CONSTRAINT `c_cps` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`),
  CONSTRAINT `ps_cps` FOREIGN KEY (`parent_skill_id`) REFERENCES `character_passive_skills` (`id`),
  CONSTRAINT `ps_psk` FOREIGN KEY (`passive_skill_id`) REFERENCES `passive_skills` (`id`),
  CONSTRAINT `ugb_gb` FOREIGN KEY (`unlocks_game_building_id`) REFERENCES `game_buildings` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `characters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `characters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `game_race_id` bigint unsigned NOT NULL,
  `game_class_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `damage_stat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` bigint DEFAULT '1',
  `xp` int NOT NULL,
  `xp_next` bigint NOT NULL,
  `str` bigint NOT NULL,
  `dur` bigint NOT NULL,
  `dex` bigint NOT NULL,
  `chr` bigint NOT NULL,
  `int` bigint NOT NULL,
  `focus` bigint NOT NULL,
  `agi` bigint NOT NULL,
  `ac` bigint NOT NULL,
  `gold` bigint DEFAULT '250',
  `inventory_max` int DEFAULT '75',
  `can_attack` tinyint(1) DEFAULT '1',
  `can_move` tinyint(1) DEFAULT '1',
  `can_craft` tinyint(1) DEFAULT '1',
  `is_dead` tinyint(1) DEFAULT '0',
  `can_move_again_at` datetime DEFAULT NULL,
  `can_attack_again_at` datetime DEFAULT NULL,
  `can_craft_again_at` datetime DEFAULT NULL,
  `force_name_change` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_test` tinyint(1) NOT NULL DEFAULT '0',
  `gold_dust` bigint DEFAULT '0',
  `shards` bigint DEFAULT '0',
  `current_adventure_id` bigint unsigned DEFAULT NULL,
  `is_attack_automation_locked` tinyint(1) NOT NULL DEFAULT '0',
  `is_mass_embezzling` tinyint(1) NOT NULL DEFAULT '0',
  `can_settle_again_at` datetime DEFAULT NULL,
  `copper_coins` bigint NOT NULL DEFAULT '0',
  `killed_in_pvp` tinyint(1) NOT NULL DEFAULT '0',
  `can_spin_again_at` datetime DEFAULT NULL,
  `can_spin` tinyint(1) NOT NULL DEFAULT '1',
  `is_mercenary_unlocked` tinyint(1) DEFAULT '0',
  `can_engage_celestials_again_at` datetime DEFAULT NULL,
  `can_engage_celestials` tinyint(1) DEFAULT '1',
  `xp_penalty` decimal(20,2) DEFAULT '0.00',
  `reincarnated_stat_increase` bigint DEFAULT '0',
  `times_reincarnated` bigint DEFAULT '0',
  `base_stat_mod` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `base_damage_stat_mod` decimal(12,4) NOT NULL DEFAULT '0.0000',
  PRIMARY KEY (`id`),
  UNIQUE KEY `characters_name_unique` (`name`),
  KEY `characters_game_race_id_foreign` (`game_race_id`),
  KEY `characters_game_class_id_foreign` (`game_class_id`),
  KEY `characters_user_id_foreign` (`user_id`),
  CONSTRAINT `characters_game_class_id_foreign` FOREIGN KEY (`game_class_id`) REFERENCES `game_classes` (`id`),
  CONSTRAINT `characters_game_race_id_foreign` FOREIGN KEY (`game_race_id`) REFERENCES `game_races` (`id`),
  CONSTRAINT `characters_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_goal_participation_kills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_goal_participation_kills` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `global_event_goal_id` bigint unsigned NOT NULL,
  `character_id` bigint unsigned NOT NULL,
  `kills` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `event_goal_participation_kills_global_event_goal_id_foreign` (`global_event_goal_id`),
  KEY `event_goal_participation_kills_character_id_foreign` (`character_id`),
  CONSTRAINT `event_goal_participation_kills_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`),
  CONSTRAINT `event_goal_participation_kills_global_event_goal_id_foreign` FOREIGN KEY (`global_event_goal_id`) REFERENCES `global_event_goals` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` int NOT NULL,
  `started_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `raid_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `events_raid_id_foreign` (`raid_id`),
  CONSTRAINT `events_raid_id_foreign` FOREIGN KEY (`raid_id`) REFERENCES `raids` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `faction_loyalties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `faction_loyalties` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `faction_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_pledged` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `faction_loyalties_character_id_foreign` (`character_id`),
  KEY `faction_loyalties_faction_id_foreign` (`faction_id`),
  CONSTRAINT `faction_loyalties_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`),
  CONSTRAINT `faction_loyalties_faction_id_foreign` FOREIGN KEY (`faction_id`) REFERENCES `factions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `faction_loyalty_npc_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `faction_loyalty_npc_tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `faction_loyalty_id` bigint unsigned NOT NULL,
  `faction_loyalty_npc_id` bigint unsigned NOT NULL,
  `fame_tasks` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `faction_loyalty_npc_tasks_faction_loyalty_id_foreign` (`faction_loyalty_id`),
  KEY `faction_loyalty_npc_tasks_faction_loyalty_npc_id_foreign` (`faction_loyalty_npc_id`),
  CONSTRAINT `faction_loyalty_npc_tasks_faction_loyalty_id_foreign` FOREIGN KEY (`faction_loyalty_id`) REFERENCES `faction_loyalties` (`id`),
  CONSTRAINT `faction_loyalty_npc_tasks_faction_loyalty_npc_id_foreign` FOREIGN KEY (`faction_loyalty_npc_id`) REFERENCES `faction_loyalty_npcs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `faction_loyalty_npcs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `faction_loyalty_npcs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `faction_loyalty_id` bigint unsigned NOT NULL,
  `npc_id` bigint unsigned NOT NULL,
  `current_level` int NOT NULL,
  `max_level` int NOT NULL,
  `next_level_fame` int NOT NULL,
  `currently_helping` tinyint(1) NOT NULL DEFAULT '0',
  `kingdom_item_defence_bonus` decimal(12,8) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `faction_loyalty_npcs_faction_loyalty_id_foreign` (`faction_loyalty_id`),
  KEY `faction_loyalty_npcs_npc_id_foreign` (`npc_id`),
  CONSTRAINT `faction_loyalty_npcs_faction_loyalty_id_foreign` FOREIGN KEY (`faction_loyalty_id`) REFERENCES `faction_loyalties` (`id`),
  CONSTRAINT `faction_loyalty_npcs_npc_id_foreign` FOREIGN KEY (`npc_id`) REFERENCES `npcs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `factions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `factions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `game_map_id` bigint unsigned NOT NULL,
  `current_level` int DEFAULT '0',
  `current_points` int DEFAULT '0',
  `points_needed` int DEFAULT '0',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `maxed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ca_fid` (`character_id`),
  KEY `gmi_gm` (`game_map_id`),
  CONSTRAINT `ca_fid` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`),
  CONSTRAINT `gmi_gm` FOREIGN KEY (`game_map_id`) REFERENCES `game_maps` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_building_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_building_units` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `game_building_id` bigint unsigned NOT NULL,
  `game_unit_id` bigint unsigned NOT NULL,
  `required_level` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gu_game_building_id` (`game_building_id`),
  KEY `gu_game_unit_id` (`game_unit_id`),
  CONSTRAINT `gu_game_building_id` FOREIGN KEY (`game_building_id`) REFERENCES `game_buildings` (`id`),
  CONSTRAINT `gu_game_unit_id` FOREIGN KEY (`game_unit_id`) REFERENCES `game_units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_buildings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_buildings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `max_level` int NOT NULL,
  `base_durability` int NOT NULL,
  `base_defence` int NOT NULL,
  `required_population` int NOT NULL,
  `units_per_level` int DEFAULT NULL,
  `only_at_level` int DEFAULT NULL,
  `is_resource_building` tinyint(1) NOT NULL DEFAULT '0',
  `trains_units` tinyint(1) NOT NULL DEFAULT '0',
  `is_walls` tinyint(1) NOT NULL DEFAULT '0',
  `is_church` tinyint(1) NOT NULL DEFAULT '0',
  `is_farm` tinyint(1) NOT NULL DEFAULT '0',
  `wood_cost` int NOT NULL DEFAULT '0',
  `clay_cost` int NOT NULL DEFAULT '0',
  `stone_cost` int NOT NULL DEFAULT '0',
  `iron_cost` int NOT NULL DEFAULT '0',
  `steel_cost` int DEFAULT '0',
  `time_to_build` double NOT NULL DEFAULT '1',
  `time_increase_amount` double NOT NULL DEFAULT '0',
  `decrease_morale_amount` double NOT NULL DEFAULT '0',
  `increase_population_amount` int NOT NULL DEFAULT '0',
  `increase_morale_amount` double NOT NULL DEFAULT '0',
  `increase_wood_amount` double NOT NULL DEFAULT '0',
  `increase_clay_amount` double NOT NULL DEFAULT '0',
  `increase_stone_amount` double NOT NULL DEFAULT '0',
  `increase_iron_amount` double NOT NULL DEFAULT '0',
  `increase_durability_amount` double NOT NULL DEFAULT '0',
  `increase_defence_amount` double NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  `passive_skill_id` bigint unsigned DEFAULT NULL,
  `level_required` int DEFAULT NULL,
  `is_special` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `gb_psk` (`passive_skill_id`),
  CONSTRAINT `gb_psk` FOREIGN KEY (`passive_skill_id`) REFERENCES `passive_skills` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_class_specials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_class_specials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `game_class_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `requires_class_rank_level` int NOT NULL,
  `specialty_damage` int DEFAULT '0',
  `increase_specialty_damage_per_level` int DEFAULT '0',
  `specialty_damage_uses_damage_stat_amount` decimal(8,4) DEFAULT '0.0000',
  `base_damage_mod` decimal(8,4) DEFAULT '0.0000',
  `base_ac_mod` decimal(8,4) DEFAULT '0.0000',
  `base_healing_mod` decimal(8,4) DEFAULT '0.0000',
  `base_spell_damage_mod` decimal(8,4) DEFAULT '0.0000',
  `health_mod` decimal(8,4) DEFAULT '0.0000',
  `base_damage_stat_increase` decimal(8,4) DEFAULT '0.0000',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `attack_type_required` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'any',
  `spell_evasion` decimal(12,8) DEFAULT NULL,
  `affix_damage_reduction` decimal(12,8) DEFAULT NULL,
  `healing_reduction` decimal(12,8) DEFAULT NULL,
  `skill_reduction` decimal(12,8) DEFAULT NULL,
  `resistance_reduction` decimal(12,8) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gc_specials` (`game_class_id`),
  CONSTRAINT `gc_specials` FOREIGN KEY (`game_class_id`) REFERENCES `game_classes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_classes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `damage_stat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_hit_stat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `str_mod` int DEFAULT '0',
  `dur_mod` int DEFAULT '0',
  `dex_mod` int DEFAULT '0',
  `chr_mod` int DEFAULT '0',
  `int_mod` int DEFAULT '0',
  `accuracy_mod` decimal(5,4) DEFAULT '0.0000',
  `dodge_mod` decimal(5,4) DEFAULT '0.0000',
  `defense_mod` decimal(5,4) DEFAULT '0.0000',
  `looting_mod` decimal(5,4) DEFAULT '0.0000',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `agi_mod` int DEFAULT '0',
  `focus_mod` int DEFAULT '0',
  `primary_required_class_id` bigint unsigned DEFAULT NULL,
  `secondary_required_class_id` bigint unsigned DEFAULT NULL,
  `primary_required_class_level` int DEFAULT NULL,
  `secondary_required_class_level` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_maps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_maps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kingdom_color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `default` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `xp_bonus` decimal(5,4) DEFAULT NULL,
  `skill_training_bonus` decimal(5,4) DEFAULT NULL,
  `drop_chance_bonus` decimal(5,4) DEFAULT NULL,
  `enemy_stat_bonus` decimal(5,4) DEFAULT NULL,
  `character_attack_reduction` decimal(8,4) DEFAULT '0.0000',
  `required_location_id` bigint unsigned DEFAULT NULL,
  `only_during_event_type` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `game_maps_required_location_id_foreign` (`required_location_id`),
  CONSTRAINT `game_maps_required_location_id_foreign` FOREIGN KEY (`required_location_id`) REFERENCES `locations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_races`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_races` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `str_mod` int DEFAULT '0',
  `dur_mod` int DEFAULT '0',
  `dex_mod` int DEFAULT '0',
  `chr_mod` int DEFAULT '0',
  `int_mod` int DEFAULT '0',
  `accuracy_mod` decimal(5,4) DEFAULT '0.0000',
  `dodge_mod` decimal(5,4) DEFAULT '0.0000',
  `defense_mod` decimal(5,4) DEFAULT '0.0000',
  `looting_mod` decimal(5,4) DEFAULT '0.0000',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `agi_mod` int DEFAULT '0',
  `focus_mod` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_skills` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `max_level` int NOT NULL,
  `base_damage_mod_bonus_per_level` decimal(10,6) DEFAULT NULL,
  `base_healing_mod_bonus_per_level` decimal(10,6) DEFAULT NULL,
  `base_ac_mod_bonus_per_level` decimal(10,6) DEFAULT NULL,
  `fight_time_out_mod_bonus_per_level` decimal(10,6) DEFAULT NULL,
  `move_time_out_mod_bonus_per_level` decimal(10,6) DEFAULT NULL,
  `can_train` tinyint(1) DEFAULT '1',
  `skill_bonus_per_level` decimal(5,4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `type` int DEFAULT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  `game_class_id` bigint unsigned DEFAULT NULL,
  `unit_time_reduction` decimal(8,4) DEFAULT '0.0000',
  `building_time_reduction` decimal(8,4) DEFAULT '0.0000',
  `unit_movement_time_reduction` decimal(8,4) DEFAULT '0.0000',
  `class_bonus` decimal(12,6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `game_skills_game_class_id_foreign` (`game_class_id`),
  CONSTRAINT `game_skills_game_class_id_foreign` FOREIGN KEY (`game_class_id`) REFERENCES `game_classes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_units` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attack` int NOT NULL,
  `defence` int NOT NULL,
  `can_not_be_healed` tinyint(1) DEFAULT '0',
  `is_settler` tinyint(1) DEFAULT '0',
  `reduces_morale_by` double DEFAULT NULL,
  `can_heal` tinyint(1) DEFAULT '0',
  `heal_percentage` double DEFAULT NULL,
  `siege_weapon` tinyint(1) DEFAULT '0',
  `is_airship` tinyint(1) DEFAULT '0',
  `defender` tinyint(1) DEFAULT '0',
  `attacker` tinyint(1) DEFAULT '0',
  `wood_cost` int DEFAULT '0',
  `clay_cost` int DEFAULT '0',
  `stone_cost` int DEFAULT '0',
  `iron_cost` int DEFAULT '0',
  `steel_cost` int DEFAULT '0',
  `required_population` int DEFAULT '0',
  `time_to_recruit` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_special` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `gem_bag_slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gem_bag_slots` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `gem_bag_id` bigint unsigned NOT NULL,
  `gem_id` bigint unsigned NOT NULL,
  `amount` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gem_bag_slots_gem_bag_id_foreign` (`gem_bag_id`),
  KEY `gem_bag_slots_gem_id_foreign` (`gem_id`),
  CONSTRAINT `gem_bag_slots_gem_bag_id_foreign` FOREIGN KEY (`gem_bag_id`) REFERENCES `gem_bags` (`id`),
  CONSTRAINT `gem_bag_slots_gem_id_foreign` FOREIGN KEY (`gem_id`) REFERENCES `gems` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `gem_bags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gem_bags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gem_bags_character_id_foreign` (`character_id`),
  CONSTRAINT `gem_bags_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `gems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gems` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tier` int NOT NULL,
  `primary_atonement_type` int NOT NULL,
  `secondary_atonement_type` int NOT NULL,
  `tertiary_atonement_type` int NOT NULL,
  `primary_atonement_amount` decimal(12,8) NOT NULL,
  `secondary_atonement_amount` decimal(12,8) NOT NULL,
  `tertiary_atonement_amount` decimal(12,8) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `global_event_goals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `global_event_goals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `max_kills` bigint NOT NULL,
  `reward_every_kills` bigint NOT NULL,
  `next_reward_at` bigint NOT NULL,
  `event_type` int NOT NULL,
  `item_specialty_type_reward` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `should_be_unique` tinyint(1) NOT NULL,
  `unique_type` bigint NOT NULL,
  `should_be_mythic` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `global_event_participation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `global_event_participation` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `global_event_goal_id` bigint unsigned NOT NULL,
  `character_id` bigint unsigned NOT NULL,
  `current_kills` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `global_event_participation_global_event_goal_id_foreign` (`global_event_goal_id`),
  KEY `global_event_participation_character_id_foreign` (`character_id`),
  CONSTRAINT `global_event_participation_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`),
  CONSTRAINT `global_event_participation_global_event_goal_id_foreign` FOREIGN KEY (`global_event_goal_id`) REFERENCES `global_event_goals` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `guide_quests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `guide_quests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `intro_text` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `instructions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `required_level` bigint DEFAULT NULL,
  `required_skill` bigint DEFAULT NULL,
  `required_skill_level` bigint DEFAULT NULL,
  `required_faction_id` bigint DEFAULT NULL,
  `required_faction_level` bigint DEFAULT NULL,
  `required_game_map_id` bigint DEFAULT NULL,
  `required_quest_id` bigint DEFAULT NULL,
  `required_quest_item_id` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `gold_dust_reward` bigint DEFAULT NULL,
  `shards_reward` bigint DEFAULT NULL,
  `required_kingdoms` int DEFAULT NULL,
  `required_kingdom_level` int DEFAULT NULL,
  `required_kingdom_units` int DEFAULT NULL,
  `required_passive_skill` int DEFAULT NULL,
  `required_passive_level` int DEFAULT NULL,
  `faction_points_per_kill` int DEFAULT NULL,
  `required_shards` int DEFAULT NULL,
  `xp_reward` int NOT NULL,
  `gold_reward` bigint DEFAULT NULL,
  `required_gold_dust` int DEFAULT NULL,
  `required_gold` int DEFAULT NULL,
  `required_stats` int DEFAULT NULL,
  `required_str` int DEFAULT NULL,
  `required_dex` int DEFAULT NULL,
  `required_int` int DEFAULT NULL,
  `required_dur` int DEFAULT NULL,
  `required_chr` int DEFAULT NULL,
  `required_agi` int DEFAULT NULL,
  `required_focus` int DEFAULT NULL,
  `required_secondary_skill` bigint unsigned DEFAULT NULL,
  `required_secondary_skill_level` bigint DEFAULT NULL,
  `secondary_quest_item_id` bigint unsigned DEFAULT NULL,
  `required_skill_type` int DEFAULT NULL,
  `required_skill_type_level` int DEFAULT NULL,
  `required_mercenary_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `required_secondary_mercenary_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `required_mercenary_level` int DEFAULT NULL,
  `required_secondary_mercenary_level` int DEFAULT NULL,
  `required_class_specials_equipped` int DEFAULT NULL,
  `desktop_instructions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `mobile_instructions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `required_class_rank_level` int DEFAULT NULL,
  `required_kingdom_building_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `required_kingdom_building_level` int DEFAULT NULL,
  `required_gold_bars` bigint DEFAULT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `unlock_at_level` int DEFAULT NULL,
  `only_during_event` int DEFAULT NULL,
  `be_on_game_map` bigint unsigned DEFAULT NULL,
  `required_event_goal_participation` bigint DEFAULT NULL,
  `required_holy_stacks` int DEFAULT NULL,
  `required_attached_gems` int DEFAULT NULL,
  `required_copper_coins` bigint DEFAULT NULL,
  `required_specialty_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `required_fame_level` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `holy_stacks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `holy_stacks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_id` bigint unsigned NOT NULL,
  `devouring_darkness_bonus` decimal(8,4) DEFAULT NULL,
  `stat_increase_bonus` decimal(8,4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `holy_stacks_item_id_foreign` (`item_id`),
  CONSTRAINT `holy_stacks_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `info_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `info_pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `page_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `page_sections` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventories_character_id_foreign` (`character_id`),
  CONSTRAINT `inventories_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventory_sets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_sets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `is_equipped` tinyint(1) NOT NULL DEFAULT '0',
  `can_be_equipped` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_sets_character_id_foreign` (`character_id`),
  CONSTRAINT `inventory_sets_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventory_slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_slots` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `inventory_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `equipped` tinyint(1) DEFAULT '0',
  `position` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_slots_inventory_id_foreign` (`inventory_id`),
  KEY `inventory_slots_item_id_foreign` (`item_id`),
  CONSTRAINT `inventory_slots_inventory_id_foreign` FOREIGN KEY (`inventory_id`) REFERENCES `inventories` (`id`),
  CONSTRAINT `inventory_slots_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `item_affixes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `item_affixes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `base_damage_mod` decimal(5,4) DEFAULT NULL,
  `base_healing_mod` decimal(5,4) DEFAULT NULL,
  `base_ac_mod` decimal(5,4) DEFAULT NULL,
  `str_mod` decimal(5,4) DEFAULT NULL,
  `dur_mod` decimal(5,4) DEFAULT NULL,
  `dex_mod` decimal(5,4) DEFAULT NULL,
  `chr_mod` decimal(5,4) DEFAULT NULL,
  `int_mod` decimal(5,4) DEFAULT NULL,
  `int_required` int DEFAULT '1',
  `skill_level_required` int DEFAULT NULL,
  `skill_level_trivial` int DEFAULT NULL,
  `skill_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `skill_bonus` decimal(5,4) DEFAULT NULL,
  `skill_training_bonus` decimal(5,4) DEFAULT NULL,
  `cost` bigint NOT NULL DEFAULT '0',
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `can_drop` tinyint(1) NOT NULL DEFAULT '1',
  `agi_mod` decimal(5,4) DEFAULT '0.0000',
  `focus_mod` decimal(5,4) DEFAULT '0.0000',
  `affects_skill_type` int DEFAULT NULL,
  `damage_can_stack` tinyint(1) NOT NULL DEFAULT '0',
  `irresistible_damage` tinyint(1) NOT NULL DEFAULT '0',
  `str_reduction` decimal(5,4) DEFAULT '0.0000',
  `dur_reduction` decimal(5,4) DEFAULT '0.0000',
  `dex_reduction` decimal(5,4) DEFAULT '0.0000',
  `chr_reduction` decimal(5,4) DEFAULT '0.0000',
  `int_reduction` decimal(5,4) DEFAULT '0.0000',
  `agi_reduction` decimal(5,4) DEFAULT '0.0000',
  `focus_reduction` decimal(5,4) DEFAULT '0.0000',
  `steal_life_amount` decimal(5,4) DEFAULT NULL,
  `entranced_chance` decimal(5,4) DEFAULT '0.0000',
  `reduces_enemy_stats` tinyint(1) NOT NULL DEFAULT '0',
  `devouring_light` decimal(8,4) DEFAULT '0.0000',
  `skill_reduction` decimal(8,4) DEFAULT '0.0000',
  `resistance_reduction` decimal(8,4) DEFAULT '0.0000',
  `randomly_generated` tinyint(1) NOT NULL DEFAULT '0',
  `affix_type` int NOT NULL,
  `damage_amount` double(12,8) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `item_skill_progressions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `item_skill_progressions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_id` bigint unsigned NOT NULL,
  `item_skill_id` bigint unsigned NOT NULL,
  `current_level` int NOT NULL,
  `current_kill` int NOT NULL,
  `is_training` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_skill_progressions_item_id_foreign` (`item_id`),
  KEY `item_skill_progressions_item_skill_id_foreign` (`item_skill_id`),
  CONSTRAINT `item_skill_progressions_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  CONSTRAINT `item_skill_progressions_item_skill_id_foreign` FOREIGN KEY (`item_skill_id`) REFERENCES `item_skills` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `item_skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `item_skills` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `str_mod` decimal(12,8) DEFAULT '0.00000000',
  `dex_mod` decimal(12,8) DEFAULT '0.00000000',
  `dur_mod` decimal(12,8) DEFAULT '0.00000000',
  `chr_mod` decimal(12,8) DEFAULT '0.00000000',
  `focus_mod` decimal(12,8) DEFAULT '0.00000000',
  `int_mod` decimal(12,8) DEFAULT '0.00000000',
  `agi_mod` decimal(12,8) DEFAULT '0.00000000',
  `base_damage_mod` decimal(12,8) DEFAULT '0.00000000',
  `base_ac_mod` decimal(12,8) DEFAULT '0.00000000',
  `base_healing_mod` decimal(12,8) DEFAULT '0.00000000',
  `max_level` int NOT NULL,
  `total_kills_needed` int NOT NULL,
  `parent_level_needed` int DEFAULT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `item_sockets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `item_sockets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_id` bigint unsigned NOT NULL,
  `gem_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_sockets_item_id_foreign` (`item_id`),
  KEY `item_sockets_gem_id_foreign` (`gem_id`),
  CONSTRAINT `item_sockets_gem_id_foreign` FOREIGN KEY (`gem_id`) REFERENCES `gems` (`id`),
  CONSTRAINT `item_sockets_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_suffix_id` bigint unsigned DEFAULT NULL,
  `item_prefix_id` bigint unsigned DEFAULT NULL,
  `market_sellable` tinyint(1) DEFAULT '0',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `default_position` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `base_damage` int DEFAULT NULL,
  `base_healing` int DEFAULT NULL,
  `base_ac` int DEFAULT NULL,
  `cost` bigint DEFAULT NULL,
  `base_damage_mod` decimal(5,4) DEFAULT NULL,
  `base_healing_mod` decimal(5,4) DEFAULT NULL,
  `base_ac_mod` decimal(5,4) DEFAULT NULL,
  `str_mod` decimal(5,4) DEFAULT NULL,
  `dur_mod` decimal(5,4) DEFAULT NULL,
  `dex_mod` decimal(5,4) DEFAULT NULL,
  `chr_mod` decimal(5,4) DEFAULT NULL,
  `int_mod` decimal(5,4) DEFAULT NULL,
  `effect` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `can_craft` tinyint(1) DEFAULT '0',
  `skill_level_required` int DEFAULT NULL,
  `skill_level_trivial` int DEFAULT NULL,
  `crafting_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `skill_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `skill_bonus` decimal(5,4) DEFAULT NULL,
  `skill_training_bonus` decimal(5,4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `can_drop` tinyint(1) NOT NULL DEFAULT '1',
  `craft_only` tinyint(1) NOT NULL DEFAULT '0',
  `gold_dust_cost` bigint DEFAULT NULL,
  `shards_cost` bigint DEFAULT NULL,
  `usable` tinyint(1) NOT NULL DEFAULT '0',
  `damages_kingdoms` tinyint(1) NOT NULL DEFAULT '0',
  `kingdom_damage` decimal(8,4) DEFAULT '0.0000',
  `lasts_for` int DEFAULT NULL,
  `stat_increase` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `increase_stat_by` decimal(8,4) DEFAULT '0.0000',
  `affects_skill_type` int DEFAULT NULL,
  `increase_skill_bonus_by` decimal(8,4) DEFAULT '0.0000',
  `increase_skill_training_bonus_by` decimal(8,4) DEFAULT '0.0000',
  `base_damage_mod_bonus` decimal(8,4) DEFAULT '0.0000',
  `base_healing_mod_bonus` decimal(8,4) DEFAULT '0.0000',
  `base_ac_mod_bonus` decimal(8,4) DEFAULT '0.0000',
  `fight_time_out_mod_bonus` decimal(8,4) DEFAULT '0.0000',
  `move_time_out_mod_bonus` decimal(8,4) DEFAULT '0.0000',
  `spell_evasion` decimal(8,4) DEFAULT '0.0000',
  `artifact_annulment` decimal(8,4) DEFAULT '0.0000',
  `agi_mod` decimal(5,4) DEFAULT '0.0000',
  `focus_mod` decimal(5,4) DEFAULT '0.0000',
  `can_resurrect` tinyint(1) NOT NULL DEFAULT '0',
  `resurrection_chance` decimal(10,6) DEFAULT '0.000000',
  `healing_reduction` decimal(5,4) DEFAULT '0.0000',
  `affix_damage_reduction` decimal(5,4) DEFAULT '0.0000',
  `devouring_light` decimal(8,4) DEFAULT '0.0000',
  `devouring_darkness` decimal(8,4) DEFAULT '0.0000',
  `parent_id` bigint DEFAULT NULL,
  `drop_location_id` bigint unsigned DEFAULT NULL,
  `xp_bonus` decimal(8,4) DEFAULT NULL,
  `ignores_caps` tinyint(1) NOT NULL DEFAULT '0',
  `can_use_on_other_items` tinyint(1) NOT NULL DEFAULT '0',
  `holy_level` int DEFAULT NULL,
  `holy_stacks` int DEFAULT '0',
  `ambush_chance` decimal(8,4) DEFAULT '0.0000',
  `ambush_resistance` decimal(8,4) DEFAULT '0.0000',
  `counter_chance` decimal(8,4) DEFAULT '0.0000',
  `counter_resistance` decimal(8,4) DEFAULT '0.0000',
  `is_mythic` tinyint(1) NOT NULL DEFAULT '0',
  `copper_coin_cost` int DEFAULT '0',
  `specialty_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gold_bars_cost` bigint DEFAULT NULL,
  `can_stack` tinyint(1) NOT NULL DEFAULT '0',
  `gains_additional_level` tinyint(1) NOT NULL DEFAULT '0',
  `unlocks_class_id` bigint unsigned DEFAULT NULL,
  `socket_count` int DEFAULT '0',
  `has_gems_socketed` tinyint(1) NOT NULL DEFAULT '0',
  `item_skill_id` bigint unsigned DEFAULT NULL,
  `alchemy_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `items_drop_location_id_foreign` (`drop_location_id`),
  KEY `items_item_skill_id_foreign` (`item_skill_id`),
  CONSTRAINT `items_drop_location_id_foreign` FOREIGN KEY (`drop_location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `items_item_skill_id_foreign` FOREIGN KEY (`item_skill_id`) REFERENCES `item_skills` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kingdom_building_expansions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kingdom_building_expansions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kingdom_building_id` bigint unsigned NOT NULL,
  `kingdom_id` bigint unsigned NOT NULL,
  `expansion_type` int NOT NULL,
  `expansion_count` int NOT NULL,
  `expansions_left` int NOT NULL,
  `minutes_until_next_expansion` int NOT NULL,
  `resource_costs` json NOT NULL,
  `gold_bars_cost` int NOT NULL,
  `resource_increases` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kingdom_buildings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kingdom_buildings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `game_building_id` bigint unsigned NOT NULL,
  `kingdom_id` bigint unsigned NOT NULL,
  `level` int NOT NULL,
  `max_defence` int NOT NULL,
  `max_durability` int NOT NULL,
  `current_defence` int NOT NULL,
  `current_durability` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `builds_gbid` (`game_building_id`),
  KEY `buildings_kid` (`kingdom_id`),
  CONSTRAINT `buildings_kid` FOREIGN KEY (`kingdom_id`) REFERENCES `kingdoms` (`id`),
  CONSTRAINT `builds_gbid` FOREIGN KEY (`game_building_id`) REFERENCES `game_buildings` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kingdom_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kingdom_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `from_kingdom_id` bigint unsigned DEFAULT NULL,
  `to_kingdom_id` bigint unsigned DEFAULT NULL,
  `status` int NOT NULL,
  `units_sent` json DEFAULT NULL,
  `units_survived` json DEFAULT NULL,
  `published` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `opened` tinyint(1) NOT NULL DEFAULT '0',
  `old_buildings` json NOT NULL,
  `new_buildings` json NOT NULL,
  `old_units` json NOT NULL,
  `new_units` json NOT NULL,
  `morale_loss` decimal(5,4) NOT NULL,
  `item_damage` decimal(12,4) DEFAULT NULL,
  `attacking_character_id` bigint DEFAULT NULL,
  `additional_details` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kl_character_id` (`character_id`),
  KEY `kl_from_king_id` (`from_kingdom_id`),
  KEY `kl_to_king_id` (`to_kingdom_id`),
  CONSTRAINT `kl_character_id` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`),
  CONSTRAINT `kl_from_king_id` FOREIGN KEY (`from_kingdom_id`) REFERENCES `kingdoms` (`id`),
  CONSTRAINT `kl_to_king_id` FOREIGN KEY (`to_kingdom_id`) REFERENCES `kingdoms` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kingdom_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kingdom_units` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kingdom_id` bigint unsigned NOT NULL,
  `game_unit_id` bigint unsigned NOT NULL,
  `amount` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ku_kingdom_id` (`kingdom_id`),
  KEY `ku_game_unit_id` (`game_unit_id`),
  CONSTRAINT `ku_game_unit_id` FOREIGN KEY (`game_unit_id`) REFERENCES `game_units` (`id`),
  CONSTRAINT `ku_kingdom_id` FOREIGN KEY (`kingdom_id`) REFERENCES `kingdoms` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kingdoms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kingdoms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned DEFAULT NULL,
  `game_map_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `max_stone` bigint NOT NULL,
  `max_wood` bigint NOT NULL,
  `max_clay` bigint NOT NULL,
  `max_iron` bigint NOT NULL,
  `current_stone` bigint NOT NULL,
  `current_wood` bigint NOT NULL,
  `current_clay` bigint NOT NULL,
  `current_iron` bigint NOT NULL,
  `current_population` int NOT NULL,
  `max_population` int NOT NULL,
  `x_position` int NOT NULL,
  `y_position` int NOT NULL,
  `current_morale` double NOT NULL,
  `max_morale` double NOT NULL,
  `treasury` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_walked` timestamp NULL DEFAULT NULL,
  `npc_owned` tinyint(1) NOT NULL DEFAULT '0',
  `gold_bars` int DEFAULT '0',
  `protected_until` date DEFAULT NULL,
  `max_steel` bigint DEFAULT '0',
  `current_steel` bigint DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `king_cid` (`character_id`),
  KEY `king_gmid` (`game_map_id`),
  CONSTRAINT `king_cid` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`),
  CONSTRAINT `king_gmid` FOREIGN KEY (`game_map_id`) REFERENCES `game_maps` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `quest_reward_item_id` bigint unsigned DEFAULT NULL,
  `x` int NOT NULL,
  `y` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_port` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `game_map_id` bigint unsigned DEFAULT NULL,
  `enemy_strength_type` int DEFAULT NULL,
  `required_quest_item_id` bigint unsigned DEFAULT NULL,
  `type` int DEFAULT NULL,
  `can_players_enter` tinyint(1) NOT NULL,
  `can_auto_battle` tinyint(1) NOT NULL,
  `raid_id` bigint DEFAULT NULL,
  `has_raid_boss` tinyint(1) NOT NULL DEFAULT '0',
  `is_corrupted` tinyint(1) NOT NULL DEFAULT '0',
  `pin_css_class` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `locations_game_map_id_foreign` (`game_map_id`),
  KEY `locations_required_quest_item_id_foreign` (`required_quest_item_id`),
  CONSTRAINT `locations_game_map_id_foreign` FOREIGN KEY (`game_map_id`) REFERENCES `game_maps` (`id`),
  CONSTRAINT `locations_required_quest_item_id_foreign` FOREIGN KEY (`required_quest_item_id`) REFERENCES `items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `position_x` int DEFAULT '0',
  `position_y` int DEFAULT '0',
  `character_position_x` int DEFAULT '32',
  `character_position_y` int DEFAULT '32',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `game_map_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `maps_character_id_foreign` (`character_id`),
  KEY `maps_game_map_id_foreign` (`game_map_id`),
  CONSTRAINT `maps_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`),
  CONSTRAINT `maps_game_map_id_foreign` FOREIGN KEY (`game_map_id`) REFERENCES `game_maps` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `market_board`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `market_board` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `listed_price` bigint NOT NULL,
  `is_locked` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mb_character` (`character_id`),
  KEY `market_board_item_id_foreign` (`item_id`),
  CONSTRAINT `market_board_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  CONSTRAINT `mb_character` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `market_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `market_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_id` bigint unsigned NOT NULL,
  `sold_for` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mh_item` (`item_id`),
  CONSTRAINT `mh_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `max_level_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `max_level_configurations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `max_level` bigint NOT NULL,
  `half_way` bigint NOT NULL,
  `three_quarters` bigint NOT NULL,
  `last_leg` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `from_user` bigint unsigned DEFAULT NULL,
  `to_user` bigint unsigned DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `x_position` int DEFAULT '0',
  `y_position` int DEFAULT '0',
  `color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `hide_location` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `messages_user_id_foreign` (`user_id`),
  KEY `messages_from_user_foreign` (`from_user`),
  KEY `messages_to_user_foreign` (`to_user`),
  CONSTRAINT `messages_from_user_foreign` FOREIGN KEY (`from_user`) REFERENCES `users` (`id`),
  CONSTRAINT `messages_to_user_foreign` FOREIGN KEY (`to_user`) REFERENCES `users` (`id`),
  CONSTRAINT `messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `monsters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `monsters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `str` bigint NOT NULL,
  `dur` bigint NOT NULL,
  `dex` bigint NOT NULL,
  `chr` bigint NOT NULL,
  `int` bigint NOT NULL,
  `agi` bigint NOT NULL,
  `focus` bigint NOT NULL,
  `ac` bigint NOT NULL,
  `max_level` int DEFAULT '0',
  `damage_stat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `xp` bigint NOT NULL,
  `drop_check` decimal(5,4) NOT NULL,
  `gold` bigint DEFAULT '10',
  `health_range` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attack_range` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `quest_item_id` bigint unsigned DEFAULT NULL,
  `quest_item_drop_chance` decimal(5,4) DEFAULT NULL,
  `game_map_id` bigint unsigned DEFAULT NULL,
  `is_celestial_entity` tinyint(1) NOT NULL DEFAULT '0',
  `gold_cost` int DEFAULT NULL,
  `gold_dust_cost` int DEFAULT NULL,
  `can_cast` tinyint(1) NOT NULL DEFAULT '0',
  `max_spell_damage` bigint DEFAULT '0',
  `shards` int DEFAULT '0',
  `spell_evasion` decimal(8,4) DEFAULT '0.0000',
  `affix_resistance` decimal(8,4) DEFAULT '0.0000',
  `max_affix_damage` bigint DEFAULT '0',
  `healing_percentage` decimal(8,4) DEFAULT '0.0000',
  `entrancing_chance` decimal(8,4) DEFAULT '0.0000',
  `devouring_light_chance` decimal(8,4) DEFAULT '0.0000',
  `accuracy` decimal(8,4) DEFAULT '0.0000',
  `casting_accuracy` decimal(8,4) DEFAULT '0.0000',
  `dodge` decimal(8,4) DEFAULT '0.0000',
  `criticality` decimal(8,4) DEFAULT '0.0000',
  `devouring_darkness_chance` decimal(8,4) DEFAULT '0.0000',
  `ambush_chance` decimal(8,4) DEFAULT '0.0000',
  `ambush_resistance` decimal(8,4) DEFAULT '0.0000',
  `counter_chance` decimal(8,4) DEFAULT '0.0000',
  `counter_resistance` decimal(8,4) DEFAULT '0.0000',
  `celestial_type` int DEFAULT NULL,
  `is_raid_monster` tinyint(1) NOT NULL DEFAULT '0',
  `is_raid_boss` tinyint(1) NOT NULL DEFAULT '0',
  `raid_special_attack_type` int DEFAULT NULL,
  `fire_atonement` decimal(8,4) DEFAULT NULL,
  `ice_atonement` decimal(8,4) DEFAULT NULL,
  `water_atonement` decimal(8,4) DEFAULT NULL,
  `life_stealing_resistance` decimal(8,4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `monsters_quest_item_id_foreign` (`quest_item_id`),
  KEY `monsters_game_map_id_foreign` (`game_map_id`),
  CONSTRAINT `monsters_game_map_id_foreign` FOREIGN KEY (`game_map_id`) REFERENCES `game_maps` (`id`),
  CONSTRAINT `monsters_quest_item_id_foreign` FOREIGN KEY (`quest_item_id`) REFERENCES `items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `monthly_pvp_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `monthly_pvp_participants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `attack_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `m_pvp_p_ch` (`character_id`),
  CONSTRAINT `m_pvp_p_ch` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `npcs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `npcs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `game_map_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `real_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` int NOT NULL,
  `x_position` int NOT NULL,
  `y_position` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `npcs_name_unique` (`name`),
  KEY `npcs_game_map_id_foreign` (`game_map_id`),
  CONSTRAINT `npcs_game_map_id_foreign` FOREIGN KEY (`game_map_id`) REFERENCES `game_maps` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `passive_skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `passive_skills` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `max_level` int NOT NULL,
  `bonus_per_level` decimal(8,4) DEFAULT NULL,
  `effect_type` int NOT NULL,
  `parent_skill_id` bigint DEFAULT NULL,
  `unlocks_at_level` int DEFAULT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  `is_parent` tinyint(1) NOT NULL DEFAULT '0',
  `hours_per_level` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `resource_bonus_per_level` bigint DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `npc_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned DEFAULT NULL,
  `gold_dust_cost` bigint DEFAULT '0',
  `shard_cost` bigint DEFAULT '0',
  `gold_cost` bigint DEFAULT '0',
  `reward_item` bigint unsigned DEFAULT NULL,
  `reward_gold_dust` bigint DEFAULT '0',
  `reward_shards` bigint DEFAULT '0',
  `reward_gold` bigint DEFAULT '0',
  `reward_xp` bigint DEFAULT '0',
  `unlocks_skill` tinyint(1) NOT NULL DEFAULT '0',
  `unlocks_skill_type` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_parent` tinyint(1) NOT NULL DEFAULT '0',
  `parent_quest_id` bigint unsigned DEFAULT NULL,
  `secondary_required_item` bigint unsigned DEFAULT NULL,
  `faction_game_map_id` bigint unsigned DEFAULT NULL,
  `required_faction_level` int DEFAULT NULL,
  `access_to_map_id` bigint unsigned DEFAULT NULL,
  `copper_coin_cost` bigint DEFAULT '0',
  `before_completion_description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `after_completion_description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `unlocks_feature` int DEFAULT NULL,
  `unlocks_passive_id` bigint DEFAULT NULL,
  `raid_id` bigint unsigned DEFAULT NULL,
  `required_quest_id` bigint unsigned DEFAULT NULL,
  `reincarnated_times` int DEFAULT NULL,
  `only_for_event` int DEFAULT NULL,
  `assisting_npc_id` bigint unsigned DEFAULT NULL,
  `required_fame_level` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quests_npc_id_foreign` (`npc_id`),
  KEY `quests_item_id_foreign` (`item_id`),
  KEY `quests_reward_item_foreign` (`reward_item`),
  KEY `quests_secondary_required_item_foreign` (`secondary_required_item`),
  KEY `gmid_gm` (`faction_game_map_id`),
  KEY `quests_access_to_map_id_foreign` (`access_to_map_id`),
  KEY `quests_raid_id_foreign` (`raid_id`),
  CONSTRAINT `gmid_gm` FOREIGN KEY (`faction_game_map_id`) REFERENCES `game_maps` (`id`),
  CONSTRAINT `quests_access_to_map_id_foreign` FOREIGN KEY (`access_to_map_id`) REFERENCES `game_maps` (`id`),
  CONSTRAINT `quests_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  CONSTRAINT `quests_npc_id_foreign` FOREIGN KEY (`npc_id`) REFERENCES `npcs` (`id`),
  CONSTRAINT `quests_raid_id_foreign` FOREIGN KEY (`raid_id`) REFERENCES `raids` (`id`),
  CONSTRAINT `quests_reward_item_foreign` FOREIGN KEY (`reward_item`) REFERENCES `items` (`id`),
  CONSTRAINT `quests_secondary_required_item_foreign` FOREIGN KEY (`secondary_required_item`) REFERENCES `items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quests_completed`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quests_completed` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned DEFAULT NULL,
  `quest_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `guide_quest_id` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quests_completed_character_id_foreign` (`character_id`),
  KEY `quests_completed_quest_id_foreign` (`quest_id`),
  CONSTRAINT `quests_completed_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`),
  CONSTRAINT `quests_completed_quest_id_foreign` FOREIGN KEY (`quest_id`) REFERENCES `quests` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `raid_boss_participations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `raid_boss_participations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `raid_id` bigint unsigned NOT NULL,
  `attacks_left` int NOT NULL,
  `damage_dealt` bigint NOT NULL,
  `killed_boss` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `raid_boss_participations_character_id_foreign` (`character_id`),
  KEY `raid_boss_participations_raid_id_foreign` (`raid_id`),
  CONSTRAINT `raid_boss_participations_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`),
  CONSTRAINT `raid_boss_participations_raid_id_foreign` FOREIGN KEY (`raid_id`) REFERENCES `raids` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `raid_bosses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `raid_bosses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `raid_id` bigint unsigned NOT NULL,
  `raid_boss_id` bigint unsigned NOT NULL,
  `boss_max_hp` bigint DEFAULT NULL,
  `boss_current_hp` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `raid_boss_deatils` json DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `raid_participations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `raid_participations` (
  `id` bigint NOT NULL,
  `character_id` bigint unsigned NOT NULL,
  `raid_boss_id` bigint unsigned NOT NULL,
  `damage_dealt` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  KEY `raid_participations_character_id_foreign` (`character_id`),
  KEY `raid_participations_raid_boss_id_foreign` (`raid_boss_id`),
  CONSTRAINT `raid_participations_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`),
  CONSTRAINT `raid_participations_raid_boss_id_foreign` FOREIGN KEY (`raid_boss_id`) REFERENCES `raid_bosses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `raids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `raids` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `story` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `raid_boss_id` bigint unsigned NOT NULL,
  `raid_monster_ids` json NOT NULL,
  `raid_boss_location_id` bigint unsigned NOT NULL,
  `corrupted_location_ids` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `item_specialty_reward_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `artifact_item_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `raids_raid_boss_id_foreign` (`raid_boss_id`),
  KEY `raids_raid_boss_location_id_foreign` (`raid_boss_location_id`),
  KEY `raids_artifact_item_id_foreign` (`artifact_item_id`),
  CONSTRAINT `raids_artifact_item_id_foreign` FOREIGN KEY (`artifact_item_id`) REFERENCES `items` (`id`),
  CONSTRAINT `raids_raid_boss_id_foreign` FOREIGN KEY (`raid_boss_id`) REFERENCES `monsters` (`id`),
  CONSTRAINT `raids_raid_boss_location_id_foreign` FOREIGN KEY (`raid_boss_location_id`) REFERENCES `locations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rank_fight_tops`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rank_fight_tops` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `current_rank` int NOT NULL,
  `rank_achievement_date` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rankft_ch_id` (`character_id`),
  CONSTRAINT `rankft_ch_id` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rank_fights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rank_fights` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `current_rank` int NOT NULL DEFAULT '10',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `release_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `release_notes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `release_date` date NOT NULL,
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `scheduled_event_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scheduled_event_configurations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_type` int NOT NULL,
  `start_date` datetime NOT NULL,
  `generate_every` enum('weekly','monthly') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_time_generated` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `scheduled_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scheduled_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_type` int NOT NULL,
  `raid_id` bigint unsigned DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `currently_running` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `scheduled_events_raid_id_foreign` (`raid_id`),
  CONSTRAINT `scheduled_events_raid_id_foreign` FOREIGN KEY (`raid_id`) REFERENCES `raids` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `set_slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `set_slots` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_id` bigint unsigned NOT NULL,
  `inventory_set_id` bigint unsigned NOT NULL,
  `equipped` tinyint(1) NOT NULL DEFAULT '0',
  `position` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `set_slots_item_id_foreign` (`item_id`),
  KEY `set_id` (`inventory_set_id`),
  CONSTRAINT `set_id` FOREIGN KEY (`inventory_set_id`) REFERENCES `inventory_sets` (`id`),
  CONSTRAINT `set_slots_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `skills` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned DEFAULT NULL,
  `currently_training` tinyint(1) DEFAULT '0',
  `level` int NOT NULL,
  `xp` int DEFAULT '0',
  `xp_max` int DEFAULT NULL,
  `xp_towards` decimal(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `game_skill_id` bigint unsigned NOT NULL DEFAULT '0',
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  `skill_type` int DEFAULT NULL,
  `is_hidden` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `skills_character_id_foreign` (`character_id`),
  KEY `skills_game_skill_id_foreign` (`game_skill_id`),
  CONSTRAINT `skills_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`),
  CONSTRAINT `skills_game_skill_id_foreign` FOREIGN KEY (`game_skill_id`) REFERENCES `game_skills` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `smelting_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `smelting_progress` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint NOT NULL,
  `kingdom_id` bigint NOT NULL,
  `started_at` datetime NOT NULL,
  `completed_at` datetime NOT NULL,
  `amount_to_smelt` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `unit_movement_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `unit_movement_queue` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `from_kingdom_id` bigint unsigned NOT NULL,
  `to_kingdom_id` bigint unsigned NOT NULL,
  `units_moving` json NOT NULL,
  `completed_at` datetime NOT NULL,
  `started_at` datetime NOT NULL,
  `moving_to_x` int NOT NULL,
  `moving_to_y` int NOT NULL,
  `from_x` int NOT NULL,
  `from_y` int NOT NULL,
  `is_attacking` tinyint(1) DEFAULT '0',
  `is_recalled` tinyint(1) DEFAULT '0',
  `is_returning` tinyint(1) DEFAULT '0',
  `is_moving` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uimq_cid` (`character_id`),
  KEY `uimq_from_king_id` (`from_kingdom_id`),
  KEY `uimq_to_king_id` (`to_kingdom_id`),
  CONSTRAINT `uimq_cid` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`),
  CONSTRAINT `uimq_from_king_id` FOREIGN KEY (`from_kingdom_id`) REFERENCES `kingdoms` (`id`),
  CONSTRAINT `uimq_to_king_id` FOREIGN KEY (`to_kingdom_id`) REFERENCES `kingdoms` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `units_in_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `units_in_queue` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `kingdom_id` bigint unsigned NOT NULL,
  `game_unit_id` bigint unsigned NOT NULL,
  `amount` int NOT NULL,
  `completed_at` datetime NOT NULL,
  `started_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `gold_paid` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uiq_cid` (`character_id`),
  KEY `uiq_king_id` (`kingdom_id`),
  KEY `uiq_game_unit_id` (`game_unit_id`),
  CONSTRAINT `uiq_cid` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`),
  CONSTRAINT `uiq_game_unit_id` FOREIGN KEY (`game_unit_id`) REFERENCES `game_units` (`id`),
  CONSTRAINT `uiq_king_id` FOREIGN KEY (`kingdom_id`) REFERENCES `kingdoms` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_site_access_statistics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_site_access_statistics` (
  `amount_signed_in` int DEFAULT '0',
  `amount_registered` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `invalid_ips` json DEFAULT NULL,
  `invalid_user_ids` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_throttle_count` int DEFAULT '0',
  `can_speak_again_at` datetime DEFAULT NULL,
  `is_silenced` tinyint(1) NOT NULL DEFAULT '0',
  `ip_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '0.0.0.0',
  `is_banned` tinyint(1) NOT NULL DEFAULT '0',
  `unbanned_at` datetime DEFAULT NULL,
  `timeout_until` datetime DEFAULT NULL,
  `banned_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `un_ban_request` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `upgraded_building_email` tinyint(1) NOT NULL DEFAULT '0',
  `rebuilt_building_email` tinyint(1) NOT NULL DEFAULT '0',
  `kingdom_attack_email` tinyint(1) NOT NULL DEFAULT '0',
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `show_unit_recruitment_messages` tinyint(1) NOT NULL DEFAULT '0',
  `show_building_upgrade_messages` tinyint(1) NOT NULL DEFAULT '0',
  `show_building_rebuilt_messages` tinyint(1) NOT NULL DEFAULT '0',
  `show_kingdom_update_messages` tinyint(1) NOT NULL DEFAULT '0',
  `auto_disenchant` tinyint(1) NOT NULL DEFAULT '0',
  `auto_disenchant_amount` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_logged_in` datetime DEFAULT NULL,
  `will_be_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `ignored_unban_request` tinyint(1) NOT NULL DEFAULT '0',
  `guide_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `chat_text_color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chat_is_bold` tinyint(1) NOT NULL DEFAULT '0',
  `chat_is_italic` tinyint(1) NOT NULL DEFAULT '0',
  `name_tag` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `websockets_statistics_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `websockets_statistics_entries` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `app_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `peak_connection_count` int NOT NULL,
  `websocket_message_count` int NOT NULL,
  `api_message_count` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2014_10_12_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2014_10_12_100000_create_password_resets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2019_08_19_000000_create_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2019_11_22_173623_create_game_races',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2019_11_22_173640_create_game_classes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2019_11_22_173651_create_characters',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2019_11_23_021655_create_permission_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2019_11_23_184641_create_websockets_statistics_entries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2019_11_23_190544_create_messages',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2019_11_24_201001_create_monsters',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2019_11_24_222740_create_skills',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2019_11_25_235345_create_items',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2019_11_26_001941_create_inventories',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2019_11_26_223837_create_inventory_slots',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2019_11_29_220548_create_maps',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2019_11_30_231615_create_locations',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2019_12_04_223756_create_item_affixes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2020_05_12_205154_create_game_maps',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2020_05_12_222332_add_game_map_id_to_maps',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2020_06_09_181647_add_game_map_id_to_locations',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2020_06_10_200948_create_adventures',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2020_06_10_202128_create_adventure_logs',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2020_06_11_173617_create_adventure_location',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2020_06_11_183143_create_adventure_monster',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2020_09_14_211537_create_notifications',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2020_09_20_013710_add_quest_information_to_monsters',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2020_09_28_011204_create_sessions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2020_10_06_164008_create_game_skills',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2020_10_06_182953_add_game_skill_id_to_skills',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2020_11_11_181548_create_security_questions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2020_11_26_201357_create_market_board',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2020_11_26_202031_create_market_history',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2020_12_18_024531_create_release_notes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2020_12_21_233115_create_character_snap_shots',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2020_12_28_190720_create_kingdoms',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2020_12_28_214357_create_game_buildings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2020_12_31_233318_add_game_map_id_to_monsters',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2021_01_12_230649_create_game_units',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2021_01_12_231050_create_game_building_units',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2021_01_12_232009_create_kingdom_units',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2021_01_18_120104_create_units_in_queue',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2021_03_10_103612_create_unit_movement_queue',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2021_03_16_173026_create_kingdom_buildings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2021_03_17_100859_create_buildings_in_queue',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2021_03_27_153843_create_audits_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2021_04_08_200645_create_kingdom_logs',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2021_06_04_110845_create_user_site_access_statistics',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2021_06_08_200758_change_cost_on_items_to_allow_biger_integers',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2021_06_09_201729_change_item_affixes_cost',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2021_06_10_112930_update_market_board_listed_price',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2021_06_15_215220_add_can_drop_to_items',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2021_06_15_215526_add_can_drop_to_item_affixes',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2021_06_18_122128_add_last_walked_to_kingdoms',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2021_06_18_175714_add_chat_settings_to_users',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2021_06_22_122810_update_skill_description_length',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2021_06_10_203954_let_characters_be_npcs',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2021_06_10_205145_create_npcs',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2021_06_10_210918_create_npc_commands',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2021_06_11_212651_all_character_id_to_be_null_on_kingdoms',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2021_06_21_202724_add_celestial_info_to_monsters',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2021_06_21_203555_add_celestial_fights',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2021_06_23_162829_add_new_currencies_to_characters',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2021_06_23_210800_add_skill_type_to_game_skills',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2021_06_24_192953_update_monsters',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2021_06_26_191759_create_character_in_celestial_fights',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2021_06_30_131820_add_alchemy_costs_to_items',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2021_06_30_235807_add_is_locked_to_game_skills',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2021_06_30_235836_create_character_boons',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2021_06_30_235853_create_quests',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2021_07_01_103318_add_is_locked_to_skills',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2021_07_01_150606_create_quests_completed',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2021_07_20_213308_create_inventory_sets',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2021_07_20_213342_create_set_slots',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2021_07_27_180228_update_game_map_with_bonuses',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2021_07_28_191307_add_class_id_to_game_skills',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2021_08_03_114147_add_to_hit_stat_to_game_classes',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2021_08_03_120022_add_new_stats_to_characters',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2021_08_03_120319_add_new_stat_mods_to_races_and_classes',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2021_08_03_191405_add_new_stats_to_items_and_item_affixes',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2021_08_05_223150_change_percision_on_skill_bonuses',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2021_08_09_185508_add_resurection_chance_to_items',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2021_08_11_145040_add_new_skill_modifiers_to_item_affixes',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2021_08_20_123750_remove_security_questions',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2021_08_24_182605_update_status_for_kingdom_logs',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2021_08_29_181928_drop_character_snap_shots',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2021_09_03_063300_add_new_stats_to_monsters',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2021_09_14_181203_change_items_agi_and_focus_to_default',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2021_09_14_182130_change_affixes_agi_and_focus_to_default',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2021_09_20_183725_add_damage_to_item_affixes',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2021_09_21_153809_change_description_for_item_affixes',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2021_09_21_164348_add_affix_resistance_to_monsters',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2021_09_22_153115_add_class_bonus_to_item_affixes',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2021_09_22_182705_add_stat_ailments_to_item_affixes',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2021_10_04_231512_add_more_stats_to_monsters',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2021_10_05_213217_add_healing_deduction_and_affix_damage_reduction_to_items',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2021_10_11_183326_create_adventure_floor_descriptions',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2021_10_12_225239_add_skill_precentages_to_monsters',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2021_10_12_230623_remove_monster_skills_from_skills',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2021_10_13_195856_add_devouring_light_and_devouring_darkness_to_items',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2021_10_13_201322_increase_description_length_for_items',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2021_10_15_175828_add_max_level_configurations',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2021_10_20_200843_add_devouring_light_to_item_affixes',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2021_10_30_015030_remove_kingdom_update_setting_from_users',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2021_11_03_215903_add_auto_disenchant_to_users',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2021_11_07_012112_add_skill_reduction_to_item_affixes',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2021_11_07_032536_add_resistance_reduction_to_item_affixes',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2021_11_07_193909_add_last_logged_in_to_users',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2021_11_07_202923_add_disable_attack_pop_overs_to_users',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2021_11_10_204151_change_maxvalue_of_health_in_character_in_celestial_fights',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2021_11_11_050908_change_current_health_column_type_on_celestial_fights',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2021_12_12_041115_add_current_adventure_id_to_characters',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2021_12_14_224600_add_parent_id_to_items',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2021_12_20_184108_add_stat_attributes_to_character_boons',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2021_11_04_041106_add_gold_to_units_in_queue',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2021_11_09_203846_add_enemy_strength_to_locations',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2021_11_15_194346_add_paid_with_gold_to_buildings_in_queue',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2021_11_17_004841_add_kingdom_reduction_to_game_skills',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2021_11_18_013118_create_character_automations',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2021_11_18_013902_add_is_attack_automation_locked_to_characters',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2021_11_20_180548_add_opened_flag_to_kingdom_logs',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2021_11_21_002528_add_is_mass_embezzling_to_characters',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2021_11_21_230039_create_factions',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2021_11_22_202619_add_randomly_generated_to_item_affixes',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2021_11_23_205244_add_location_id_to_adventures',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2021_11_26_001434_add_flag_to_delete_to_users',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2021_11_26_025639_create_passive_skills',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2021_11_26_213514_character_passive_skills',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2021_12_02_030336_add_passive_skill_lock_details_to_game_buildings',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2021_12_05_000723_add_is_locked_to_kingdom_buildings',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2021_12_05_203721_remove_new_building_email_setting_from_users',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2021_12_06_185609_add_gold_bars_to_kingdoms',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2021_12_18_031735_add_character_attack_reduction_to_game_maps',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2021_12_18_064044_add_parent_details_to_quests',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2021_12_22_045604_add_drops_from_spcial_location_to_items',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2021_12_22_051945_add_requires_access_to_plane_to_quests',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2021_12_24_004522_update_stats_for_monsters',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2021_12_31_010528_add_name_to_inventory_sets',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2022_01_01_010621_add_location_id_to_game_maps',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2022_01_08_043251_add_over_flow_set_id_to_adventure_logs',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (140,'2022_01_12_233248_add_kingdom_lock_out_to_characters',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (141,'2022_01_13_022046_add_modifiers_to_character_boons',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (142,'2022_01_31_200645_add_can_auto_battle_to_users',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (143,'2022_02_10_150301_add_experience_buff_to_items',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2022_02_10_192743_add_copper_coins_to_characters',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (145,'2022_02_11_145421_add_copper_coins_to_quests',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (146,'2022_02_14_200025_remove_attack_autom_ation_flag_from_users',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (147,'2022_02_14_220238_add_can_use_on_other_items_to_items',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (148,'2022_02_15_091842_add_requires_quest_item_to_locations',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (149,'2022_02_15_112039_add_type_to_locations',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (150,'2022_02_15_122007_create_holy_stacks',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (151,'2022_02_15_123223_add_holy_information_to_items',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (152,'2022_02_18_130225_add_devouring_resistance_and_darkness_to_monsters',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (153,'2022_02_25_111903_change_sold_for_in_market_history',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (154,'2022_02_28_185545_remove_disable_attack_typ_popover_from_users',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (155,'2022_03_01_212500_add_ips_to_user_site_access_statistics',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (156,'2022_03_25_205939_add_additional_info_to_items',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (157,'2022_03_28_114204_add_ambush_and_counter_chance_and_resistance_to_monsters',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (158,'2022_04_23_101624_change_character_boons',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (159,'2022_05_05_151046_add_ignored_un_ban_request_to_users',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (160,'2022_05_07_193816_add_before_and_after_acceptance_descriptions_to_quests',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (161,'2022_05_11_091709_drop_adventures_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (162,'2022_05_12_200219_drop_columns_from_monsters',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (163,'2022_05_14_104039_create_info_pages',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (164,'2022_05_18_171857_drop_notifications_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (165,'2022_05_18_181930_create_guide_quests',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (166,'2022_05_18_182812_add_guide_quest_id_to_quests_completed',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (167,'2022_05_19_134949_add_guide_enabled_to_users',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (168,'2022_05_19_185726_add_reward_level_to_guide_quests',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (169,'2022_05_22_102216_drop_adventure_email_from_users',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (170,'2022_05_29_103505_add_killed_in_pvp_to_characters',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (171,'2022_05_31_103016_add_is_mythic_to_items',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (172,'2022_06_13_144016_add_is_location_locked_from_player_to_locations',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (173,'2022_06_13_150652_create_events_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (174,'2022_06_13_200001_monthly_pvp_participants',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (175,'2022_06_14_150342_add_attack_type_to_monthly_pvp_participants',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (176,'2022_06_15_145808_set_monster_id_to_null_on_character_automations',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (177,'2022_06_16_124409_add_celestial_type_to_monsters',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (178,'2022_06_18_132246_add_additional_rewards_to_guide_quests',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (179,'2022_06_23_184907_add_required_kingdom_level_to_guide_quests',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (180,'2022_07_06_160711_add_passive_skill_requirements_to_guide_quests',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (181,'2022_07_09_113427_change_story_fields_for_quests',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (182,'2022_07_12_102109_add_protection_until_to_kingdoms',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (183,'2022_08_06_131338_drop_npc_commands_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (184,'2022_08_06_131550_clean_up_characters',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (185,'2022_08_15_180839_add_copper_coin_cost_to_items',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (186,'2022_09_01_075321_add_skill_type_to_skills',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (187,'2022_09_03_103252_change_kingdoms_logs',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (188,'2022_09_04_090311_add_attacking_character_id_to_kingdom_logs',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (189,'2022_09_04_094314_make_changes_to_kingdom_logs',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (190,'2022_09_04_095037_change_status_on_kingdom_logs',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (191,'2022_09_05_114115_add_specialty_type_to_items',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (192,'2022_09_05_115411_change_specialty_type_on_items',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (193,'2022_09_24_162553_add_faction_increase_to_guide_quests',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (194,'2022_09_26_162529_add_required_shards_to_guide_quests',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (195,'2022_10_10_094444_change_x_p_on_characters',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (196,'2022_10_18_135340_add_is_locationhidden_to_messages',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (197,'2022_11_05_205813_add_spin_info_to_character_sheet',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (198,'2022_11_07_143308_add_is_mercenary_unlocked_to_characters',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (199,'2022_11_07_150312_add_unlocks_feature_to_quests',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (200,'2022_11_08_082906_create_character_mercenaries',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (201,'2022_11_10_081755_add_xp_increase_and_reincarnated_times_to_mercenaries',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (202,'2022_11_12_161617_add_can_engage_celestial_to_characters',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (203,'2022_11_14_133054_change_the_item_damage_onkingdom_logs',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (204,'2022_11_19_105822_add_unlocks_passive_id_to_quests',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (205,'2022_11_19_112310_removetext_command_to_message_from_npcs',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (206,'2022_11_21_203021_add_steel_to_kingdoms',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (207,'2022_11_22_101426_create_table_smelting_progress',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (208,'2022_11_22_142006_add_steel_cost_to_game_buildings',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (209,'2022_11_22_142401_add_steel_cost_to_game_units',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (210,'2022_11_24_133158_add_is_special_to_game_buildings',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (211,'2022_11_24_133209_add_is_special_to_game_units',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (212,'2022_11_24_163055_add_xp_buff_to_mercenaries',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (213,'2022_11_24_204858_add_xp_penalty_to_characters',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (214,'2022_11_24_225727_add_reincarnated_stat_increase_and_times_reincarnated_to_characters',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (215,'2022_11_30_144137_create_class_ranks',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (216,'2022_12_01_084448_create_class_ranks_weapon_masteries',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (217,'2022_12_02_112412_create_class_specials',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (218,'2022_12_02_172923_create_character_class_specialties_equipped',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (219,'2022_12_14_183105_add_is_hidden_to_skills',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (220,'2022_12_14_194155_add_required_attack_type_to_game_class_specials',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (221,'2022_12_20_212448_add_stat_pools_to_characters',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (222,'2022_12_27_125831_rank_fights',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (223,'2022_12_27_132349_add_rank_fight_tops',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (224,'2023_01_06_121304_add_gold_bars_cost_to_items',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (225,'2023_01_17_181017_add_class_rank_requirements_to_game_classes',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (226,'2023_01_17_182505_add_unlocks_class_id_to_items',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (227,'2023_01_20_104203_add_reductions_to_class_specials',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (228,'2023_03_14_093113_create_gems',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (229,'2023_03_14_094116_create_gem_bags',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (230,'2023_03_14_100511_create_item_sockets',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (231,'2023_03_14_214841_create_gem_bag_slots',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (232,'2023_03_18_121507_add_socket_amount_to_items',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (233,'2023_04_18_204824_create_announcements',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (234,'2023_04_18_204938_add_raid_id_to_events',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (235,'2023_04_18_205143_add_raid_info_to_locations',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (236,'2023_04_18_205614_add_raid_info_to_monsters',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (237,'2023_04_18_210255_create_raids',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (238,'2023_04_26_140838_create_scheduled_events',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (239,'2023_05_02_122834_update_monster_gold_to_big_integer',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (240,'2023_05_02_142223_add_raid_id_to_quests',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (241,'2023_05_04_132726_create_raid_participations',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (242,'2023_05_04_132750_create_raid_bosses',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (243,'2023_05_04_133259_add_raid_boss_id_to_raid_participations',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (244,'2023_05_08_093817_change_health_to_nullable_on_raid_bosses',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (245,'2023_05_09_104028_change_raid_id_on_events',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (246,'2023_05_10_110644_add_currently_running_to_scheduled_events',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (247,'2023_05_10_192257_change_event_type_on_scheduled_events',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (248,'2023_05_30_150733_add_raid_boss_details_to_raid_bosses',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (249,'2023_05_31_135357_create_raid_boss_participations',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (250,'2023_06_02_190403_add_recination_times_and_required_quest_id_to_quests',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (251,'2023_06_05_190646_add_elemental_information_to_monsters',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (252,'2023_06_14_105325_add_life_stealing_resistance_to_monsters',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (253,'2023_06_15_202412_add_item_specialty_type_to_raids',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (254,'2023_06_16_114248_create_item_skills',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (255,'2023_06_16_150509_add_item_skill_id_to_items',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (256,'2023_06_16_193432_create_item_skill_progressions',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (257,'2023_06_21_110406_add_event_id_to_announcements',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (258,'2023_06_22_135441_add_artifact_item_id_to_raids',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (259,'2023_07_03_121434_remove_fields_from_item_affixes',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (260,'2023_07_05_160947_remove_can_monster_have_skill_from_skills',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (261,'2023_07_06_160047_add_affix_type_to_item_affixes',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (262,'2023_07_16_213124_add_more_to_guide_quests',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (263,'2023_07_17_132915_add_stat_checking_to_guide_quests',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (264,'2023_07_25_150928_add_secondary_skill_to_guide_quests',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (265,'2023_07_31_112229_add_secondary_required_quest_item_to_guide_quests',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (266,'2023_07_31_133151_add_skill_type_requirements_to_guide_quests',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (267,'2023_07_31_152746_add_mercenary_requirements_to_guide_quests',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (268,'2023_08_08_095122_add_class_specials_equipped_to_guide_quests',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (269,'2023_08_09_185724_add_chat_options_to_users',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (270,'2023_09_11_193230_create_scheduled_event_configurations',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (271,'2023_09_11_213532_make_last_time_generated_nullable_on_scheduled_event_configurations',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (272,'2023_09_15_220812_add_desktop_and_mobile_instructions_to_guide_quests',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (273,'2023_09_30_125526_add_required_class_rank_level_to_guide_quests',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (274,'2023_10_23_213614_add_alchemy_type_to_items',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (275,'2023_10_29_155657_add_kingdom_requirements_to_guide_quests',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (276,'2023_10_29_165251_increase_size_of_rewards_for_guide_quests',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (277,'2023_11_01_230344_add_only_during_event_to_game_maps',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (278,'2023_11_05_093842_global_event_goals',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (279,'2023_11_05_094509_create_global_event_participation',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (280,'2023_11_06_183842_update_announcements_foriegn_key_relation_ship',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (281,'2023_11_07_215603_add_only_for_event_type_to_quests',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (282,'2023_11_10_174153_add_special_location_pin_class_to_locations',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (283,'2023_11_12_140914_add_name_tag_to_users',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (284,'2023_11_17_144744_add_unlocks_at_level_and_parent_id_to_guide_quests',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (285,'2023_11_17_155452_add_additional_requirements_to_guide_quests',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (286,'2023_11_17_194508_create_new_table_event_goal_participation_kills',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (287,'2023_11_19_204133_add_additional_details_to_kingdom_logs',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (288,'2023_12_02_223528_add_invalid_user_ids_to_user_site_access_statistics',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (289,'2023_12_27_003213_create_faction_loyalties',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (290,'2023_12_27_003402_create_faction_loyalty_npcs',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (291,'2023_12_27_003755_create_faction_loyalty_npc_tasks',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (292,'2023_12_27_225247_add_is_pledged_to_faction_loyalties',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (293,'2024_01_04_213628_add_affix_damage_amount_to_item_affixes',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (294,'2024_01_04_224949_drop_damage_from_item_affixes',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (295,'2024_01_06_195824_add_more_requirements_to_guide_quests',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (296,'2024_01_07_150931_add_required_fame_level_to_guide_quests',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (297,'2024_01_07_172037_add_fame_loyalty_requirements_to_quests',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (298,'2024_01_25_201114_add_resource_bonus_per_level_to_passive_skills',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (299,'2024_01_25_202211_allow_bonus_per_level_to_be_null_on_passive_skills',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (300,'2024_01_30_200342_create_kingdom_building_expansions',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (301,'2024_01_30_200758_create_building_expansion_queues',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (302,'2024_02_04_161850_change_game_building_id_to_kingdom_building_id_on_kingdom_building_expansions',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (303,'2024_02_10_120636_add_type_to_buildings_in_queue',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (304,'2024_02_18_091336_add_additional_info_to_character_boons',29);
