#!/bin/sh

cd /app
composer install
pm2 logs