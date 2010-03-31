-- note: the DEFAULT '' NOT NULL are OK, since NOT NULL takes pecendence and we do not rely on ezdbschema to remove the default clause

CREATE TABLE ezfind_elevate_configuration (
    search_query varchar2(255) default '' NOT NULL,
    contentobject_id integer default 0 NOT NULL,
    language_code varchar2(20) NOT NULL,
    PRIMARY KEY (search_query, contentobject_id, language_code)
);

CREATE INDEX ezfind_elevate_config_sq ON ezfind_elevate_configuration (search_query);

