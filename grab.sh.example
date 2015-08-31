#!/bin/sh
echo 'Creating database dump on server...'
ssh forge@radadata.com '/usr/bin/mysqldump --add-drop-table lawgrabber | /bin/gzip > /home/forge/lawgrabber_prod.sql.gz'

echo 'Downloading dump...'
scp forge@radadata.com:/home/forge/lawgrabber_prod.sql.gz ~/Downloads/lawgrabber_prod.sql.gz
ssh forge@radadata.com 'rm /home/forge/lawgrabber_prod.sql.gz'

echo 'Applying dump to local database...'
gunzip < ~/Downloads/lawgrabber_prod.sql.gz | mysql --defaults-group-suffix=lawgrabber



echo 'Compressing downloads cache on server...'
ssh forge@radadata.com 'cd /home/forge/; tar -zcf downloads.tar.gz downloads'

echo 'Downloading archive...'
scp forge@radadata.com:/home/forge/downloads.tar.gz ~/www/radadata/downloads.tar.gz
ssh forge@radadata.com 'rm /home/forge/downloads.tar.gz'

echo 'Extracting downloads cache to local file system...'
rm -rf ~/www/radadata/downloads
cd ~/www/radadata/
tar -zxf downloads.tar.gz