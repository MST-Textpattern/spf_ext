<?php

// This is a PLUGIN TEMPLATE.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'spf_ext';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version'] = '0.31';
$plugin['author'] = 'Simon Finch';
$plugin['author_uri'] = 'https://github.com/spiffin/spf_ext';
$plugin['description'] = 'External file editor';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '5';

// Plugin 'type' defines where the plugin is loaded
// 0 = public       : only on the public side of the website (default)
// 1 = public+admin : on both the public and admin side
// 2 = library      : only when include_plugin() or require_plugin() is called
// 3 = admin        : only on the admin side
$plugin['type'] = '3';

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '2';

// Plugin 'textpack' - provides i18n strings to be used in conjunction with gTxt().
$plugin['textpack'] = <<< EOT
#@spf_ext
spf_ext_files => External Files
spf_ext_dir => External files directory
spf_filename => Filename
spf_edit_file => You are editing file
spf_all_files => All Files
spf_file_updated => File <strong>{name}</strong> updated.
spf_file_name => External file name
spf_copy_file => &#8230;or copy file as
spf_create_new_file => Create new file
spf_file_created => File <strong>{name}</strong> created.
spf_file_name_required => Please provide a name for your file.
spf_file_exists => File <strong>{name}</strong> already exists.
spf_file_deleted => File <strong>{name}</strong> deleted.
EOT;


if (!defined('txpinterface'))
        @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
/**
 * spf_ext - External file editor for Textpattern
 *
 * © 2012 Simon Finch - https://github.com/spiffin/spf_ext
 *
 * Licensed under GNU General Public License version 2
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Version 0.31 -- 7 November 2012
 */

/**
 * Setup tabs and callback
 */

if (@txpinterface == 'admin') {

    add_privs('spf_ext', '1');
    register_tab('extensions', 'spf_ext', gTxt('spf_ext_files'));
    register_callback('spf_ext_gui', 'spf_ext');
    register_callback('spf_ext_install', 'plugin_lifecycle.spf_ext', 'installed');
    register_callback('spf_ext_remove', 'plugin_lifecycle.spf_ext', 'deleted');

}

/**
 * GUI functions / step dispatcher
 */

function spf_ext_gui() {

    global $event, $step;

    if ($event == 'spf_ext') {
        require_privs('spf_ext');

        bouncer($step,
            array(
                'spf_ext_edit_raw'   => false,
                'pour'              => false,
                'spf_ext_save'       => true,
                'spf_ext_copy'       => true,
                'spf_ext_delete'     => true,
                'spf_ext_edit'       => false,
            )
        );

        switch ($step) {
            case '': spf_ext_edit();                         break;
            case 'spf_ext_edit_raw': spf_ext_edit();          break;
            case 'pour': spf_ext_edit();                     break;
            case 'spf_ext_save': spf_ext_save();              break;
            case 'spf_ext_copy': spf_ext_copy();              break;
            case 'spf_ext_delete': spf_ext_delete();          break;
            case 'spf_ext_edit': spf_ext_edit();
        }
    }
}

/**
 * Removal function
 */

function spf_ext_remove($event, $step) {
global $prefs, $step;

    if(isset($prefs['spf_ext_dir'])) {

        safe_delete(
            'txp_prefs',
            "name='spf_ext_dir'"
        );

        // Don't drop the table - just in case.
        //@safe_query(
        //    'DROP TABLE IF EXISTS '.safe_pfx('spf_ext')
        //);

        // delete the Textpack

        safe_delete(
            'txp_lang',
            "event = 'spf_ext'"
        );

    }

}

/**
 * Installer function
 */

function spf_ext_install($event, $step) {
global $prefs, $step;

    if(!isset($prefs['spf_ext_dir'])) {

        safe_query(
            "CREATE TABLE IF NOT EXISTS ".safe_pfx('spf_ext')." (
                filename varchar(255) NOT NULL default '',
                content longtext NOT NULL,
                PRIMARY KEY(filename)
            ) CHARSET=utf8"
        );

        if(!safe_count("spf_ext", "filename='holder.txt'")) {

            safe_insert("spf_ext", "filename='holder.txt', content='Holder text file...you need at least one file here.'");

        }

        safe_insert(
            'txp_prefs',
            "prefs_id=1,
            name='spf_ext_dir',
            val='ext_files',
            type=1,
            event='admin',
            html='text_input',
            position=22"
        );
    }
}

/**
 * List function
 */

function spf_ext_list($current) {

    //$out[] = startTable('list', 'left');

$out[] = startTable('', '', 'txp-list');

    $rs = safe_rows_start('filename', 'spf_ext', "1=1");

    if ($rs) {

        while ($a = nextRow($rs)) {
            extract($a);
            $edit = ($current != $filename) ? eLink('spf_ext', '', 'filename', $filename, $filename) : txpspecialchars($filename);
            $delete = ($filename) ? dLink('spf_ext', 'spf_ext_delete', 'filename', $filename) : '';
            $out[] = tr(td($edit).td($delete));
        }

        $out[] =  endTable();

        return join('', $out);

    }
}

/**
 * Edit function
 */

function spf_ext_edit($message='') {

    pagetop(gTxt('spf_ext_files'),$message);
    global $step, $prefs;
    spf_ext_edit_raw();

}

/**
 * Edit function (raw)
 */

function spf_ext_edit_raw() {

    global $event, $step;

    $first = safe_row('filename', 'spf_ext', "1=1");
    $first_file = $first['filename'];

    extract(gpsa(array('filename', 'newname', 'copy', 'savenew')));

    if ($step == 'spf_ext_delete' || empty($filename) && $step != 'pour' && !$savenew) {

        $filename = $first_file;

    }

    elseif (($copy || $savenew) && trim(preg_replace('/[<>&"\']/', '', $newname))) {

        $filename = $newname;

    }

    if (empty($filename)) {

        $buttons = '<div class="edit-title">'.
        gTxt('spf_file_name').': '
        .fInput('text','newname','','edit','','',20).
        hInput('savenew','savenew').
        '</div>';
        $filecontent = gps('spf_ext');

    } else {

        $buttons = '<div class="edit-title">'.gTxt('spf_edit_script').sp.strong(txpspecialchars($filename)).'</div>';
        $filecontent = fetch("content",'spf_ext','filename',$filename);

    }

    if (!empty($filename)) {

        $copy = '<span class="copy-as"><label for="copy-file">'.gTxt('spf_copy_file').'</label>'.sp.fInput('text', 'newname', '', 'edit', '', '', '', '', 'copy-file').sp.
            fInput('submit', 'copy', gTxt('copy'), 'smallerbox').'</span>';

    } else {

        $copy = '';

    }

    $right =
    '<div id="content_switcher">'.
    hed(gTxt('spf_all_files'),2).
    graf(sLink('spf_ext', 'pour', gTxt('spf_create_new_file')), ' class="action-create"').
    spf_ext_list($filename).
    '</div>';

    echo
    '<h1 class="txp-heading">'.gTxt('spf_ext_files').'</h1>'.
    '<div id="'.$event.'_container" class="txp-container">'.
    startTable('', '', 'txp-columntable').
    tr(
        td(
            form(
                '<div id="main_content">'.
                $buttons.
                '<textarea id="spf_ext" class="code" name="spf_ext" cols="'.INPUT_LARGE.'" rows="'.INPUT_REGULAR.'" style="height: 39.25em;">'.txpspecialchars($filecontent).'</textarea>'.
                '<p>'.fInput('submit','',gTxt('save'),'publish').
                eInput('spf_js').sInput('spf_js_save').
                hInput('filename',$filename).'</p>'
                .$copy.
                '</div>'
            , '', '', 'post', 'edit-form', '', 'style_form')
        , '', 'column').
        tdtl(
            $right
        , ' class="column"')
    ).
    endTable().
    '</div>';
}

/**
 * Copy function
 *
 */

function spf_ext_copy() {

    extract(gpsa(array('oldname', 'newname')));

    $content = doSlash(fetch('content', 'spf_ext', 'filename', $oldname));

    $rs = safe_insert('spf_ext', "content = '$content', filename = '".doSlash($newname)."'");

    spf_ext_edit(
        gTxt('spf_file_created', array('{name}' => $newname))
    );
}

/**
 * Save function
 */

function spf_ext_save() {

    extract(gpsa(array('filename','spf_ext','savenew','newname','copy')));
    $content = doSlash($spf_ext);

    if ($savenew or $copy) {

        $newname = doSlash(trim(preg_replace('/[<>&"\']/', '', gps('newname'))));

        if ($newname and safe_field('filename', 'spf_ext', "filename = '$newname'")) {

            $message = gTxt('spf_file_exists', array('{name}' => $newname));

            if ($savenew) {

                $_POST['newname'] = '';

            }

        } elseif ($newname) {

            safe_insert('spf_ext', "filename = '".$newname."', content = '$content'");

            $message = gTxt('spf_file_created', array('{name}' => $newname));

            spf_ext_write();

        } else {

            $message = array(gTxt('spf_file_name_required'), E_ERROR);

        }

        spf_ext_edit($message);

    } else {

        safe_update('spf_ext', "content = '$content'", "filename = '".doSlash($filename)."'");

        $message = gTxt('spf_file_updated', array('{name}' => $filename));

        spf_ext_write();

        spf_ext_edit($message);

    }

}

/**
 * Write function
 */

function spf_ext_write() {

    global $prefs;
    extract(gpsa(array('filename','spf_ext','savenew','newname','copy')));

    $filename = (ps('copy') or ps('savenew')) ? ps('newname') : ps('filename');
    $file = $prefs['path_to_site'].'/'.$prefs['spf_ext_dir'].'/'.sanitizeForFile($filename);

    if (empty($prefs['spf_ext_dir']) or !$filename) {

        return;

    } else {

    $content_raw = fetch("content", "spf_ext", 'filename', $filename); // Moved here to save newly-created files

        $handle = fopen($file, 'wb');
        fwrite($handle, $content_raw);
        fclose($handle);
        chmod($file, 0644);
    }

}

/**
 * Delete function
 */

function spf_ext_delete() {

    global $prefs;

    $filename = ps('filename');
    $file = $prefs['path_to_site'].'/'.$prefs['spf_ext_dir'].'/'.$filename;

        if (!empty($prefs['spf_ext_dir']) and $filename) {

            safe_delete('spf_ext', "filename = '".doSlash($filename)."'");

            @unlink($file);

        spf_ext_edit(

            gTxt('spf_file_deleted', array('{name}' => $filename))
        );

    } else {
        return;
    }

}
# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN HELP ---
<h1>spf_ext</h1>

<p>External file editor plugin. Create, edit and delete external (to Textpattern) files in Textpattern admin.</p>
<p><strong>REQUIRES: Texpattern 4.5.1 and PHP 5 and shell access (for sym-linking existing files).</strong></p>
<p><a href="https://github.com/spiffin/spf_ext/blob/master/spf_ext_v0.2.txt">Use this version for Textpattern 4.4.1 and below</a>.</p>
<p><strong>Please read the instructions and notes below before use.</strong></p>
<p>This plugin creates external files you can manage and edit via the admin interface. All files are saved to the directory you create in step 1 of the instructions below. Syntax highlighting is available via <a href="https://github.com/spiffin/spf_codemirror">spf_codemirror</a> (currently HTML, PHP and JavaScript - more to come).</p>

<h2>Why?</h2>
<ul>
<li>This plugin allows you to edit and manage site files residing outside Textpattern within the Textpattern admin area (see ‘Procedure for managing existing files’ below).</li>
<li>It offers an easy way to create static files you can edit and access via your page templates: <code>&lt;txp:site_url /&gt;ext_files/yourfile.html</code></li>
</ul>

<hr />

<h2>Instructions:</h2>

<ol>
<li>Create a directory for the static external files in the root of your textpattern installation called ‘ext_files’ (or whatever you like). You should make sure that PHP is able to write to that directory.</li>
<li>Visit the advanced preferences and make sure the “External files directory” preference contains the directory you created in step 1 (by default ‘ext_files’). This path is relative path to the directory of your root Textpattern installation.</li>
<li>Activate this plugin.</li>
<li>Go to Extensions &gt; External Files and create files you’d like to manage.</li>
<li>Always include a file extension in the file name (.html, .php, etc.).</li>
</ol>

<h2>Procedure for managing existing files:</h2>
<ol>
<li>Copy the contents of the file (called for example myfile.php) into a new file in Extensions &gt; External Files &gt; Create new file.</li>
<li>Name the new file (External file name:) ‘myfile.php’.</li>
<li>Rename the original file (to, for example, ‘myfile_original.php’) - then you’ll have a backup.</li>
<li>Make a symbolic link, in the original file location, to the new file (e.g. cd to the ‘ext_files’ directory and then ‘ln -s /original_directory_path/myfile.php ./myfile.php’).</li>
</ol>

<hr />

<h2>Notes:</h2>
<ol>
<li>You can only manage files which are read/write to your web server e.g. Minify config files, external scripts, etc.</li>
<li>Deleting a file via the External Files tab deletes the actual file in the ’ext_files’ directory (as well as the database entry) - use with care!.</li>
</ol>


<h2>Languages (Textpack)</h2>
<ul>
<li>This plugin installs an English Textpack by default.</li>
<li>To install a Textpack for your own language see the <a href="https://github.com/spiffin/spf_ext/blob/master/spf_ext_textpack.txt">instructions on GitHub</a>.</li>
</ul>

<h2>Version history</h2>
<ul>
<li>0.31 - 7 November 2012 - rewritten for Textpattern 4.5.x.</li>
<li>0.2 - 26 August 2012 - security enhancements (thanks Jukka).</li>
<li>0.1 - April 2012 - first release.</li>
</ul>
# --- END PLUGIN HELP ---
-->
<?php
}
?>