# YOURLS-title-refetch
A simple plugin to refetch poorly defined titles in YOURLS

### The Problem
Sometimes YOURLS does not fetch the title of a url that is being processed, this happens frequently when using the api with certain applications (looking at you, [Drupal: Shorten URLs](https://www.drupal.org/project/shorten)). When this occures YOURLS will use the url itself as the title in the databse, which is less than desireable.

### The solution
This plugin will check and refetch the title of any url if it begins with either `http://` or `https://` when visiting the url's stats page. Alternatively, there is an option to batch process the entire datbase at once from the admin menu.

### The catch
This doesn't fix the problem. It would be more beneficial to address the actual issue and have proper titles returned 100% of the time, or at least catch the fails when they happen.

### Installation
Drop this repo into the `YOURLS/user/plugins/` directory and enable it in the admin interface.
