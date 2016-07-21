#!/usr/bin/env php
<?php
/**
 * Script for test migration tools
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

(new MigrationRunner(getopt('b:m:', ['m1-version:', 'config:'])))->execute();

class MigrationRunner
{
    /** Table prefix */
    const TABLES_PREFIX = 'magento_';

    /**
     * @var array
     */
    private $config = [];

    /**
     * @var array
     */
    private $mapping = [
        'base_path' => 'm',
        'm2_path' => 'b'
    ];

    /**
     * Path to local.xml
     */
    const LOCAL_XML = 'app/etc/local.xml';

    /**
     * @param array $config
     */
    public function __construct($config)
    {
        var_dump($config);
        $this->config = $config;
    }

    /**
     * Get option
     *
     * @param string $name
     * @param string|null $defaultValue
     * @return string
     * @throws Exception
     */
    private function getOption($name, $defaultValue = null)
    {
        $name = isset($this->mapping[$name]) ? $this->mapping[$name] : $name;
        if (!isset($this->config[$name])) {
            if ($defaultValue === null) {
                throw new \Exception('Illegal option . ' . $name);
            } else {
                return $defaultValue;
            }
        } else {
            return $this->config[$name];
        }
    }

    /**
     * Get capability env
     * @param string $name
     * @return string
     */
    private function getEnv($name)
    {
        return getenv('bamboo_capability_'.$name);
    }

    /**
     * Log arguments
     */
    private function log()
    {
        echo implode(' ', func_get_args()), PHP_EOL;
    }

    /**
     * Run command
     *
     * @param string $command
     * @param array $arguments
     * @param string $separator
     * @return array
     * @throws Exception
     */
    private function runCommand($command, $arguments = [], $separator = ' ')
    {
        $argumentsString = '';
        foreach ($arguments as $key => $value) {
            $argumentsString .= ' ' .(strlen($key) == 1 ? "-" : "--") . $key . $separator .  escapeshellarg($value);
        }
        echo 'Run: ' . $command . $argumentsString, PHP_EOL;
        exec($command . $argumentsString, $output, $result);
        if ($result != 0) {
            throw new \Exception('Error execute command: ' . $command . "\n" . implode("\n", $output));
        }
        return $output;
    }

    /**
     * Execute
     *
     * @throws Exception
     */
    public function execute()
    {
        $cwd = getcwd();

        $this->reinstallM2();
        $this->reinstallM1($this->getOption('m1-version'));
        chdir($this->getOption('m2_path') . '/' . getenv('bamboo_migration_tool_composer_path'));
        $this->log(getcwd());
        $this->updateMigrationConfig($this->getOption('config'));

        $output = $this->runCommand('php bin/migrate data', ['config' => 'etc/config.xml'], '=');
        $output = implode('', $output);

        //temporary solution, migration tools always return ok code
        $migrationResult = strpos($output, 'exception:') === false;
        if (!$migrationResult) {
            $this->log($output);
            throw new \Exception('Integrity check failed with output: ' . $output);
        }
        chdir($cwd);
    }

    /**
     * Get Db name
     *
     * @param string $suffix
     * @return string
     */
    private function getDbName($suffix)
    {
        return 'migration_tool_' . $suffix;
    }

    /**
     * Install Magento 1.x
     *
     * @param $version
     * @throws Exception
     */
    private function reinstallM1($version)
    {
        $this->log('Install M1');

        chdir($this->getOption('base_path'));
        $this->runCommand("git checkout {$version}");
        $dbName = $this->getDbName('m1');
        $this->cleanDb($dbName);
        if (file_exists(self::LOCAL_XML)) {
            unlink(self::LOCAL_XML);
        }

        $this->runCommand(
            "php install.php  -- ",
            [
                "license_agreement_accepted" => "yes",
                "locale" => "en_US",
                "timezone" => "America/Los_Angeles",
                "default_currency" => "USD",
                "db_name" => $dbName,
                "db_host" => $this->getEnv('mysql_host'),
                "db_user" => $this->getEnv('mysql_user'),
                "db_pass" => $this->getEnv('mysql_password'),
                "db_prefix" => self::TABLES_PREFIX,
                "url" => "http://magento.example.com/",
                "use_rewrites" => "yes",
                "use_secure" => "no",
                "secure_base_url" => "https://magento.example.com/",
                "use_secure_admin" => "no",
                "admin_firstname" => "Store",
                "admin_lastname" => "Owner",
                "admin_email" => "admin@example.com",
                "admin_username" => "admin",
                "admin_password" => "123123q",
                "skip_url_validation" => "yes"
            ]

        );
    }

    /**
     * Install Magento 2
     */
    private function reinstallM2()
    {
        $this->log('Install M2');

        chdir($this->getOption('m2_path'));
        $dbName = $this->getDbName('m2');
        $this->cleanDb($dbName);
        $this->runCommand(
            "php bin/magento setup:install",
            [
                "language" => "en_US",
                "timezone" => "America/Los_Angeles",
                "backend-frontname" => "backend",
                "currency" => "USD",
                "db-name" => $dbName,
                "db-host" => $this->getEnv('mysql_host'),
                "db-user" => $this->getEnv('mysql_user'),
                "db-password" => $this->getEnv('mysql_password'),
                "base-url" => "http://magento.example.com/",
                "use-rewrites" => 1,
                "admin-firstname" => "Store",
                "admin-lastname" => "Owner",
                "admin-email" => "admin@example.com",
                "admin-user" => "admin",
                "admin-password" => "123123q",
            ],
            '='
        );
    }

    /**
     * @param $config
     */
    private function updateMigrationConfig($config)
    {
        $path = getcwd() . "/etc/{$config}/config.xml.dist";
        if (!file_exists($path)) {
            $this->log("File not exists: " . $path);
        } else {
            $this->log(file_get_contents($path));
        }
        $xml = simplexml_load_file($path);
        $m1Db = $xml->source->database;
        $m1Db['name'] = $this->getDbName('m1');
        $m1Db['host'] = $this->getEnv('mysql_host');
        $m1Db["user"] = $this->getEnv('mysql_user');
        $m1Db["password"] = $this->getEnv('mysql_password');

        $xml->options->source_prefix = self::TABLES_PREFIX;

        $m2Db = $xml->destination->database;
        $m2Db['name'] = $this->getDbName('m2');
        $m2Db['host'] = $this->getEnv('mysql_host');
        $m2Db["user"] = $this->getEnv('mysql_user');
        $m2Db["password"] = $this->getEnv('mysql_password');
        $xml->saveXML('etc/config.xml');
        $this->log('Created Config File: ' . $xml->asXML());
    }

    /**
     * Clean DB
     *
     * @param $dbName
     * @throws Exception
     */
    private function cleanDb($dbName)
    {
        $this->runCommand(
            "mysql --password=" . $this->getEnv('mysql_password'),
            [
                'u' => $this->getEnv('mysql_user'),
                'h' => $this->getEnv('mysql_host'),
                'e' => "drop database if exists {$dbName}; create database {$dbName}"
            ]
        );
    }
}
