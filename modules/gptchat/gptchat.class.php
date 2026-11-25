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
        $out['CUSTOM_GPT_URL'] = $this->config['CUSTOM_GPT_URL'];
        $out['CUSTOM_GPT_MODEL'] = $this->config['CUSTOM_GPT_MODEL'];
        $out['CUSTOM_GPT_KEY'] = $this->config['CUSTOM_GPT_KEY'];
        foreach (array('CLAUDE_API_KEY', 'GEMINI_API_KEY', 'DEEPSEEK_API_KEY', 'KIMI_API_KEY', 'QWEN_API_KEY') as $newKey) {
            $out[$newKey] = isset($this->config[$newKey]) ? $this->config[$newKey] : '';
        }
        if ($this->view_mode == 'update_settings' && $this->action == 'admin') {
            $this->config['YANDEX_OAUTH'] = gr('yandex_oauth');
            $this->config['YANDEX_CATALOG_ID'] = gr('yandex_catalog_id');
            $this->config['OPENAI_API_KEY'] = gr('openai_api_key');
            $this->config['CUSTOM_GPT_URL'] = gr('custom_gpt_url');
            $this->config['CUSTOM_GPT_MODEL'] = gr('custom_gpt_model');
            $this->config['CUSTOM_GPT_KEY'] = gr('custom_gpt_key');
            foreach (array('CLAUDE_API_KEY', 'GEMINI_API_KEY', 'DEEPSEEK_API_KEY', 'KIMI_API_KEY', 'QWEN_API_KEY') as $newKey) {
                $this->config[$newKey] = gr(strtolower($newKey));
            }
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
            if ($this->view_mode == 'delete_gptchats' && $this->action == 'admin') {
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
        $id = (int)$id;
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
                $image = '';
                $valueString = is_scalar($value) ? (string)$value : '';
                if ($valueString !== '' && strpos($valueString, '/') !== false) {
                    $safeName = basename($valueString);
                    if ($safeName !== '' && file_exists(ROOT . 'cms/images/' . $safeName)) {
                        $image = ROOT . 'cms/images/' . $safeName;
                    }
                }
                $this->activateChat($properties[$i]['ID'], array('VALUE' => $value), $image);

            }
        }
    }

    function activateChat($chat_id, $params = 0, $image = '')
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

        if ($image != '' && preg_match('/^(claude|gemini|deepseek|kimi|qwen)/i', $chat['MODEL'])) {
            DebMes('Image input is not supported for model ' . $chat['MODEL'] . ', ignoring image', 'gptchat');
        }

        if ($chat['MODEL'] == 'customgpt') {
            $answer = $this->customgpt($instructions, $prompt, (float)$chat['TEMPERATURE'], $image);
        } elseif (preg_match('/yandex/is', $chat['MODEL'])) {
            $answer = $this->yandexgpt($chat['MODEL'], $instructions, $prompt, (float)$chat['TEMPERATURE'], $image);
        } elseif (preg_match('/^claude/i', $chat['MODEL'])) {
            $answer = $this->claudegpt($chat['MODEL'], $instructions, $prompt, (float)$chat['TEMPERATURE']);
        } elseif (preg_match('/^gemini/i', $chat['MODEL'])) {
            $answer = $this->geminigpt($chat['MODEL'], $instructions, $prompt, (float)$chat['TEMPERATURE']);
        } elseif (preg_match('/^(deepseek|kimi|qwen)/i', $chat['MODEL'], $providerMatch)) {
            $answer = $this->openaiCompatible(strtolower($providerMatch[1]), $chat['MODEL'], $instructions, $prompt, (float)$chat['TEMPERATURE']);
        } else {
            $answer = $this->openaigpt($chat['MODEL'], $instructions, $prompt, (float)$chat['TEMPERATURE'], $image);
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

    function prepareImage($image_file, $new_width = 1024, $new_height = 1024)
    {
        $size = getimagesize($image_file);
        $content = '';
        if ($size) {
            $image_width = $size[0];
            $image_height = $size[1];
            $image_format = $size[2];

            if ($image_format == 1) {
                $old_image = imagecreatefromgif($image_file);
            } elseif ($image_format == 2) {
                $old_image = imagecreatefromjpeg($image_file);
            } elseif ($image_format == 3) {
                $old_image = imagecreatefrompng($image_file);
            } else {
                return '';
            }

            if ($image_width > $new_width || $image_height > $new_height) {
                if (($new_width != 0) && ($new_width < $image_width)) {
                    $image_height = (int)($image_height * ($new_width / $image_width));
                    $image_width = $new_width;
                }
                if (($new_height != 0) && ($new_height < $image_height)) {
                    $image_width = (int)($image_width * ($new_height / $image_height));
                    $image_height = $new_height;
                }
            }
            $new_image = imageCreateTrueColor($image_width, $image_height);
            $white = ImageColorAllocate($new_image, 255, 255, 255);
            ImageFill($new_image, 0, 0, $white);
            imagecopyresampled($new_image, $old_image, 0, 0, 0, 0, $image_width, $image_height, imageSX($old_image), imageSY($old_image));

            $cache = ROOT . 'cms/cached/' . md5($image_file) . '.jpg';
            if (imageJpeg($new_image, $cache)) {
                $data = LoadFile($cache);
                $content = 'data:image/jpeg;base64,' . base64_encode($data);
            }
        }
        return $content;
    }

    function customgpt($instructions, $prompt, $temperature = 0, $image_file = '')
    {
        $this->getConfig();
        if (!$this->config['CUSTOM_GPT_URL']) return false;

        $answer = '';

        $url = $this->config['CUSTOM_GPT_URL'];
        $model = $this->config['CUSTOM_GPT_MODEL'];
        $api_key = $this->config['CUSTOM_GPT_KEY'];

        $headers = array(
            'Content-Type: application/json'
        );

        if ($api_key) {
            $headers[] = "Authorization: Bearer " . $api_key;
        }

        if ($image_file != '' && file_exists($image_file)) {
            $image_content = $this->prepareImage($image_file);
            if ($image_content) {
                $prompt = array(
                    array('type' => 'text', 'text' => $prompt),
                    array('type' => 'image_url', 'image_url' => array('url' => $image_content)),
                );
            }
        }

        $request = array(
            'temperature' => $temperature,
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

        if ($model) {
            $request['model'] = $model;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
        $result = curl_exec($ch);
        if ($result === false) {
            DebMes('gptchat cURL error: ' . curl_error($ch), 'gptchat');
            $result = '';
        }
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

    function openaigpt($model, $instructions, $prompt, $temperature = 0, $image_file = '')
    {
        $this->getConfig();
        if (!$this->config['OPENAI_API_KEY']) return false;

        $answer = '';

        $url = 'https://api.openai.com/v1/chat/completions';
        $headers = array(
            'Content-Type: application/json',
            "Authorization: Bearer " . $this->config['OPENAI_API_KEY']
        );

        if ($image_file != '' && file_exists($image_file)) {
            $image_content = $this->prepareImage($image_file);
            if ($image_content) {
                $prompt = array(
                    array('type' => 'text', 'text' => $prompt),
                    array('type' => 'image_url', 'image_url' => array('url' => $image_content)),
                );
            }
        }

        $request = array(
            'model' => $model,
            'temperature' => $temperature,
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
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
        $result = curl_exec($ch);
        if ($result === false) {
            DebMes('gptchat cURL error: ' . curl_error($ch), 'gptchat');
            $result = '';
        }
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

    function yandexgpt($model, $instructions, $prompt, $temperature = 0, $image_file = '')
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
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $result = curl_exec($ch);
        if ($result === false) {
            DebMes('gptchat cURL error: ' . curl_error($ch), 'gptchat');
            $result = '';
        }
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
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
        $result = curl_exec($ch);
        if ($result === false) {
            DebMes('gptchat cURL error: ' . curl_error($ch), 'gptchat');
            $result = '';
        }
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

    function isDeprecatedModel($model)
    {
        $deprecated = array(
            'gpt-3.5-turbo', 'gpt-3.5-turbo-0301', 'gpt-3.5-turbo-0613', 'gpt-3.5-turbo-16k',
            'gpt-4-0314', 'gpt-4-0613', 'gpt-4-32k', 'text-davinci-003', 'text-davinci-002',
            'yandexgpt-lite-rc', 'yandex_summarization',
        );
        return in_array(strtolower(trim((string)$model)), $deprecated, true);
    }
    function requestJson($url, $headers, $payload)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        $result = curl_exec($ch);
        if ($result === false) {
            DebMes('gptchat cURL error: ' . curl_error($ch), 'gptchat');
            $result = '';
        }
        curl_close($ch);
        return $result;
    }

    function claudegpt($model, $instructions, $prompt, $temperature = 0)
    {
        $this->getConfig();
        if (empty($this->config['CLAUDE_API_KEY'])) return false;
        $result = $this->requestJson('https://api.anthropic.com/v1/messages', array(
            'Content-Type: application/json',
            'x-api-key: ' . $this->config['CLAUDE_API_KEY'],
            'anthropic-version: 2023-06-01',
        ), array(
            'model' => $model,
            'max_tokens' => 1000,
            'temperature' => (float)$temperature,
            'system' => $instructions,
            'messages' => array(array('role' => 'user', 'content' => $prompt)),
        ));
        $data = json_decode($result, true);
        if (isset($data['content'][0]['text'])) return $data['content'][0]['text'];
        DebMes('Cannot answer message: ' . $result, 'gptchat');
        return '';
    }

    function geminigpt($model, $instructions, $prompt, $temperature = 0)
    {
        $this->getConfig();
        if (empty($this->config['GEMINI_API_KEY'])) return false;
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model)
            . ':generateContent?key=' . rawurlencode($this->config['GEMINI_API_KEY']);
        $result = $this->requestJson($url, array('Content-Type: application/json'), array(
            'contents' => array(array('parts' => array(array('text' => $instructions . "\n\n" . $prompt)))),
            'generationConfig' => array('temperature' => (float)$temperature, 'maxOutputTokens' => 1000),
        ));
        $data = json_decode($result, true);
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) return $data['candidates'][0]['content']['parts'][0]['text'];
        DebMes('Cannot answer message: ' . $result, 'gptchat');
        return '';
    }

    function openaiCompatible($provider, $model, $instructions, $prompt, $temperature = 0)
    {
        $endpoints = array(
            'deepseek' => 'https://api.deepseek.com/chat/completions',
            'kimi' => 'https://api.moonshot.cn/v1/chat/completions',
            'qwen' => 'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions',
        );
        if (!isset($endpoints[$provider])) return false;
        $this->getConfig();
        $keyName = strtoupper($provider) . '_API_KEY';
        if (empty($this->config[$keyName])) return false;
        $result = $this->requestJson($endpoints[$provider], array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->config[$keyName],
        ), array(
            'model' => $model,
            'temperature' => (float)$temperature,
            'messages' => array(
                array('role' => 'system', 'content' => $instructions),
                array('role' => 'user', 'content' => $prompt),
            ),
        ));
        $data = json_decode($result, true);
        if (isset($data['choices'][0]['message']['content'])) return $data['choices'][0]['message']['content'];
        DebMes('Cannot answer message: ' . $result, 'gptchat');
        return '';
    }

    function improvePromptOnce($chat, $instructions, $prompt)
    {
        $metaInstruction = 'You improve prompts for a home automation assistant. '
            . 'Return ONLY the improved prompt text: no markdown, no explanations, no quotes. '
            . 'Keep the meaning, all %PLACEHOLDERS% and object properties unchanged. '
            . 'Make the prompt specific and concise to reduce token cost. '
            . 'Do not add requests to execute code, change system settings, reveal secrets or access the network.';
        $metaPrompt = "Instruction: " . $instructions . "\nPrompt: " . $prompt;
        $model = isset($chat['MODEL']) ? $chat['MODEL'] : '';

        if (method_exists($this, 'callProvider')) {
            $improved = $this->callProvider($model, $metaInstruction, $metaPrompt, 0);
        } elseif ($model == 'customgpt') {
            $improved = $this->customgpt($metaInstruction, $metaPrompt, 0);
        } elseif (preg_match('/yandex/is', $model)) {
            $improved = $this->yandexgpt($model, $metaInstruction, $metaPrompt, 0);
        } else {
            $improved = $this->openaigpt($model, $metaInstruction, $metaPrompt, 0);
        }

        if (!is_string($improved) || trim($improved) === '') {
            DebMes('Prompt improvement failed, original kept (chat ' . (int)$chat['ID'] . ')', 'gptchat');
            return $prompt;
        }
        DebMes('Prompt improved once for chat ' . (int)$chat['ID'], 'gptchat');
        return trim($improved);
    }

    function audioProviders()
    {
        return array(
            'openai' => 'OpenAI Whisper + Chat',
            'elevenlabs' => 'ElevenLabs Speech to Text',
            'gemini' => 'Google Gemini (audio)',
            'yandex' => 'Yandex SpeechKit + GPT',
        );
    }

    function audioFiles()
    {
        $files = array();
        $dir = ROOT . 'cms' . DIRECTORY_SEPARATOR . 'sounds' . DIRECTORY_SEPARATOR;
        if (!is_dir($dir)) {
            return $files;
        }
        foreach (glob($dir . '*.{mp3,wav,ogg,m4a}', GLOB_BRACE) as $file) {
            $files[] = basename($file);
        }
        sort($files);
        return $files;
    }

    function sttTranscribe($provider, $filePath, $language)
    {
        if (!is_file($filePath)) {
            return array('text' => '', 'error' => 'Файл не найден');
        }
        $this->getConfig();

        if ($provider === 'openai') {
            if (empty($this->config['OPENAI_API_KEY'])) return array('text' => '', 'error' => 'Не задан OpenAI API Key');
            $post = array('model' => 'whisper-1', 'file' => '@' . $filePath);
            if ($language !== '') $post['language'] = $language;
            $ch = curl_init('https://api.openai.com/v1/audio/transcriptions');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->config['OPENAI_API_KEY']));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            $result = curl_exec($ch);
            if ($result === false) { $error = curl_error($ch); curl_close($ch); return array('text' => '', 'error' => $error); }
            curl_close($ch);
            $data = json_decode($result, true);
            if (isset($data['text'])) return array('text' => $data['text'], 'error' => '');
            return array('text' => '', 'error' => substr((string)$result, 0, 300));
        }

        if ($provider === 'elevenlabs') {
            if (empty($this->config['ELEVENLABS_API_KEY'])) return array('text' => '', 'error' => 'Не задан ElevenLabs API Key');
            $ch = curl_init('https://api.elevenlabs.io/v1/speech-to-text');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('xi-api-key: ' . $this->config['ELEVENLABS_API_KEY']));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            $post = array('file' => '@' . $filePath, 'model_id' => 'scribe_v1');
            if ($language !== '') $post['language_code'] = $language;
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            $result = curl_exec($ch);
            if ($result === false) { $error = curl_error($ch); curl_close($ch); return array('text' => '', 'error' => $error); }
            curl_close($ch);
            $data = json_decode($result, true);
            if (isset($data['text'])) return array('text' => $data['text'], 'error' => '');
            return array('text' => '', 'error' => substr((string)$result, 0, 300));
        }

        if ($provider === 'gemini') {
            if (empty($this->config['GEMINI_API_KEY'])) return array('text' => '', 'error' => 'Не задан Gemini API Key');
            $mime = 'audio/mpeg';
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if ($ext === 'wav') $mime = 'audio/wav';
            if ($ext === 'ogg') $mime = 'audio/ogg';
            if ($ext === 'm4a') $mime = 'audio/mp4';
            $payload = array(
                'contents' => array(array('parts' => array(
                    array('text' => 'Transcribe this audio exactly. Return only the transcript text.'),
                    array('inline_data' => array('mime_type' => $mime, 'data' => base64_encode(file_get_contents($filePath)))),
                ))),
            );
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . rawurlencode($this->config['GEMINI_API_KEY']);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            $result = curl_exec($ch);
            if ($result === false) { $error = curl_error($ch); curl_close($ch); return array('text' => '', 'error' => $error); }
            curl_close($ch);
            $data = json_decode($result, true);
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) return array('text' => $data['candidates'][0]['content']['parts'][0]['text'], 'error' => '');
            return array('text' => '', 'error' => substr((string)$result, 0, 300));
        }

        if ($provider === 'yandex') {
            if (empty($this->config['YANDEX_OAUTH']) || empty($this->config['YANDEX_CATALOG_ID'])) {
                return array('text' => '', 'error' => 'Не заданы Yandex OAuth/Catalog ID');
            }
            $ch = curl_init('https://iam.api.cloud.yandex.net/iam/v1/tokens');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('yandexPassportOauthToken' => $this->config['YANDEX_OAUTH'])));
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $iamResult = curl_exec($ch);
            curl_close($ch);
            $iam = json_decode((string)$iamResult, true);
            if (empty($iam['iamToken'])) return array('text' => '', 'error' => 'Не удалось получить IAM-токен');
            $url = 'https://stt.api.cloud.yandex.net/speech/v1/stt:recognize?folderId=' . rawurlencode($this->config['YANDEX_CATALOG_ID']);
            if ($language !== '') $url .= '&language=' . rawurlencode($language);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $iam['iamToken']));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($filePath));
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            $result = curl_exec($ch);
            if ($result === false) { $error = curl_error($ch); curl_close($ch); return array('text' => '', 'error' => $error); }
            curl_close($ch);
            $data = json_decode($result, true);
            if (isset($data['result'])) return array('text' => $data['result'], 'error' => '');
            return array('text' => '', 'error' => substr((string)$result, 0, 300));
        }
        return array('text' => '', 'error' => 'Неизвестный провайдер');
    }

    function audioProcess($provider, $file, $task, $language)
    {
        $file = basename((string)$file);
        $path = ROOT . 'cms' . DIRECTORY_SEPARATOR . 'sounds' . DIRECTORY_SEPARATOR . $file;
        $stt = $this->sttTranscribe($provider, $path, $language === '' ? 'ru-RU' : $language);
        if ($stt['error'] !== '') {
            DebMes('Audio STT error (' . $provider . '): ' . $stt['error'], 'gptchat');
            return array('text' => '', 'error' => $stt['error']);
        }
        $text = $stt['text'];
        if ($task === 'translate' || $task === 'summary') {
            $instruction = $task === 'translate'
                ? 'Translate the following text into ' . $language . '. Return only the translation.'
                : 'Summarize the following text in ' . $language . '. Return only the summary.';
            $model = $provider === 'openai' ? 'gpt-4o-mini' : ($provider === 'yandex' ? 'yandexgpt-lite' : 'gemini-2.0-flash'); // ElevenLabs uses Gemini for text tasks
            if (method_exists($this, 'callProvider')) {
                $textResult = $this->callProvider($model, $instruction, $text, 0);
            } elseif ($provider === 'openai' && method_exists($this, 'openaigpt')) {
                $textResult = $this->openaigpt($model, $instruction, $text, 0);
            } elseif ($provider === 'yandex' && method_exists($this, 'yandexgpt')) {
                $textResult = $this->yandexgpt($model, $instruction, $text, 0);
            } else {
                $textResult = $text;
            }
            if (is_string($textResult) && trim($textResult) !== '') $text = $textResult;
        }
        return array('text' => $text, 'error' => '');
    }

    function imageProviders()
    {
        return array(
            'openai' => 'OpenAI (vision + images)',
            'gemini' => 'Google Gemini (vision + Imagen)',
        );
    }

    function imageFiles()
    {
        $files = array();
        $dirs = array(
            ROOT . 'cms' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR,
            ROOT . 'cms' . DIRECTORY_SEPARATOR . 'cached' . DIRECTORY_SEPARATOR,
        );
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) continue;
            foreach (glob($dir . '*.{png,jpg,jpeg,webp}', GLOB_BRACE) as $file) {
                $files[] = str_replace(ROOT, '', $file);
            }
        }
        sort($files);
        return $files;
    }

    function imageAnalyze($provider, $file, $prompt)
    {
        $file = ltrim(str_replace('\\', '/', (string)$file), '/');
        if (strpos($file, '..') !== false || !is_file(ROOT . $file)) {
            return array('text' => '', 'error' => 'Файл не найден или путь вне каталога проекта');
        }
        $this->getConfig();
        $mime = 'image/jpeg';
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($ext === 'png') $mime = 'image/png';
        if ($ext === 'webp') $mime = 'image/webp';
        $base64 = base64_encode(file_get_contents(ROOT . $file));

        if ($provider === 'gemini') {
            if (empty($this->config['GEMINI_API_KEY'])) return array('text' => '', 'error' => 'Не задан Gemini API Key');
            $payload = array('contents' => array(array('parts' => array(
                array('text' => $prompt),
                array('inline_data' => array('mime_type' => $mime, 'data' => $base64)),
            ))));
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . rawurlencode($this->config['GEMINI_API_KEY']);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            $result = curl_exec($ch);
            if ($result === false) { $e = curl_error($ch); curl_close($ch); return array('text' => '', 'error' => $e); }
            curl_close($ch);
            $data = json_decode($result, true);
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) return array('text' => $data['candidates'][0]['content']['parts'][0]['text'], 'error' => '');
            return array('text' => '', 'error' => substr((string)$result, 0, 300));
        }

        if (empty($this->config['OPENAI_API_KEY'])) return array('text' => '', 'error' => 'Не задан OpenAI API Key');
        $payload = array(
            'model' => 'gpt-4o-mini',
            'messages' => array(array('role' => 'user', 'content' => array(
                array('type' => 'text', 'text' => $prompt),
                array('type' => 'image_url', 'image_url' => array('url' => 'data:' . $mime . ';base64,' . $base64)),
            ))),
        );
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bearer ' . $this->config['OPENAI_API_KEY']));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        $result = curl_exec($ch);
        if ($result === false) { $e = curl_error($ch); curl_close($ch); return array('text' => '', 'error' => $e); }
        curl_close($ch);
        $data = json_decode($result, true);
        if (isset($data['choices'][0]['message']['content'])) return array('text' => $data['choices'][0]['message']['content'], 'error' => '');
        return array('text' => '', 'error' => substr((string)$result, 0, 300));
    }

    function imageGenerate($provider, $prompt, $width = 1024, $height = 1024)
    {
        $this->getConfig();
        $width = max(256, min(1536, (int)$width));
        $height = max(256, min(1536, (int)$height));

        if ($provider === 'gemini') {
            if (empty($this->config['GEMINI_API_KEY'])) return array('url' => '', 'error' => 'Не задан Gemini API Key');
            $payload = array('instances' => array(array('prompt' => $prompt)));
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/imagen-3.0-generate-002:predict?key=' . rawurlencode($this->config['GEMINI_API_KEY']);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 180);
            $result = curl_exec($ch);
            if ($result === false) { $e = curl_error($ch); curl_close($ch); return array('url' => '', 'error' => $e); }
            curl_close($ch);
            $data = json_decode($result, true);
            if (isset($data['predictions'][0]['bytesBase64Encoded'])) {
                $binary = base64_decode($data['predictions'][0]['bytesBase64Encoded']);
                return $this->saveGeneratedImage($binary);
            }
            return array('url' => '', 'error' => substr((string)$result, 0, 300));
        }

        if (empty($this->config['OPENAI_API_KEY'])) return array('url' => '', 'error' => 'Не задан OpenAI API Key');
        $size = $width >= $height ? $width . 'x' . $height : $height . 'x' . $width;
        $allowed = array('1024x1024', '1536x1024', '1024x1536');
        if (!in_array($size, $allowed, true)) $size = '1024x1024';
        $payload = array('model' => 'gpt-image-1', 'prompt' => $prompt, 'size' => $size, 'n' => 1);
        $ch = curl_init('https://api.openai.com/v1/images/generations');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bearer ' . $this->config['OPENAI_API_KEY']));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        $result = curl_exec($ch);
        if ($result === false) { $e = curl_error($ch); curl_close($ch); return array('url' => '', 'error' => $e); }
        curl_close($ch);
        $data = json_decode($result, true);
        if (isset($data['data'][0]['b64_json'])) {
            return $this->saveGeneratedImage(base64_decode($data['data'][0]['b64_json']));
        }
        if (isset($data['data'][0]['url'])) return array('url' => $data['data'][0]['url'], 'error' => '');
        return array('url' => '', 'error' => substr((string)$result, 0, 300));
    }

    function saveGeneratedImage($binary)
    {
        $dir = ROOT . 'cms' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $name = 'gptchat_' . date('Ymd_His') . '_' . substr(md5((string)microtime(true)), 0, 6) . '.png';
        if (file_put_contents($dir . $name, $binary) === false) {
            return array('url' => '', 'error' => 'Не удалось сохранить изображение');
        }
        return array('url' => 'cms/images/' . $name, 'error' => '');
    }

    function api($params)
    {
        if (isset($params['id'])) {
            $image = '';
            if (isset($params['image'])) {
                $image = $params['image'];
            }
    function auditCode($code, $language = 'php')
    {
        $chatId = 0;
        if (defined('SETTINGS_CODEEDITOR_GPTCHAT_ID')) {
            $chatId = (int)SETTINGS_CODEEDITOR_GPTCHAT_ID;
        }
        if (!$chatId) {
            $chat = SQLSelectOne("SELECT ID FROM gptchats WHERE TITLE LIKE '%audit%' OR TITLE LIKE '%аудит%' ORDER BY ID LIMIT 1");
            $chatId = $chat['ID'] ? (int)$chat['ID'] : 0;
        }
        if (!$chatId) {
            return '';
        }
        $this->activateChat($chatId, array('code' => $code, 'language' => $language, 'task' => 'Code review'));
        $history = SQLSelectOne("SELECT ANSWER FROM gptchats_history WHERE CHAT_ID=" . $chatId . " ORDER BY ID DESC LIMIT 1");
        return $history['ANSWER'] ?: '';
    }
            $this->activateChat($params['id'], $params, $image);
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
