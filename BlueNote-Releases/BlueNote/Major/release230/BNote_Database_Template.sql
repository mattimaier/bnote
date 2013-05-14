-- phpMyAdmin SQL Dump
-- version 2.8.2.4
-- http://www.phpmyadmin.net
-- 
-- Host: localhost:3306
-- Erstellungszeit: 27. April 2013 um 13:45
-- Server Version: 5.0.18
-- PHP-Version: 5.2.6
-- 
-- Datenbank
-- 
USE bluenote_demo03;

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `address`
-- 

CREATE TABLE `address` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `street` varchar(45) NOT NULL,
  `city` varchar(45) NOT NULL,
  `zip` varchar(45) default NULL,
  `country` varchar(45) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=27 ;

-- 
-- Daten für Tabelle `address`
-- 

INSERT INTO `address` (`id`, `street`, `city`, `zip`, `country`) VALUES (1, 'Parkstr. 18', 'Remshalden', '73630', '');

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `category`
-- 

CREATE TABLE `category` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(60) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=9 ;

-- 
-- Daten fü Tabelle `category`
-- 

INSERT INTO `category` (`id`, `name`) VALUES (1, 'Streicher'),
(2, 'Blechbläser'),
(3, 'Holzbläser'),
(4, 'Rhythmusgruppe'),
(5, 'Gesang'),
(6, 'Dirigent'),
(7, 'Organisation'),
(8, 'Sonstige');

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `composer`
-- 

CREATE TABLE `composer` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(45) NOT NULL,
  `notes` text,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;


-- --------------------------------------------------------

-- 
-- Tabellenstruktur `concert`
-- 

CREATE TABLE `concert` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `begin` datetime NOT NULL,
  `end` datetime default NULL,
  `location` int(10) unsigned NOT NULL,
  `program` int(10) unsigned default NULL,
  `notes` text,
  `contact` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;


-- --------------------------------------------------------

-- 
-- Tabellenstruktur `concert_user`
-- 

CREATE TABLE `concert_user` (
  `concert` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `participate` tinyint(4) NOT NULL,
  `reason` varchar(200) default NULL,
  PRIMARY KEY  (`concert`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

-- 
-- Tabellenstruktur `contact`
-- 

CREATE TABLE `contact` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `surname` varchar(50) default NULL,
  `name` varchar(50) default NULL,
  `phone` varchar(45) default NULL,
  `fax` varchar(45) default NULL,
  `mobile` varchar(30) default NULL,
  `business` varchar(30) default NULL,
  `email` varchar(100) default NULL,
  `web` varchar(150) default NULL,
  `notes` text,
  `address` int(10) unsigned NOT NULL,
  `status` varchar(10) default NULL,
  `instrument` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=26 ;

-- 
-- Daten `contact`
-- 

INSERT INTO `contact` (`id`, `surname`, `name`, `phone`, `fax`, `mobile`, `business`, `email`, `web`, `notes`, `address`, `status`, `instrument`) VALUES (1, 'Maier', 'Matti', '', '', '', '089 80912915', 'info@mattimaier.de', 'www.mattimaier.de', 'Ansprechpartner rund ums System', 1, 'ADMIN', 23);

-- --------------------------------------------------------

-- 
-- Tabellenstruktur `gallery`
-- 

CREATE TABLE `gallery` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(50) NOT NULL,
  `previewimage` int(10) unsigned default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

-- 
-- Tabellenstruktur `galleryimage`
-- 

CREATE TABLE `galleryimage` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `filename` varchar(200) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `gallery` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

-- 
-- Tabellenstruktur`genre`
-- 

CREATE TABLE `genre` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(45) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=18 ;

-- 
-- Daten `genre`
-- 

INSERT INTO `genre` (`id`, `name`) VALUES (1, 'Swing'),
(2, 'Latin'),
(3, 'Jazz'),
(4, 'Traditional Jazz'),
(5, 'Pop'),
(6, 'Rock'),
(7, 'Blues'),
(8, 'Blues Rock'),
(9, 'Metal'),
(10, 'Klassik'),
(11, 'Bebop'),
(12, 'Dixyland'),
(13, 'Free Jazz'),
(14, 'Smooth Jazz'),
(15, 'Instrumental Jazz'),
(16, 'Vocal Jazz'),
(17, 'Funk');

-- --------------------------------------------------------

-- 
-- Tabellenstruktur `infos`
-- 

CREATE TABLE `infos` (
  `id` int(11) NOT NULL auto_increment,
  `author` int(11) NOT NULL,
  `createdOn` datetime NOT NULL,
  `editedOn` datetime default NULL,
  `title` varchar(200) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `author` (`author`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;


-- --------------------------------------------------------

-- 
-- Tabellenstruktur  `instrument`
-- 

CREATE TABLE `instrument` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(60) NOT NULL,
  `category` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=25 ;


INSERT INTO `instrument` (`id`, `name`, `category`) VALUES (1, 'Dirigent', 6),
(2, 'Gesang', 5),
(3, 'Organisator', 7),
(4, 'Klavier / ePiano', 4),
(5, 'Orgel', 4),
(6, 'Elektro Orgel', 4),
(7, 'Schlagzeug', 4),
(8, 'Kontrabass', 4),
(9, 'E-Bass', 4),
(10, 'Tuba', 4),
(11, 'Posaune', 2),
(12, 'Trompete', 2),
(13, 'Altsaxophon', 3),
(14, 'Tenorsaxophon', 3),
(15, 'Bariton Saxophon', 3),
(16, 'Sopran Saxophon', 3),
(17, 'Klarinette', 3),
(18, 'Bassklarinette', 3),
(19, 'Geige', 1),
(20, 'Bratsche', 1),
(21, 'Violoncello', 1),
(22, 'Gambe', 8),
(23, 'Sonstige', 8),
(24, 'Gitarre', 4);

-- --------------------------------------------------------

-- 
-- Tabellenstruktur  `location`
-- 

CREATE TABLE `location` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(60) NOT NULL,
  `notes` text,
  `address` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;


-- --------------------------------------------------------

-- 
-- Tabellenstruktur `module`
-- 

CREATE TABLE `module` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(45) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=15 ;

-- 
-- Daten `module`
-- 

INSERT INTO `module` (`id`, `name`) VALUES (1, 'Start'),
(2, 'User'),
(3, 'Kontakte'),
(4, 'Konzerte'),
(5, 'Proben'),
(6, 'Repertoire'),
(7, 'Kommunikation'),
(8, 'Locations'),
(9, 'Kontaktdaten'),
(10, 'Hilfe'),
(11, 'Website'),
(12, 'Share'),
(13, 'Mitspieler'),
(14, 'Abstimmung');

-- --------------------------------------------------------

-- 
-- Tabellenstruktur `privilege`
-- 

CREATE TABLE `privilege` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user` int(11) unsigned NOT NULL,
  `module` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=20 ;

-- 
-- Daten `privilege`
-- 

INSERT INTO `privilege` (`id`, `user`, `module`) VALUES
(12, 1, 12),
(11, 1, 11),
(10, 1, 10),
(9, 1, 9),
(8, 1, 8),
(7, 1, 7),
(6, 1, 6),
(5, 1, 5),
(4, 1, 4),
(3, 1, 3),
(2, 1, 2),
(1, 1, 1);

-- --------------------------------------------------------

-- 
-- Tabellenstruktur `program`
-- 

CREATE TABLE `program` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `notes` text,
  `isTemplate` tinyint(1) default NULL,
  `name` varchar(80) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;


-- --------------------------------------------------------

-- 
-- Tabellenstruktur `program_song`
-- 

CREATE TABLE `program_song` (
  `program` int(10) unsigned NOT NULL,
  `song` int(10) unsigned NOT NULL,
  `rank` int(11) default NULL,
  PRIMARY KEY  (`program`,`song`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


-- --------------------------------------------------------

-- 
-- Tabellenstruktur `rehearsal`
-- 

CREATE TABLE `rehearsal` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `begin` datetime NOT NULL,
  `end` datetime default NULL,
  `notes` text,
  `location` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;


-- --------------------------------------------------------

-- 
-- Tabellenstruktur `rehearsal_song`
-- 

CREATE TABLE `rehearsal_song` (
  `rehearsal` int(11) NOT NULL,
  `song` int(11) NOT NULL,
  `notes` varchar(200) default NULL,
  PRIMARY KEY  (`rehearsal`,`song`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Tabellenstruktur  `rehearsal_user`
-- 

CREATE TABLE `rehearsal_user` (
  `rehearsal` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `participate` tinyint(4) NOT NULL,
  `reason` varchar(200) default NULL,
  PRIMARY KEY  (`rehearsal`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

-- 
-- Tabellenstruktur `song`
-- 

CREATE TABLE `song` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `title` varchar(60) NOT NULL,
  `length` time default NULL,
  `notes` text,
  `genre` int(10) unsigned NOT NULL,
  `composer` int(10) unsigned NOT NULL,
  `status` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;


-- --------------------------------------------------------

-- 
-- Tabellenstruktur `status`
-- 

CREATE TABLE `status` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(45) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;

-- 
-- Daten `status`
-- 

INSERT INTO `status` (`id`, `name`) VALUES (1, 'Konzertreif'),
(2, 'Kernrepertoire'),
(3, 'Noten vorhanden'),
(4, 'ben&ouml;tigt weitere Proben'),
(5, 'nicht im Notenbestand'),
(6, 'Idee');

-- --------------------------------------------------------

-- 
-- Tabellenstruktur `user`
-- 

CREATE TABLE `user` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `isActive` tinyint(1) NOT NULL default '1',
  `login` varchar(45) NOT NULL,
  `password` varchar(60) NOT NULL,
  `lastlogin` datetime default NULL,
  `contact` int(10) unsigned NOT NULL,
  `pin` int(6) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=25 ;

-- 
-- Daten `user`
-- 

INSERT INTO `user` (`id`, `isActive`, `login`, `password`, `lastlogin`, `contact`, `pin`) VALUES
(1, 1, 'admin', '1$KHHj7FY4MLs', NULL, 1, NULL),
(2, 1, 'stefankreminski', '1$4OdlaPnE5KQ', NULL, 3, NULL);

-- --------------------------------------------------------

-- 
-- Tabellenstruktur `vote`
-- 

CREATE TABLE `vote` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) NOT NULL,
  `author` int(11) NOT NULL,
  `end` datetime NOT NULL,
  `is_multi` int(1) NOT NULL,
  `is_date` int(1) NOT NULL,
  `is_finished` int(1) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

-- 
-- Tabellenstruktur `vote_group`
-- 

CREATE TABLE `vote_group` (
  `vote` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  PRIMARY KEY  (`vote`,`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



-- --------------------------------------------------------

-- 
-- Tabellenstruktur `vote_option`
-- 

CREATE TABLE `vote_option` (
  `id` int(11) NOT NULL auto_increment,
  `vote` int(11) NOT NULL,
  `name` varchar(100) default NULL,
  `odate` datetime default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Tabellenstruktur `vote_option_user`
-- 

CREATE TABLE `vote_option_user` (
  `vote_option` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  PRIMARY KEY  (`vote_option`,`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
