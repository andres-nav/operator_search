FROM php:alpine

RUN apk add --no-cache --virtual .build-deps autoconf g++ make

RUN pecl install -o -f redis \
  &&  rm -rf /tmp/pear \
  &&  echo "extension=redis.so" > /usr/local/etc/php/conf.d/redis.ini

ADD mailreflector.php /
CMD ["php","/mailreflector.php"]
