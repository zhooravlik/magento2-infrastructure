#!/bin/bash

echo 'Installing git, mc, rsync, httpd...'
yum -y install git mc rsync httpd &&

if ! mysql --version | grep ' 5.6'>/dev/null 2>&1; then
  echo 'Installing MySQL 5.6...'
  rpm -Uvh http://dev.mysql.com/get/mysql-community-release-el6-5.noarch.rpm
  yum install -y mysql-community-server
fi &&

if ! php -v | grep 'PHP 5.5'>/dev/null; then
  echo 'Installing php 5.5...'
  rpm -Uvh https://mirror.webtatic.com/yum/el6/latest.rpm
  yum -y install php55w php55w-intl php55w-mcrypt php55w-mbstring php55w-mysql php55w-pdo php55w-mbstring php55w-soap\
    php55w-pecl-zendopcache php55w-xml php55w-gd php55w-pecl-imagick
fi &&

if ! which htop >/dev/null 2>&1; then
  echo 'Installing htop...'
  rpm -Uvh http://pkgs.repoforge.org/rpmforge-release/rpmforge-release-0.5.3-1.el6.rf.x86_64.rpm
  yum -y install htop
fi &&

echo 'Updating configs...' &&
sed -i 's#AllowOverride None#AllowOverride All#' /etc/httpd/conf/httpd.conf &&
sed -i 's#KeepAlive Off#KeepAlive On#' /etc/httpd/conf/httpd.conf &&

sed -i '$a\' /etc/php.d/opcache.ini &&
sed -i 's#^.*opcache.memory_consumption=.*#opcache.memory_consumption=512#' /etc/php.d/opcache.ini &&
sed -i 's#^.*opcache.enable_cli=.*#opcache.enable_cli=1#' /etc/php.d/opcache.ini &&
sed -i 's#^.*opcache.max_accelerated_files=.*#opcache.max_accelerated_files=30000#' /etc/php.d/opcache.ini &&
sed -i 's#^.*opcache.enable_file_override=.*#opcache.enable_file_override=1#' /etc/php.d/opcache.ini &&

sed -i '$a\' /etc/php.ini &&
(grep -e ^date.timezone /etc/php.ini >/dev/null ||  echo date.timezone=UTC >> /etc/php.ini) &&
sed -i 's#^memory_limit.*#memory_limit=1536M#' /etc/php.ini &&

if ! which composer >/dev/null 2>&1; then
  echo 'Installing composer...' &&
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer
fi &&

service mysqld stop &&
service httpd stop &&

if ! grep /var/lib/mysql /etc/fstab | grep tmpfs > /dev/null 2>&1; then
  echo 'Creating tmpfs for mysql...'
  (test -d /var/lib/mysql.bak || mv /var/lib/mysql /var/lib/mysql.bak) &&
  (\
    umount /var/lib/mysql;
    rm -rf /var/lib/mysql;
    mkdir -p /var/lib/mysql &&
    mount -t tmpfs -o size=1G tmpfs /var/lib/mysql\
  ) &&
  sed -i '$a\' /etc/fstab &&
  grep /var/lib/mysql /etc/mtab >> /etc/fstab &&
  cp -R /var/lib/mysql.bak/. /var/lib/mysql
fi &&

if ! grep /var/www/html /etc/fstab | grep tmpfs > /dev/null 2>&1; then
  echo 'Creating tmpfs for httpd...'
  umount /var/www/html
  rm -rf /var/www/html &&
  mkdir -p /var/www/html &&
  mount -t tmpfs -o size=2G tmpfs /var/www/html &&
  sed -i '$a\' /etc/fstab &&
  grep /var/www/html /etc/mtab >> /etc/fstab
fi &&

chown -R apache:apache /var/www &&
chkconfig httpd on &&
chkconfig mysqld on &&

echo 'Starting services...' &&
service mysqld start &&
service httpd start &&
mysql -e "GRANT ALL PRIVILEGES ON *.* TO 'magento'@'localhost' IDENTIFIED BY 'magento'; " &&
chsh -s /bin/bash apache &&
echo "apache ALL=(ALL) NOPASSWD: /sbin/service httpd restart" > /etc/sudoers.d/apache &&
echo -e '\033[0;32mSuccess\033[0m' || { echo -e '\033[1;31mFAILED\033[0m'; exit 1; }
