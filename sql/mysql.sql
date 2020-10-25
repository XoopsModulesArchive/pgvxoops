CREATE TABLE pgv_blocks (
    b_id       INT(11) NOT NULL DEFAULT '0',
    b_username VARCHAR(100)     DEFAULT NULL,
    b_location VARCHAR(30)      DEFAULT NULL,
    b_order    INT(11)          DEFAULT NULL,
    b_name     VARCHAR(255)     DEFAULT NULL,
    b_config   TEXT,
    PRIMARY KEY (b_id)
)
    ENGINE = ISAM;

CREATE TABLE pgv_dates (
    d_day   INT(11) UNSIGNED DEFAULT NULL,
    d_month VARCHAR(5)       DEFAULT NULL,
    d_year  INT(11) UNSIGNED DEFAULT NULL,
    d_fact  VARCHAR(10)      DEFAULT NULL,
    d_gid   VARCHAR(30)      DEFAULT NULL,
    d_file  VARCHAR(255)     DEFAULT NULL,
    KEY date_day (d_day),
    KEY date_month (d_month),
    KEY date_year (d_year),
    KEY date_fact (d_fact),
    KEY date_gid (d_gid),
    KEY date_file (d_file)
)
    ENGINE = ISAM;

CREATE TABLE pgv_families (
    f_id     VARCHAR(30)  DEFAULT NULL,
    f_file   VARCHAR(255) DEFAULT NULL,
    f_husb   VARCHAR(30)  DEFAULT NULL,
    f_wife   VARCHAR(30)  DEFAULT NULL,
    f_chil   VARCHAR(255) DEFAULT NULL,
    f_gedcom TEXT,
    KEY fam_id (f_id),
    KEY fam_file (f_file)
)
    ENGINE = ISAM;


CREATE TABLE pgv_favorites (
    fv_id       INT(11) NOT NULL DEFAULT '0',
    fv_username VARCHAR(30)      DEFAULT NULL,
    fv_gid      VARCHAR(10)      DEFAULT NULL,
    fv_type     VARCHAR(10)      DEFAULT NULL,
    fv_file     VARCHAR(100)     DEFAULT NULL,
    PRIMARY KEY (fv_id)
)
    ENGINE = ISAM;


CREATE TABLE pgv_individuals (
    i_id      VARCHAR(30)  DEFAULT NULL,
    i_file    VARCHAR(255) DEFAULT NULL,
    i_rin     VARCHAR(30)  DEFAULT NULL,
    i_name    VARCHAR(255) DEFAULT NULL,
    i_isdead  INT(1)       DEFAULT '1',
    i_gedcom  TEXT,
    i_letter  VARCHAR(5)   DEFAULT NULL,
    i_surname VARCHAR(100) DEFAULT NULL,
    KEY indi_id (i_id),
    KEY indi_name (i_name),
    KEY indi_letter (i_letter),
    KEY indi_file (i_file),
    KEY indi_surn (i_surname)
)
    ENGINE = ISAM;

CREATE TABLE pgv_messages (
    m_id      INT(11) NOT NULL DEFAULT '0',
    m_from    VARCHAR(255)     DEFAULT NULL,
    m_to      VARCHAR(30)      DEFAULT NULL,
    m_subject VARCHAR(255)     DEFAULT NULL,
    m_body    TEXT,
    m_created VARCHAR(255)     DEFAULT NULL,
    PRIMARY KEY (m_id)
)
    ENGINE = ISAM;

CREATE TABLE pgv_names (
    n_gid     VARCHAR(30)  DEFAULT NULL,
    n_file    VARCHAR(255) DEFAULT NULL,
    n_name    VARCHAR(255) DEFAULT NULL,
    n_letter  VARCHAR(5)   DEFAULT NULL,
    n_surname VARCHAR(100) DEFAULT NULL,
    n_type    VARCHAR(10)  DEFAULT NULL,
    KEY name_gid (n_gid),
    KEY name_name (n_name),
    KEY name_letter (n_letter),
    KEY name_type (n_type),
    KEY name_surn (n_surname)
)
    ENGINE = ISAM;

CREATE TABLE pgv_news (
    n_id       INT(11) NOT NULL DEFAULT '0',
    n_username VARCHAR(100)     DEFAULT NULL,
    n_date     INT(11)          DEFAULT NULL,
    n_title    VARCHAR(255)     DEFAULT NULL,
    n_text     TEXT,
    PRIMARY KEY (n_id)
)
    ENGINE = ISAM;

CREATE TABLE pgv_other (
    o_id     VARCHAR(30)  DEFAULT NULL,
    o_file   VARCHAR(255) DEFAULT NULL,
    o_type   VARCHAR(20)  DEFAULT NULL,
    o_gedcom TEXT,
    KEY other_id (o_id),
    KEY other_file (o_file)
)
    ENGINE = ISAM;


CREATE TABLE pgv_placelinks (
    pl_p_id INT(11)      DEFAULT NULL,
    pl_gid  VARCHAR(30)  DEFAULT NULL,
    pl_file VARCHAR(255) DEFAULT NULL,
    KEY plindex_place (pl_p_id),
    KEY plindex_gid (pl_gid),
    KEY plindex_file (pl_file)
)
    ENGINE = ISAM;


CREATE TABLE pgv_places (
    p_id        INT(11) NOT NULL DEFAULT '0',
    p_place     VARCHAR(150)     DEFAULT NULL,
    p_level     INT(11)          DEFAULT NULL,
    p_parent_id INT(11)          DEFAULT NULL,
    p_file      VARCHAR(255)     DEFAULT NULL,
    PRIMARY KEY (p_id),
    KEY place_place (p_place),
    KEY place_level (p_level),
    KEY place_parent (p_parent_id),
    KEY place_file (p_file)
)
    ENGINE = ISAM;


CREATE TABLE pgv_sources (
    s_id     VARCHAR(30)  DEFAULT NULL,
    s_file   VARCHAR(255) DEFAULT NULL,
    s_name   VARCHAR(255) DEFAULT NULL,
    s_gedcom TEXT,
    KEY sour_id (s_id),
    KEY sour_name (s_name),
    KEY sour_file (s_file)
)
    ENGINE = ISAM;

CREATE TABLE pgv_tblver (
    t_table   VARCHAR(255)     NOT NULL DEFAULT '',
    t_version INT(10) UNSIGNED NOT NULL DEFAULT '0'
)
    ENGINE = ISAM;

INSERT INTO pgv_tblver (t_table, t_version)
VALUES ('pgv_blocks', 1);
INSERT INTO pgv_tblver (t_table, t_version)
VALUES ('pgv_dates', 1);
INSERT INTO pgv_tblver (t_table, t_version)
VALUES ('pgv_families', 1);
INSERT INTO pgv_tblver (t_table, t_version)
VALUES ('pgv_favorites', 1);
INSERT INTO pgv_tblver (t_table, t_version)
VALUES ('pgv_individuals', 1);
INSERT INTO pgv_tblver (t_table, t_version)
VALUES ('pgv_messages', 1);
INSERT INTO pgv_tblver (t_table, t_version)
VALUES ('pgv_names', 1);
INSERT INTO pgv_tblver (t_table, t_version)
VALUES ('pgv_news', 1);
INSERT INTO pgv_tblver (t_table, t_version)
VALUES ('pgv_other', 1);
INSERT INTO pgv_tblver (t_table, t_version)
VALUES ('pgv_placelinks', 1);
INSERT INTO pgv_tblver (t_table, t_version)
VALUES ('pgv_places', 1);
INSERT INTO pgv_tblver (t_table, t_version)
VALUES ('pgv_sources', 1);

CREATE TABLE pgv_users (
    u_xoopsid       INT(10) UNSIGNED,
    u_username      VARCHAR(255),
    u_gedcomid      TEXT,
    u_rootid        TEXT,
    u_canedit       TEXT,
    u_contactmethod VARCHAR(255),
    u_defaulttab    INT UNSIGNED
)
    ENGINE = ISAM;

CREATE TABLE pgv_media (
    m_id      INT(11)      NOT NULL AUTO_INCREMENT,
    m_media   VARCHAR(15)  NOT NULL,
    m_ext     CHAR(6)      NOT NULL,
    m_titl    VARCHAR(255) NULL,
    m_file    VARCHAR(255) NOT NULL,
    m_gedfile VARCHAR(255) NOT NULL,
    m_gedrec  TEXT,
    PRIMARY KEY (m_id),
    KEY m_media (m_media)
)
    ENGINE = ISAM;

CREATE TABLE pgv_media_mapping (
    m_id      INT(11)      NOT NULL AUTO_INCREMENT,
    m_media   VARCHAR(15)  NOT NULL,
    m_indi    VARCHAR(15)  NOT NULL,
    m_order   INT(11)      NOT NULL,
    m_gedfile VARCHAR(255) NOT NULL,
    m_gedrec  TEXT,
    PRIMARY KEY (m_id),
    KEY m_media (m_media)
)
    ENGINE = ISAM;
