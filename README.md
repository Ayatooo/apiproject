# Api Project ๐งช

![SYMFONY](https://img.shields.io/badge/Framework-Symfony-purple)

![PHP](https://img.shields.io/badge/Langage-Php-blue)

This project is an api based on restaurants.
We learned the good practices to create a full rest API in symfony.

## Environnement ๐ฉ

Create a file .env.local and fill it with the data from .env
Replace the informations inside with your owns


## Installation ๐

Clone the repository
Move inside it
Run the following commands :

```bash
composer install
```

Create a directory called 'jwt' inside the config one, then run the command :
```bash
php bin/console lexik:jwt:generate-keypair
```

```bash
php bin/console d:d:c
php bin/console d:s:u --force
php bin/console d:f:l
symfony serve
```

## Authors ๐ป

- rs_06#8394
- Ayato__#0069
