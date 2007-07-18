# ---------------------------------------------------------------------- #
# Script generated with: DeZign for Databases v4.2.0                     #
# Target DBMS:           MySQL 5                                         #
# Project file:          ERD01.dez                                       #
# Project name:          ServAdmin UI                                    #
# Author:                Jon Coulter                                     #
# Script type:           Database creation script                        #
# Created on:            2007-07-18 15:11                                #
# ---------------------------------------------------------------------- #


# ---------------------------------------------------------------------- #
# Tables                                                                 #
# ---------------------------------------------------------------------- #

# ---------------------------------------------------------------------- #
# Add table "account"                                                    #
# ---------------------------------------------------------------------- #

CREATE TABLE account (
    accountid INTEGER NOT NULL AUTO_INCREMENT,
    username VARCHAR(100),
    password VARCHAR(32),
    ownername VARCHAR(255),
    email VARCHAR(255),
    domain VARCHAR(255),
    accstatus VARCHAR(100),
    createdatetime DATETIME,
    CONSTRAINT PK_account PRIMARY KEY (accountid)
)
engine=innodb;

CREATE UNIQUE INDEX IDX_account_1 ON account (username);

CREATE INDEX IDX_account_2 ON account (username,password);

# ---------------------------------------------------------------------- #
# Add table "server"                                                     #
# ---------------------------------------------------------------------- #

CREATE TABLE server (
    serverid INTEGER NOT NULL AUTO_INCREMENT,
    servname VARCHAR(100),
    servkey VARCHAR(200),
    servurl VARCHAR(250),
    ftpurl VARCHAR(200),
    nameserver1 VARCHAR(200),
    nameserver2 VARCHAR(200),
    mailservname VARCHAR(200),
    smtpservname VARCHAR(200),
    tmpurl VARCHAR(250),
    CONSTRAINT PK_server PRIMARY KEY (serverid)
)
engine=innodb;

CREATE INDEX IDX_server_1 ON server (servkey);

# ---------------------------------------------------------------------- #
# Add table "account_server"                                             #
# ---------------------------------------------------------------------- #

CREATE TABLE account_server (
    accountid INTEGER NOT NULL,
    serverid INTEGER NOT NULL,
    CONSTRAINT PK_account_server PRIMARY KEY (accountid, serverid)
)
engine=innodb;

CREATE INDEX IDX_account_server_1 ON account_server (accountid);

CREATE INDEX IDX_account_server_2 ON account_server (serverid);

# ---------------------------------------------------------------------- #
# Add table "support_ticket"                                             #
# ---------------------------------------------------------------------- #

CREATE TABLE support_ticket (
    ticketid INTEGER NOT NULL AUTO_INCREMENT,
    accountid INTEGER NOT NULL,
    parentticketid INTEGER NOT NULL,
    topic VARCHAR(255),
    content LONGTEXT,
    createdatetime DATETIME,
    ticketstatus VARCHAR(20) DEFAULT 'open',
    CONSTRAINT PK_support_ticket PRIMARY KEY (ticketid)
)
engine=innodb;

CREATE INDEX IDX_support_ticket_1 ON support_ticket (accountid);

CREATE INDEX IDX_support_ticket_2 ON support_ticket (parentticketid);

# ---------------------------------------------------------------------- #
# Foreign key constraints                                                #
# ---------------------------------------------------------------------- #

ALTER TABLE account_server ADD CONSTRAINT account_account_server 
    FOREIGN KEY (accountid) REFERENCES account (accountid) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE account_server ADD CONSTRAINT server_account_server 
    FOREIGN KEY (serverid) REFERENCES server (serverid) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE support_ticket ADD CONSTRAINT account_support_ticket 
    FOREIGN KEY (accountid) REFERENCES account (accountid) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE support_ticket ADD CONSTRAINT support_ticket_support_ticket 
    FOREIGN KEY (parentticketid) REFERENCES support_ticket (ticketid) ON DELETE CASCADE ON UPDATE CASCADE;
