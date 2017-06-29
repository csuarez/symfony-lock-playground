FROM ausov/docker-ci-node-php

RUN npm install pm2 -g

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

RUN apt-get update && apt-get install -y php7.0-memcached

RUN mkdir /app

RUN pm2 flush

CMD ["sh", "/app/init.sh"]