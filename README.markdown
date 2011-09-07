These are a collection of scripts for managing mainly WordPress install.

Scripts
=======
*   wp-utf8ize.php

    Convert all your database character sets to utf8, trying to follow [Codex guides](http://codex.wordpress.org/Converting_Database_Character_Sets). You should use this if you are experiencing double utf8 encoding. You can check this by setting `DB_CHARSET` in your `wp-config.php` file to `latin1` or commenting the line; if your characters look good now on your site than you are probably suffering from this issue.

    It works by scanning all you tables and columns and generating a list of SQL statements which allow you to convert to convert your content to uft8.

    Run this in the command line like this `./wp-utf8ize.php > utf8ize.sql`.
 

Copyright
=========

The software in this repository is free for both perosnal and comercial use. You are free to modify and distribute it but please add some credits if you do so.
