# Spoken Dialog System (SDS)

![Screenshot](https://github.com/emavgl/lus_sds/raw/master/images/screenshot.png)

## Report Abstract
In this report we show how to design a spoken dialog system using Web Speech API and the FST models that we have created
for the first midterm project. The report will describe how the system works, focusing on some important aspects of a di-
alog system such as the implementation of error recovery, confirmation recovery, the use of a multi-modal system etc.

For more information, please check the file *report.pdf*.

## Prerequisites:
- web server
- PHP5
- MySQL
- OpenFst binaries(http://www.openfst.org/)
- fstprintstrings

## Setting up the database
```
CREATE USER 'lus'@'localhost' IDENTIFIED BY 'luspassword';
GRANT ALL PRIVILEGES ON * . * TO 'lus'@'localhost';
FLUSH PRIVILEGES;
create database moviedb;
mysql -ulus -pluspassword moviedb < moviedb.sql
```

## Application info
- client application name: app.html
- server application name: controller1.php

## Demo video (for unitn users, ask for access)
- demo1: https://drive.google.com/open?id=0B8rf8YUqVOCxQUYzTEdVZkpIalE
- demo2: https://drive.google.com/open?id=0B8rf8YUqVOCxUU50UTJjUWgzNWM

If necessary, ask for high quality video at emanuele.viglianisi@studenti.unitn.it
