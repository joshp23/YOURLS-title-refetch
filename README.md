# YOURLS-title-refetch
A simple plugin to refetch poorly defined titles in YOURLS

### The Problem
Sometimes YOURLS does not fetch the title of a url that is being processed, this happens frequently when using the api with certain 3rd party applications (which need attention from their developers). When this occures YOURLS uses the url itself as the title in the databse, which is less than desireable.

### The solution
This plugin adds a new `key->value` pair to `action=shorturl`, and if it is present will check the title of the new link and attempt to repair it if it is malformed.

### Installation
Clone this repo into the `YOURLS/user/plugins/` directory and enable it in the admin interface.

### Usage
1.  Send the `refetch=true` along with a typical API shorturl call. Ex:
```
https://eg.com/yourls-api.php?refetch=true&action=shorturl&url=https://some.really.long.url.com/it_never/ends/index.php
```
2.  This plugin also creates a new action:
-  `action=refetch` which will trigger a refetch check according to one of the following required conditions:
   -  `target=title` which will check a single link and requires`shorturl=EX`. The short url can be a keyword or a full short url.
   -  `target=all` which will run a check on the entire database and requires a valid log in (eg. signature or user/pass)
3.  There is also a simple admin interface page where a database refetch check can be performed. A simple button in the admin section to update a single link is on the way.

#### The catch
While this doesn't cause the developers of third party applications to give attetnion to their code, it does make integration more flexible and provides an immediate working solution on YOURLS's end, akin to the [api-concurrence-fix](https://bitbucket.org/laceous/yourls-concurrency-fix) plugin. 

#### Note:
This module was initially developed to assist with integration of the [Drupal: Shorten URLs](https://www.drupal.org/project/shorten) module. An issue regarding this can be found [here](https://www.drupal.org/node/2889342).
