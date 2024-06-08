<?php
/**
 * GPT chat
 * @package project
 * @author Wizard <sergejey@gmail.com>
 * @copyright http://majordomo.smartliving.ru/ (c)
 * @version 0.1 (wizard, 10:06:19 [Jun 08, 2024])
 */
//
//
class gptchat extends module
{
    /**
     * gptchat
     *
     * Module class constructor
     *
     * @access private
     */
    function __construct()
    {
        $this->name = "gptchat";
        $this->title = "GPT chat";
        $this->module_category = "<#LANG_SECTION_APPLICATIONS#>";
        $this->checkInstalled();
    }

    /**
     * saveParams
     *
     * Saving module parameters
     *
     * @access public
     */
    function saveParams($data = 1)
    {
        $p = array();
        if (isset($this->id)) {
            $p["id"] = $this->id;
        }
        if (isset($this->view_mode)) {
            $p["view_mode"] = $this->view_mode;
        }
        if (isset($this->edit_mode)) {
            $p["edit_mode"] = $this->edit_mode;
        }
        if (isset($this->tab)) {
            $p["tab"] = $this->tab;
        }
        return parent::saveParams($p);
    }

    /**
     * getParams
     *
     * Getting module parameters from query string
     *
     * @access public
     */
    function getParams()
    {
        global $id;
        global $mode;
        global $view_mode;
        global $edit_mode;
        global $tab;
        if (isset($id)) {
            $this->id = $id;
        }
        if (isset($mode)) {
            $this->mode = $mode;
        }
        if (isset($view_mode)) {
            $this->view_mode = $view_mode;
        }
        if (isset($edit_mode)) {
            $this->edit_mode = $edit_mode;
        }
        if (isset($tab)) {
            $this->tab = $tab;
        }
    }

    /**
     * Run
     *
     * Description
     *
     * @access public
     */
    function run()
    {
        global $session;
        $out = array();
        if ($this->action == 'admin') {
            $this->admin($out);
        } else {
            $this->usual($out);
        }
        if (isset($this->owner->action)) {
            $out['PARENT_ACTION'] = $this->owner->action;
        }
        if (isset($this->owner->name)) {
            $out['PARENT_NAME'] = $this->owner->name;
        }
        $out['VIEW_MODE'] = $this->view_mode;
        $out['EDIT_MODE'] = $this->edit_mode;
        $out['MODE'] = $this->mode;
        $out['ACTION'] = $this->action;
        $out['TAB'] = $this->tab;
        $this->data = $out;
        $p = new parser(DIR_TEMPLATES . $this->name . "/" . $this->name . ".html", $this->data, $this);
        $this->result = $p->result;
    }

    /**
     * BackEnd
     *
     * Module backend
     *
     * @access public
     */
    function admin(&$out)
    {
        $this->getConfig();
        $out['YANDEX_CATALOG_ID'] = $this->config['YANDEX_CATALOG_ID'];
        $out['YANDEX_OAUTH'] = $this->config['YANDEX_OAUTH'];
        $out['OPENAI_API_KEY'] = $this->config['OPENAI_API_KEY'];
        if ($this->view_mode == 'update_settings') {
            $this->config['YANDEX_OAUTH'] = gr('yandex_oauth');
            $this->config['YANDEX_CATALOG_ID'] = gr('yandex_catalog_id');
            $this->config['OPENAI_API_KEY'] = gr('openai_api_key');
            $this->saveConfig();
            $this->redirect("?");
        }
        if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
            $out['SET_DATASOURCE'] = 1;
        }
        if ($this->data_source == 'gptchats' || $this->data_source == '') {
            if ($this->view_mode == '' || $this->view_mode == 'search_gptchats') {
                $this->search_gptchats($out);
            }
            if ($this->view_mode == 'edit_gptchats') {
                $this->edit_gptchats($out, $this->id);
            }
            if ($this->view_mode == 'delete_gptchats') {
                $this->delete_gptchats($this->id);
                $this->redirect("?");
            }
        }
    }

    /**
     * FrontEnd
     *
     * Module frontend
     *
     * @access public
     */
    function usual(&$out)
    {
        $this->admin($out);
    }

    /**
     * gptchats search
     *
     * @access public
     */
    function search_gptchats(&$out)
    {
        require(dirname(__FILE__) . '/gptchats_search.inc.php');
    }

    /**
     * gptchats edit/add
     *
     * @access public
     */
    function edit_gptchats(&$out, $id)
    {
        require(dirname(__FILE__) . '/gptchats_edit.inc.php');
    }

    /**
     * gptchats delete record
     *
     * @access public
     */
    function delete_gptchats($id)
    {
        $rec = SQLSelectOne("SELECT * FROM gptchats WHERE ID='$id'");
        // some action for related tables
        SQLExec("DELETE FROM gptchats_history WHERE CHAT_ID='" . $rec['ID'] . "'");
        SQLExec("DELETE FROM gptchats WHERE ID='" . $rec['ID'] . "'");
    }

    function propertySetHandle($object, $property, $value)
    {
        $this->getConfig();
        $table = 'gptchats';
        $properties = SQLSelect("SELECT ID FROM $table WHERE LINKED_OBJECT LIKE '" . DBSafe($object) . "' AND LINKED_PROPERTY LIKE '" . DBSafe($property) . "'");
        $total = count($properties);
        if ($total) {
            for ($i = 0; $i < $total; $i++) {
                $this->activateChat($property[$i]['ID'], array('VALUE' => $value));
            }
        }
    }

    function activateChat($chat_id, $params = 0)
    {
        $chat = SQLSelectOne("SELECT * FROM gptchats WHERE ID=" . (int)$chat_id);
        if (!$chat['ID']) return 0;

        $out = array();

        $sys_params = 'Model: ' . $chat['MODEL'] . '; ';
        if ($chat['TEMPERATURE']) {
            $sys_params .= ' Temperature: ' . $chat['TEMPERATURE'] . '; ';
        }

        if ($chat['USE_PRESCRIPT'] && $chat['PRESCRIPT']) {
            eval($chat['PRESCRIPT']);
        }

        if (is_array($params)) {
            foreach ($params as $k => $v) {
                if (is_array($v)) $v = json_encode($v);
                $out[$k] = $v;
                $sys_params .= ' ' . $k . ': ' . $v . '; ';
            }
        }

        foreach ($out as $k => $v) {
            $chat['INSTRUCTIONS'] = str_replace('%' . $k . '%', $v, $chat['INSTRUCTIONS']);
            $chat['PROMPT'] = str_replace('%' . $k . '%', $v, $chat['PROMPT']);
        }

        $instructions = processTitle($chat['INSTRUCTIONS']);
        $prompt = processTitle($chat['PROMPT']);

        $answer = '';
        if (preg_match('/yandex/is', $chat['MODEL'])) {
            $answer = $this->yandexgpt($chat['MODEL'], $instructions, $prompt, (float)$chat['TEMPERATURE']);
        } else {
            $answer = $this->openaigpt($chat['MODEL'], $instructions, $prompt, (float)$chat['TEMPERATURE']);
        }

        $res = array();
        $res['CHAT_ID'] = $chat['ID'];
        $res['PARAMS'] = trim($sys_params);
        $res['INSTRUCTIONS'] = $instructions;
        $res['PROMPT'] = $prompt;
        $res['ANSWER'] = $answer;
        $res['ADDED'] = date('Y-m-d H:i:s');
        SQLInsert('gptchats_history', $res);

        if ($answer != '') {
            if ($chat['USE_POSTSCRIPT'] && $chat['POSTSCRIPT']) {
                eval($chat['POSTSCRIPT']);
            }
            if ($chat['SAY_RESULT']) {
                if ($chat['SAY_TO']) {
                    sayTo($answer, $chat['SAY_LEVEL'], $chat['SAY_TO']);
                } else {
                    say($answer, $chat['SAY_LEVEL'], $chat['SAY_USER_ID'], 'gptchat');
                }
            }
        }

    }

    function openaigpt($model, $instructions, $prompt, $temperature = 0) {
        $this->getConfig();
        if (!$this->config['OPENAI_API_KEY']) return false;

        $answer = '';

        $url = 'https://api.openai.com/v1/chat/completions';
        $headers = array(
            'Content-Type: application/json',
            "Authorization: Bearer " . $this->config['OPENAI_API_KEY']
        );

        $request = array(
          'model'=>$model,
          'temperature'=>$temperature,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => $instructions
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            )
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
        $result = curl_exec($ch);
        curl_close($ch);

        if ($result != '') {
            $data = json_decode($result, true);
            if (isset($data['choices'][0]['message']['content'])) {
                $answer = $data['choices'][0]['message']['content'];
            } else {
                DebMes('Cannot answer message: ' . $result, 'gptchat');
            }
        }

        return $answer;

    }

    function yandexgpt($model, $instructions, $prompt, $temperature = 0)
    {
        $this->getConfig();
        if (!$this->config['YANDEX_OAUTH'] || !$this->config['YANDEX_CATALOG_ID']) return false;

        $answer = '';

        // authorize to get IAM token
        $url = 'https://iam.api.cloud.yandex.net/iam/v1/tokens';
        $data = array('yandexPassportOauthToken' => $this->config['YANDEX_OAUTH']);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $result = curl_exec($ch);
        curl_close($ch);

        $iamToken = '';
        if ($result != '') {
            $data = json_decode($result, true);
            if (isset($data['iamToken'])) {
                $iamToken = $data['iamToken'];
            }
        }

        if ($iamToken == '') {
            DebMes('Cannot get iamToken: ' . $result, 'gptchat');
            return false;
        }

        // make request to GPT
        $modelUri = 'gpt://' . $this->config['YANDEX_CATALOG_ID'] . '/';
        if ($model == 'yandexgpt' || $model == 'yandexgpt-lite') {
            $modelUri .= $model . '/latest';
        } elseif ($model == 'yandex_summarization') {
            $modelUri .= 'summarization/latest';
        } elseif ($model == 'yandexgpt-lite-rc') {
            $modelUri .= 'yandexgpt-lite/rc';
        }

        $url = 'https://llm.api.cloud.yandex.net/foundationModels/v1/completion';
        $headers = array(
            'Content-Type: application/json',
            "Authorization: Bearer " . $iamToken,
            'x-folder-id: ' . $this->config['YANDEX_CATALOG_ID']
        );

        $request = array(
            'modelUri' => $modelUri,
            'completionOptions' => array(
                'stream' => false,
                'temperature' => $temperature,
                'maxTokens' => 1000
            ),
            'messages' => array(
                array(
                    'role' => 'system',
                    'text' => $instructions
                ),
                array(
                    'role' => 'user',
                    'text' => $prompt
                )
            )
        );


        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
        $result = curl_exec($ch);
        curl_close($ch);

        if ($result != '') {
            $data = json_decode($result, true);
            if (isset($data['result']['alternatives'][0]['message']['text'])) {
                $answer = $data['result']['alternatives'][0]['message']['text'];
            } else {
                DebMes('Cannot answer message: ' . $result, 'gptchat');
            }
        }
        return $answer;
    }

    function api($params)
    {
        if (isset($params['id'])) {
            $this->activateChat($params['id'], $params);
        }
    }

    /**
     * Install
     *
     * Module installation routine
     *
     * @access private
     */
    function install($data = '')
    {
        parent::install();
    }

    /**
     * Uninstall
     *
     * Module uninstall routine
     *
     * @access public
     */
    function uninstall()
    {
        SQLExec('DROP TABLE IF EXISTS gptchats');
        parent::uninstall();
    }

    /**
     * dbInstall
     *
     * Database installation routine
     *
     * @access private
     */
    function dbInstall($data)
    {
        /*
        gptchats -
        */
        $data = <<<EOD
 gptchats: ID int(10) unsigned NOT NULL auto_increment
 gptchats: TITLE varchar(100) NOT NULL DEFAULT ''
 gptchats: MODEL varchar(255) NOT NULL DEFAULT ''
 gptchats: TEMPERATURE float NOT NULL DEFAULT '0'
 gptchats: INSTRUCTIONS text
 gptchats: PROMPT text
 gptchats: USE_PRESCRIPT int(3) NOT NULL DEFAULT '0'
 gptchats: PRESCRIPT varchar(255) NOT NULL DEFAULT ''
 gptchats: USE_POSTSCRIPT int(3) NOT NULL DEFAULT '0'
 gptchats: POSTSCRIPT varchar(255) NOT NULL DEFAULT ''
 gptchats: SAY_RESULT int(3) NOT NULL DEFAULT '0'
 gptchats: SAY_USER_ID int(10) NOT NULL DEFAULT '0' 
 gptchats: SAY_LEVEL int(3) NOT NULL DEFAULT '0'
 gptchats: SAY_TO varchar(255) NOT NULL DEFAULT ''
 gptchats: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 gptchats: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
 gptchats: LINKED_CONDITION int(3) NOT NULL DEFAULT '0'
 gptchats: LINKED_CONDITION_VALUE varchar(255) NOT NULL DEFAULT ''
 
 gptchats_history: ID int(10) unsigned NOT NULL auto_increment
 gptchats_history: CHAT_ID int(10) unsigned NOT NULL DEFAULT '0'
 gptchats_history: PARAMS varchar(255) NOT NULL DEFAULT ''
 gptchats_history: INSTRUCTIONS text
 gptchats_history: PROMPT text
 gptchats_history: ANSWER text
 gptchats_history: ADDED datetime
 
EOD;
        parent::dbInstall($data);
    }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgSnVuIDA4LCAyMDI0IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
