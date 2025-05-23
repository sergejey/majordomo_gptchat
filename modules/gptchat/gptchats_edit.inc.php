<?php
/*
* @version 0.1 (wizard)
*/
if ($this->owner->name == 'panel') {
    $out['CONTROLPANEL'] = 1;
}
$table_name = 'gptchats';
$rec = SQLSelectOne("SELECT * FROM $table_name WHERE ID='" . (int)$id . "'");

if ($this->tab == 'history' && $rec['ID']) {
    if ($this->mode == 'run') {
        global $file;
        if ($file != '' && file_exists($file)) {
            $this->activateChat($rec['ID'], 0, $file);
        } else {
            $this->activateChat($rec['ID']);
        }
        $this->redirect("?id=" . $rec['ID'] . "&view_mode=" . $this->view_mode . "&tab=" . $this->tab);
    }
    if ($this->mode == 'delete_history' && gr('history_id')) {
        SQLExec("DELETE FROM gptchats_history WHERE CHAT_ID=" . $rec['ID'] . " AND ID=" . gr('history_id', 'int'));
        $this->redirect("?id=" . $rec['ID'] . "&view_mode=" . $this->view_mode . "&tab=" . $this->tab);
    }
    if ($this->mode == 'clear') {
        SQLExec("DELETE FROM gptchats_history WHERE CHAT_ID=" . $rec['ID']);
        $this->redirect("?id=" . $rec['ID'] . "&view_mode=" . $this->view_mode . "&tab=" . $this->tab);
    }
    $history = SQLSelect("SELECT * FROM gptchats_history WHERE CHAT_ID=" . $rec['ID'] . " ORDER BY ADDED DESC");
    if (count($history) > 0) {
        $out['HISTORY'] = $history;
    }

}

if ($this->mode == 'update') {
    $ok = 1;

    if ($this->tab == '') {

        //updating '<%LANG_TITLE%>' (varchar, required)
        $rec['TITLE'] = gr('title');
        if ($rec['TITLE'] == '') {
            $out['ERR_TITLE'] = 1;
            $ok = 0;
        }
        //updating 'MODEL' (varchar)
        $rec['MODEL'] = gr('model');
        if (!$rec['MODEL']) {
            $out['ERR_MODEL'] = 1;
            $ok = 0;
        }

        //updating '<%LANG_LINKED_OBJECT%>' (varchar)
        $rec['LINKED_OBJECT'] = gr('linked_object');
        //updating '<%LANG_LINKED_PROPERTY%>' (varchar)
        $rec['LINKED_PROPERTY'] = gr('linked_property');


    }

    if ($this->tab == 'prompt') {
        //updating 'TEMPERATURE' (varchar)
        $rec['TEMPERATURE'] = gr('temperature', 'float');
        //updating 'INSTRUCTIONS' (varchar)
        $rec['INSTRUCTIONS'] = gr('instructions');
        if (!$rec['INSTRUCTIONS']) {
            $out['ERR_INSTRUCTIONS'] = 1;
            $ok = 0;
        }
        //updating 'PROMPT' (varchar)
        $rec['PROMPT'] = gr('prompt');
        if (!$rec['PROMPT']) {
            $out['ERR_PROMPT'] = 1;
            $ok = 0;
        }
        //updating 'USE_PRESCRIPT' (varchar)
        $rec['USE_PRESCRIPT'] = gr('use_prescript', 'int');
        //updating 'PRESCRIPT' (varchar)
        $rec['PRESCRIPT'] = gr('prescript');
        if ($rec['PRESCRIPT'] != '' && $rec['USE_PRESCRIPT']) {
            $errors = php_syntax_error($rec['PRESCRIPT']);
            if ($errors) {
                $out['PRESCRIPT_ERRORS'] = $errors;
                $out['ERR_PRESCRIPT'] = 1;
                $ok = 0;
            }
        }
    }

    if ($this->tab == 'result') {
        $rec['USE_POSTSCRIPT'] = gr('use_postscript');
        //updating 'POSTSCRIPT' (varchar)
        $rec['POSTSCRIPT'] = gr('postscript');
        if ($rec['POSTSCRIPT'] != '' && $rec['USE_POSTSCRIPT']) {
            $errors = php_syntax_error($rec['POSTSCRIPT']);
            if ($errors) {
                $out['POSTSCRIPT_ERRORS'] = $errors;
                $out['ERR_POSTSCRIPT'] = 1;
                $ok = 0;
            }
        }
        //updating 'SAY_RESULT' (varchar)
        $rec['SAY_RESULT'] = gr('say_result');
        //updating 'SAY_LEVEL' (varchar)
        $rec['SAY_LEVEL'] = gr('say_level', 'int');
        //updating 'SAY_TO' (varchar)
        $rec['SAY_TO'] = gr('say_to');
        $rec['SAY_USER_ID'] = gr('say_user_id', 'int');
    }

    //UPDATING RECORD
    if ($ok) {
        if ($rec['ID']) {
            SQLUpdate($table_name, $rec); // update
        } else {
            $new_rec = 1;
            $rec['ID'] = SQLInsert($table_name, $rec); // adding new record
        }

        if ($rec['LINKED_OBJECT'] != '' && $rec['LINKED_PROPERTY'] != '') {
            addLinkedProperty($rec['LINKED_OBJECT'], $rec['LINKED_PROPERTY'], $this->name);
        }

        $out['OK'] = 1;
    } else {
        $out['ERR'] = 1;
    }
}

if ($this->config['YANDEX_CATALOG_ID'] != '' && $this->config['YANDEX_OAUTH']) {
    $out['CAN_YANDEX'] = 1;
}

if ($this->config['CUSTOM_GPT_URL'] != '') {
    $out['CUSTOM_GPT_URL'] = $this->config['CUSTOM_GPT_URL'];
}

$gpt_models = array();
if ($this->config['OPENAI_API_KEY']) {
    $out['CAN_OPENAI'] = 1;
    if ($this->tab == '') {

        $cache_filename = ROOT . 'cms/cached/openai_models.txt';
        if (file_exists($cache_filename) && (time() - filemtime($cache_filename)) < 24 * 60 * 60) {
            $result = LoadFile($cache_filename);
        } else {
            $url = 'https://api.openai.com/v1/models';
            $headers = array(
                'Content-Type: application/json',
                "Authorization: Bearer " . $this->config['OPENAI_API_KEY']
            );
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            curl_close($ch);
            if ($result != '') {
                SaveFile($cache_filename, $result);
            }
        }


        if ($result != '') {
            $data = json_decode($result, true);
            if (isset($data['data'])) {
                $models = $data['data'];
                foreach ($models as $model) {
                    if (preg_match('/^gpt/', $model['id'])) {
                        $gpt_models[] = array('id' => $model['id'], 'title' => 'OpenAI ' . $model['id']);
                    }
                }
            }
        }
    }
}

$out['GPT_MODELS'] = $gpt_models;

if ($this->tab == 'result') {
    $out['TERMINALS'] = getTerminalsByCANTTS();
    $out['USERS'] = SQLSelect("SELECT ID, NAME FROM users ORDER BY NAME");
}

if (is_array($rec)) {
    foreach ($rec as $k => $v) {
        if (!is_array($v)) {
            $rec[$k] = htmlspecialchars($v);
        }
    }
}
outHash($rec, $out);
