#!/bin/sh
echo 'Creating database backup on server...'
ssh forge@radadata.com '/usr/bin/mysqldump --add-drop-table lawgrabber | /bin/gzip > /home/forge/lawgrabber_prod.sql.gz'

echo 'Creating local database dump...'
mysqldump --add-drop-table lawgrabber | /usr/bin/gzip > /Users/Admin/Downloads/lawgrabber_dev.sql.gz

echo 'Uploading dump...'
scp ~/Downloads/lawgrabber_dev.sql.gz forge@radadata.com:/home/forge/lawgrabber_dev.sql.gz

echo 'Applying database dump on server...'
ssh forge@radadata.com 'gunzip < /home/forge/lawgrabber_dev.sql.gz | mysql --defaults-group-suffix=forge'
ssh forge@radadata.com 'rm /home/forge/lawgrabber_dev.sql.gz'



echo 'Creating downloads cache backup on server...'
ssh forge@radadata.com 'cd /home/forge/; tar -zcf downloads.tar.gz downloads; rm downloads_bk.tar.gz; mv downloads.tar.gz downloads_bk.tar.gz'

echo 'Compressing local downloads cache...'
cd ~/www/radadata/; tar -zcf downloads.tar.gz downloads

echo 'Uploading archive...'
scp ~/www/radadata/downloads.tar.gz forge@radadata.com:/home/forge/downloads.tar.gz

echo 'Extracting downloads cache to a server file system...'
ssh forge@radadata.com 'rm -rf /home/forge/downloads; cd /home/forge; tar -zxf downloads.tar.gz'
ssh forge@radadata.com 'rm /home/forge/downloads.tar.gz'