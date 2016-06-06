<?php
/*
 *
 -------------------------------------------------------------------------
 Plugin GLPI News
 Copyright (C) 2015 by teclib.
 http://www.teclib.com
 -------------------------------------------------------------------------
 LICENSE
 This file is part of Plugin GLPI News.
 Plugin GLPI News is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.
 Plugin GLPI News is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 You should have received a copy of the GNU General Public License
 along with Plugin GLPI News. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
*/

function plugin_news_install() {
   global $DB;

   $plugin     = new Plugin();
   $found      = $plugin->find("name = 'news'");
   $pluginNews = array_shift($found);
   $migration  = new Migration($pluginNews['version']);

   if (! TableExists('glpi_plugin_news_alerts')) {
      $DB->query("
         CREATE TABLE IF NOT EXISTS `glpi_plugin_news_alerts` (
         `id`                   INT NOT NULL AUTO_INCREMENT,
         `date_mod`             DATETIME NOT NULL,
         `name`                 VARCHAR(255) NOT NULL,
         `message`              TEXT NOT NULL,
         `date_start`           DATETIME DEFAULT NULL,
         `date_end`             DATETIME DEFAULT NULL,
         `type`                 INT NOT NULL,
         `is_deleted`           TINYINT(1) NOT NULL DEFAULT 0,
         `is_displayed_onlogin` TINYINT(1) NOT NULL,
         `entities_id`          INT NOT NULL,
         `is_recursive`         TINYINT(1) NOT NULL DEFAULT 1,
         PRIMARY KEY (`id`)
         ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
      ");
   }

   if (! TableExists('glpi_plugin_news_alerts_users')) {
      $DB->query("
         CREATE TABLE IF NOT EXISTS `glpi_plugin_news_alerts_users` (
         `id`                    INT NOT NULL AUTO_INCREMENT,
         `plugin_news_alerts_id` INT NOT NULL,
         `users_id`              INT NOT NULL,
         `state`                 TINYINT(1) NOT NULL,
         PRIMARY KEY (`id`),
         UNIQUE KEY `state_for_user`
            (`plugin_news_alerts_id`,`users_id`,`state`)
         ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
      ");
   }

   if (! TableExists('glpi_plugin_news_alerts_targets')) {
      $DB->query("
         CREATE TABLE IF NOT EXISTS `glpi_plugin_news_alerts_targets` (
         `id`                    INT NOT NULL AUTO_INCREMENT,
         `plugin_news_alerts_id` INT NOT NULL,
         `itemtype`              VARCHAR(255) NOT NULL,
         `items_id`              INT NOT NULL,
         PRIMARY KEY (`id`),
         UNIQUE KEY `alert_itemtype_items_id`
            (`plugin_news_alerts_id`, `itemtype`,`items_id`)
         ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
      ");
   }

   /* Remove old table */
   if (TableExists('glpi_plugin_news_profiles')) {
      $DB->query("DROP TABLE IF EXISTS `glpi_plugin_news_profiles`;");
   }

   // add displayed on login flag
   if (!FieldExists("glpi_plugin_news_alerts", "is_displayed_onlogin")) {
      $migration->addField("glpi_plugin_news_alerts", "is_displayed_onlogin", 'bool');
   }

   // add displayed on login flag
   if (!FieldExists("glpi_plugin_news_alerts", "type")) {
      $migration->addField("glpi_plugin_news_alerts", "type", 'integer');
   }

   // end/start dates can be null
   $migration->changeField("glpi_plugin_news_alerts",
                           "date_end", "date_end",
                           "DATETIME DEFAULT NULL");
   $migration->changeField("glpi_plugin_news_alerts",
                           "date_start", "date_start",
                           "DATETIME DEFAULT NULL");

   if (FieldExists("glpi_plugin_news_alerts", "profiles_id")) {
      // migration of direct profiles into targets table
      $query_targets = "INSERT INTO glpi_plugin_news_alerts_targets
                           (plugin_news_alerts_id, itemtype, items_id)
                           SELECT id, 'Profile', profiles_id
                           FROM glpi_plugin_news_alerts";
      $res_targets = $DB->query($query_targets) or die("fail to migration targets");

      //drop old field
      $migration->dropField("glpi_plugin_news_alerts", "profiles_id");
   }

   $migration->migrationOneTable("glpi_plugin_news_alerts");
   return true;
}

function plugin_news_uninstall() {
   global $DB;

   $DB->query("DROP TABLE IF EXISTS `glpi_plugin_news_alerts`;");
   $DB->query("DROP TABLE IF EXISTS `glpi_plugin_news_profiles`;");
   $DB->query("DROP TABLE IF EXISTS `glpi_plugin_news_alerts_users`;");
   $DB->query("DROP TABLE IF EXISTS `glpi_plugin_news_alerts_targets`;");
   $DB->query("DELETE FROM `glpi_profiles` WHERE `name` LIKE '%plugin_news%';");

   return true;
}
