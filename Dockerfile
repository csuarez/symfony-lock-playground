FROM ausov/docker-ci-node-php

RUN npm install pm2 -g

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer 

RUN mkdir /app

RUN pm2 flush

CMD ["pm2", "logs"]