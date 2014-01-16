# LiipRegistryAdaptor

This library is an extension to the [LiipDrupalRegistryModule](https://github.com/liip/LiipDrupalRegistryModule).
It introduces a number of adaptors to be able to integrate the registry with a number of external services; like Elasticsearch.

## Current Project Status

[![Build Status](https://travis-ci.org/liip/LiipRegistryAdaptor.png?branch=master)](https://travis-ci.org/liip/LiipRegistryAdaptor)
[![Dependency Status](https://www.versioneye.com/user/projects/52526e85632bac22fc000075/badge.png)](https://www.versioneye.com/user/projects/52526e85632bac22fc000075)

## Installation
The source is now PSR-0 compatible. There is no specific installation routine to be followed. Just clone or checkout the source into to your project
and use it.
In case you don't use a [PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md) compatible autoloader, you only have to add the `bootstrap.php` into your bootstrap or
autoloader.

### Composer
Add the following lines to your `composer.json` file and update your project's composer installation.

```json
{
    "require": {
       "liip/registryadaptor": "dev-master"
    }
}
```

This composer configuration will checkout the 'cutting edge' version ('dev-master') of the project. Be warned that this might be broken sometimes.


**NOTE:**
In case you do not know what this means the [composer project website](http://getcomposer.org) is a good place to start.


### Github
Thus I recommend the composer way to make LiipDrupalRegistryModule a dependency to your project.
The sources are also available via github. Just clone it as you might be familiar with.

```bash
$ git clone git@github.com:liip/LiipRegistryAdaptor.git
```

## Dependencies

- [Assert](http://github.com/beberlei/assert)
- [ProxyObject](git@github.com:lapistano/proxy-object.git) (optional)

### Optional

- [Elastica](https://github.com/ruflin/elastica)
