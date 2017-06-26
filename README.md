# Spoken Dialog System (SDS)
### Emanuele Viglianisi

## Prerequisites:
- web server
- PHP5
- MySQL
- OpenFst binaries(http://www.openfst.org/)
- fstprintstrings

## Setting up the database
CREATE USER 'lus'@'localhost' IDENTIFIED BY 'luspassword';
GRANT ALL PRIVILEGES ON * . * TO 'lus'@'localhost';
FLUSH PRIVILEGES;
create database moviedb;
mysql -ulus -pluspassword moviedb < moviedb.sql

## Application info
client application name: app.html
server application name: controller1.php

## Demo video (for unitn users)
demo1: https://drive.google.com/open?id=0B8rf8YUqVOCxQUYzTEdVZkpIalE
demo2: https://drive.google.com/open?id=0B8rf8YUqVOCxUU50UTJjUWgzNWM
If necessary, ask for high quality video at emanuele.viglianisi@studenti.unitn.it