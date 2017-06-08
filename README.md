# redvolver-acfcleaner
Wordpress Plugin for delete unused modified ACF fields.

### READ THIS FIRST

The version is untested and may delete break things.
I recommend backing up your database before using.

### Installing

```
Clone in wp-plugin
git clone https://github.com/filippozanardo/redvolver-acfcleaner.git redvolver-acfcleaner
```
or download zip and upload in wp-plugin directory

### Documentation

On activation go to Settings->Clean ACF Settings and save your preferences:

- Clean On Post save : on acf/save_post check unused changed meta value and delete unused
- Clean all ACF Post Meta Before Save : delete all post meta value for the post before af save its field

This plugin for now work only on post and custom post type not for taxonomy etc etc

Feel free to post comment or pull request.

## TODO

* add tools to batch processing acf
* clean taxonomy and other type
* better documentation
* bug fix and test

## Licence

Copyright © 2017 Redvolver srl
