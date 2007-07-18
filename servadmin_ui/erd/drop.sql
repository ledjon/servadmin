# ---------------------------------------------------------------------- #
# Script generated with: DeZign for Databases v4.2.0                     #
# Target DBMS:           MySQL 5                                         #
# Project file:          ERD01.dez                                       #
# Project name:          ServAdmin UI                                    #
# Author:                Jon Coulter                                     #
# Script type:           Database drop script                            #
# Created on:            2007-07-18 15:11                                #
# ---------------------------------------------------------------------- #


# ---------------------------------------------------------------------- #
# Drop foreign key constraints                                           #
# ---------------------------------------------------------------------- #

ALTER TABLE account_server DROP FOREIGN KEY account_account_server;

ALTER TABLE account_server DROP FOREIGN KEY server_account_server;

ALTER TABLE support_ticket DROP FOREIGN KEY account_support_ticket;

ALTER TABLE support_ticket DROP FOREIGN KEY support_ticket_support_ticket;

# ---------------------------------------------------------------------- #
# Drop table "account"                                                   #
# ---------------------------------------------------------------------- #

# Drop constraints #

ALTER TABLE account DROP PRIMARY KEY;

# Drop table #

DROP TABLE account;

# ---------------------------------------------------------------------- #
# Drop table "server"                                                    #
# ---------------------------------------------------------------------- #

# Drop constraints #

ALTER TABLE server DROP PRIMARY KEY;

# Drop table #

DROP TABLE server;

# ---------------------------------------------------------------------- #
# Drop table "account_server"                                            #
# ---------------------------------------------------------------------- #

# Drop constraints #

ALTER TABLE account_server DROP PRIMARY KEY;

# Drop table #

DROP TABLE account_server;

# ---------------------------------------------------------------------- #
# Drop table "support_ticket"                                            #
# ---------------------------------------------------------------------- #

# Drop constraints #

ALTER TABLE support_ticket ALTER COLUMN ticketstatus DROP DEFAULT;

ALTER TABLE support_ticket DROP PRIMARY KEY;

# Drop table #

DROP TABLE support_ticket;
