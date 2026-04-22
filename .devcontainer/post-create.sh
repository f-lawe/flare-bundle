apt update
apt install -y git libzip-dev unzip

curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
PATH="$PATH:/usr/local/bin"

docker-php-ext-install zip
