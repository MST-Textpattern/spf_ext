spf_ext
=======

an external file editor plugin for [Textpattern][].

Create, edit and delete external (to Textpattern) files in Textpattern
admin.

REQUIRES: Texpattern 4.4.1 and PHP 5 and shell access (for sym-linking
existing files).

**Please read the instructions and notes below before use.**

This plugin creates external files you can manage and edit via the admin
interface. All files are saved to the directory you create in step 1 of
the instructions below. Syntax highlighting is available via the
[Codemirror admin theme][] (currently HTML, PHP and JavaScript - more to
come).


[DOWNLOAD][]



* * * * *

  

### Why?

-   This plugin allows you to edit and manage site files residing
    outside Textpattern within the Textpattern admin area (see
    ‘Procedure for managing existing files’ below).
-   It offers an easy way to create static files you can edit and access
    via your page templates: `<txp:site_url />ext_files/yourfile.html`

  

* * * * *

  

### Instructions:

1.  Create a directory for the static external files in the root of your
    textpattern installation called ‘ext\_files’ (or whatever you like).
    You should make sure that PHP is able to write to that directory.
2.  Visit the advanced preferences and make sure the “External files
    directory” preference contains the directory you created in step 1
    (by default ‘ext\_files’). This path is relative path to the
    directory of your root Textpattern installation.
3.  Activate this plugin.
4.  Go to Extensions \> External Files and create files you’d like to
    manage.
5.  Always include a file extension in the file name (.html, .php,
    etc.).

  

* * * * *

  

### Procedure for managing existing files:

1.  Copy the contents of the file (called for example myfile.php) into a
    new file in Extensions \> External Files \> Create new file.
2.  Name the new file (External file name:) ‘myfile.php’.
3.  Rename the original file (to, for example, ‘myfile\_original.php’) -
    then you’ll have a backup.
4.  Make a symbolic link, in the original file location, to the new file
    (e.g. cd to the ‘ext\_files’ directory and then ‘ln -s
    /original\_directory\_path/myfile.php ./myfile.php’).

  

* * * * *

  

### Notes:

1.  You can only manage files which are read/write to your web server
    (i.e. within your web root) - e.g. Minify config files, external
    scripts, etc.
2.  Deleting a file via the External Files tab deletes the actual file in the ‘ext\_files’ directory
    (as well as the database entry) - use with care!.


  

* * * * *

  

### Languages (Textpack)

This plugin installs an English Textpack by default.

To install a Textpack for your own language see the [instructions on
GitHub][].

  

* * * * *

  

### Version history

0.1 - April 2012 - first release.

  [Textpattern]: http://www.textpattern.com/
  [DOWNLOAD]: https://raw.github.com/spiffin/spf_ext/master/spf_ext_v0.1.txt
  [Codemirror admin theme]: https://github.com/spiffin/CodeMirrorTextpattern
  [instructions on GitHub]: https://github.com/spiffin/spf_ext/blob/master/spf_ext_textpack.txt