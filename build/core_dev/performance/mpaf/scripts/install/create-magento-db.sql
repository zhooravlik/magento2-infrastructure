#
# Delete user and database if they exist
#
DROP DATABASE IF EXISTS `magento`;
# Since DROP USER does not support 'IF EXISTS'
# the following grant will create a new account with no privileges
GRANT USAGE ON *.* TO 'magento'@'%'; 
DROP USER 'magento'@'%';
#
# Create User and Database
#
CREATE database magento;
GRANT USAGE ON *.* TO 'magento'@'%' IDENTIFIED BY 'magento';
GRANT ALL ON magento.* TO 'magento'@'%';
#
EXIT