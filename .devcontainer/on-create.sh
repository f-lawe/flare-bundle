echo 'debconf debconf/frontend select Noninteractive' | debconf-set-selections

apt update
apt install -y git  libicu-dev libzip-dev zip

curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
PATH="$PATH:/usr/local/bin"

docker-php-ext-install intl zip
