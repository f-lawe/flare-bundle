#!/bin/bash

cd
sudo chown -R docker:docker /workspaces/flare-bundle

# Copy config files from host
php -r "file_put_contents('./copy_if_exists.sh', file_get_contents('https://gist.githubusercontent.com/f-lawe/f2b99dabbc761a0f90b44952ac021363/raw/copy_if_exists.sh'));"
chmod +x ./copy_if_exists.sh

./copy_if_exists.sh /tmp/host/.bashrc ~/.bashrc 1000:1000 755 644
./copy_if_exists.sh /tmp/host/kilo.jsonc ~/.config/kilo/kilo.jsonc 1000:1000 755 644
./copy_if_exists.sh /tmp/host/kilo ~/.local/share/kilo 1000:1000 755 644

rm ./copy_if_exists.sh

# Fix .ssh folder permissions
if [ -d ~/.ssh ]; then
    chmod 600 ~/.ssh/*
fi

# History persistence
sudo chown -R docker:docker ~/.bash

if [ ! -f ~/.bash/history ]; then
  touch ~/.bash/history
fi

chmod 600 ~/.bash/history
rm -f ~/.bash_history
ln -s ~/.bash/history ~/.bash_history
