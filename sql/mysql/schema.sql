CREATE TABLE `ezfind_elevate_configuration` (
    `search_query` varchar(255) NOT NULL default '',
    `contentobject_id` int(11) NOT NULL default '0',
    `language_code` varchar(20) NOT NULL default '',
    PRIMARY KEY (`search_query`,`contentobject_id`,`language_code`),
    KEY `ezfind_elevate_configuration__search_query` (`search_query`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

