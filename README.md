# A2A Laravel

## Description

A2A Laravel is a package that provides a simple and efficient way to integrate the A2A API into your Laravel application. It allows you to easily make requests to the A2A API and handle responses in a structured manner.

## Installation

You can install the package via composer:

```bash
composer require dwoodard/a2a-laravel
```

```bash
// add config/a2a.php
php artisan vendor:publish --tag=a2a
```

## Configuration

After installing the package, you need to publish the configuration file. You can do this by running the following command:

```bash
php artisan vendor:publish --provider="Dwoodard\A2aLaravel\A2aServiceProvider"
```
