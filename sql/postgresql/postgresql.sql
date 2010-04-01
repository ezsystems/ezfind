CREATE TABLE ezfind_elevate_configuration (
    search_query varchar(255) NOT NULL default '',
    contentobject_id integer NOT NULL default 0,
    language_code varchar(20) NOT NULL,
    PRIMARY KEY (search_query, contentobject_id, language_code)
);

CREATE INDEX ezfind_elevate_configuration__search_query ON ezfind_elevate_configuration USING btree (search_query);

