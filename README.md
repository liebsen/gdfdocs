# puntoweberplast Weber Webapp [website](https://puntoweberplast.com)

## Server Requirements

You'll need 

PHP >= 7 [PHP](http://php.net/downloads.php)
MySQL >= 5.5 [MySQL](https://dev.mysql.com/downloads/)

## Installation

Install [composer](https://getcomposer.org/) or type

``` bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

Install PHP dependencies

``` bash
cd /var/www/my-project
composer install
```

Set folder permissions

``` bash
sudo chmod -R 777 logs
sudo chmod -R 777 public/dist
```

Set your environment up

``` bash
cat .env.dist > .env
nano .env
```

Create dababase and run

``` bash
cd bin
php db migrate
```

Edit your settings as you please.
Now, your project must be up and running.

## Deploying

### Before you commit to bitbucket.com run dist script.

``` bash
php dist
```

## Documentation

### Before you commit to bitbucket.com run dist script.

``` bash
php docs
```