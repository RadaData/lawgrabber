#!/bin/sh
rm tests/db/stub.sqlite
rm tests/db/test.sqlite
touch tests/db/stub.sqlite

mv .env .env_bak

sed 's/DB_/#DB_/g' .env_bak > .env
echo "DB_CONNECTION=sqlite" >> .env
echo "DB_DATABASE=tests/db/stub.sqlite" >> .env

php artisan migrate

rm .env
mv .env_bak .env

cp tests/db/stub.sqlite tests/db/test.sqlite
