#!/bin/bash
# ------------------------------------------------------------------
# Derek Maxson
# Backs up Moodle database and files
# ------------------------------------------------------------------


echo "==============================="
BOXID=$(cat /sys/class/net/eth0/address | tr ':' '-' | tr -d '\r')
echo "Making A Backup of The Well: ${BOXID}"
echo "==============================="

echo "Creating /tmp/thewellbackup/"
rm -rf /tmp/thewellbackup
mkdir /tmp/thewellbackup
mkdir /tmp/thewellbackup/sql

echo "Creating Dump of Postgres Database"
cd /tmp/thewellbackup/sql
sudo -u postgres pg_dump moodle > /tmp/thewellbackup/sql/moodle-database.sql

echo "Performing tar/gzip backup file"
cd /tmp/thewellbackup/
tar cvfz thewellbackup.${BOXID}.tar.gz sql/ /var/www/moodledata/filedir 

echo "Backup complete: /tmp/thewellbackup/thewellbackup.${BOXID}.tar.gz"
