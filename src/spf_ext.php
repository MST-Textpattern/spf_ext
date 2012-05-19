<?php

/**
 * spf_ext - External file editor for Textpattern
 *
 * © 2012 Simon Finch - https://github.com/spiffin/spf_ext
 *
 * Licensed under GNU General Public License version 2
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Version 0.1 -- 28 April 2012
 */

/**
 * Setup tabs and callback
 */

if (@txpinterface == 'admin') {

    add_privs('spf_ext', '1');
    register_tab('extensions', 'spf_ext', gTxt('spf_ext_files'));
    register_callback('spf_ext_gui', 'spf_ext');
    register_callback('spf_ext_install', 'plugin_lifecycle.spf_ext');

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
 * Installer function
 */

function spf_ext_install($event='', $step='') {

    global $prefs;

    if($step == 'deleted') {

        safe_delete(
            'txp_prefs',
            "name='spf_ext_dir'"
        );

        // Don't drop the table - just in case..
        //@safe_query(
        //    'DROP TABLE IF EXISTS '.safe_pfx('spf_ext')
        //);

        // delete the Textpack

        safe_delete(
            'txp_lang',
            "event = 'spf_ext'"
        );

        return;

    }

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

    $out[] = startTable('list', 'left');

    $rs = safe_rows_start('filename', 'spf_ext', "1=1");

    if ($rs) {

        while ($a = nextRow($rs)) {
            extract($a);
            $edit = ($current != $filename) ? eLink('spf_ext', '', 'filename', $filename, $filename) : htmlspecialchars($filename);
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

        $buttons = '<div class="edit-title">'.gTxt('spf_edit_script').sp.strong(htmlspecialchars($filename)).'</div>';
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
    graf(sLink('spf_ext', 'pour', gTxt('spf_create_new_file')), ' class="action-create smallerbox"').
    spf_ext_list($filename).
    '</div>';

    echo
    '<div id="'.$event.'_container" class="txp-container txp-edit">'.
    startTable('edit').
    tr(
        td(
            form(
                '<div id="main_content">'.
                $buttons.
                '<textarea id="spf_ext" class="code" name="spf_ext" cols="78" rows="32" style="margin-top: 6px; width: 700px; height: 515px;">'.htmlspecialchars($filecontent).'</textarea>'.br.
                fInput('submit','',gTxt('save'),'publish').
                eInput('spf_ext').sInput('spf_ext_save').
                hInput('filename',$filename)
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
    $file = $prefs['path_to_site'].'/'.$prefs['spf_ext_dir'].'/'.$filename;

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

?>