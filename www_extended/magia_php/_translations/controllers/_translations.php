<?php

// read the blog 
// Blog
// https://blog.factuz.com/index.php?c=public_html&a=details&id=15
define("API_URL", "");
// c = api & a = invoices & api_key = demo & function = list
// c = api & a = invoices & function = details & id = 10
// 
$function = (isset($_GET["function"])) ? clean($_GET["function"]) : null;
// api_ke que se le da al usuario
//
$api_key = (isset($_GET["api_key"])) ? clean($_GET["api_key"]) : null;
// para registrar los errores
//
$error = array();
//
##########################################################################
# MANDATORY
# Aca se controlla las variables obligatorias son enviadas
if ($api_key == "" || $api_key == null || $api_key == false) {
    array_push($error, "api_key is manatory");
}
//
###########################################################################
# FORMAT
# Aca controlamos el formato de las variables enviadas por el usuario
# si no es formato adecuado da error 
# 
##########################################################################
# CONDICIONES
# Aca verificamos las condiciones de control de las variables 
// La api_key es valida ? 
// puede hacer u crud ?
// Limite de request?
// No es mandatory pero si no manda la funcion mostramos todas las funciones disponibles
if ($function == "" || $function == null || $function == false) {
    //array_push($error, "function is manatory");
    $function = "functions_list";
}
////////////////////////////////////////////////////////////////////////////////
$function_list = array(
    "functions_list", // lista de todas las funciones disponibles
    "add",
    "details",
    "update",
    "delete",
    "push",
    "search",
);
//
if (!in_array($function, $function_list)) {
    array_push($error, "function name incorrect");
}
################################################################################
################################################################################
################################################################################

$content = (isset($_GET["content"])) ? clean($_GET["content"]) : null;
$language = (isset($_GET["language"])) ? clean($_GET["language"]) : null;


################################################################################
################################################################################
################################################################################
if (!$error) {
    $json = json_encode($data, JSON_PRETTY_PRINT);
} else {
    $json = json_encode($error, JSON_PRETTY_PRINT);
}
################################################################################
################################################################################
################################################################################


function api_extract_quoted_text($input) {
    if (preg_match('/"([^"]+)"/', $input, $matches)) {
        return $matches[1];
    }
    return null;
}

function api_curl_request($url, $i, $attempts) {
    $i++;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, 'AndroidTranslate/5.3.0.RC02.130475354-53000263 5.1 phone TRANSLATE_OPM5_TEST_1');

    $result = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (false === $result || 200 !== $httpcode) {
        if ($i >= $attempts) {
            return null;
        } else {
            usleep(1500000); 
            return api_curl_request($url, $i, $attempts);
        }
    } else {
        return $result;
    }
}

function api_get_sentences_from_json($json) {
    $arr = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return '';
    }
    
    $sentences = '';
    if (isset($arr['sentences'])) {
        foreach ($arr['sentences'] as $s) {
            $sentences .= isset($s['trans']) ? $s['trans'] : '';
        }
    }

    return $sentences;
}

function api_fields_string($fields) {
    $fields_string = '';
    foreach ($fields as $key => $value) {
        $fields_string .= $key.'='.urlencode($value).'&';
    }
    return rtrim($fields_string, '&');
}

function api_curl_request_for_translate($url, $fields, $fields_string, $i, $attempts) {
    $i++;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, 'AndroidTranslate/5.3.0.RC02.130475354-53000263 5.1 phone TRANSLATE_OPM5_TEST_1');

    try {
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (false === $result || 200 !== $httpcode) {
            throw new Exception('Request failed with HTTP code: ' . $httpcode);
        }
        return $result;
    } catch (Exception $e) {
        if ($i >= $attempts) {
            return null;
        } else {
            usleep(1500000);
            return api_curl_request_for_translate($url, $fields, $fields_string, $i, $attempts);
        }
    } finally {
        curl_close($ch);
    }
}

function api_request_translation($source, $target, $text, $attempts) {
    $url = 'https://translate.google.com/translate_a/single?client=at&dt=t&dt=ld&dt=qca&dt=rm&dt=bd&dj=1&hl=uk-RU&ie=UTF-8&oe=UTF-8&inputm=2&otf=2&iid=1dd3b944-fa62-4b55-b330-74909a99969e';
    $fields = [
        'sl' => $source,
        'tl' => $target,
        'q'  => $text,
    ];

    if (strlen($fields['q']) >= 5000) {
        echo 'Maximum number of characters exceeded: 5000';
        return null;
    }

    $fields_string = api_fields_string($fields);
    $content = api_curl_request_for_translate($url, $fields, $fields_string, 0, $attempts);
    return api_get_sentences_from_json($content);
}

function api_translate($source, $target, $text, $attempts = 5) {
    return api_request_translation($source, $target, $text, $attempts);
}

function api_translations_search_by_content($content) {
    global $db;
    $attempts = 5;
    $base_url = 'https://coop.factuz.com/index.php';
    $params = [
        'c' => 'api',
        'api_key' => $_GET['api_key'] ?? 'demo',
        'a' => $_GET['a'] ?? '_translations',
        'function' => $_GET['function'] ?? 'search',
        'content' => $content,
    ];
    $query_string = http_build_query($params);
    $url = $base_url . '?' . $query_string;

    $response = api_curl_request($url, 0, $attempts);

    if (api_extract_quoted_text($response) === 'Not find') {
        $source = 'es';
        $target = 'fr';
        $language = $_GET['language'] ?? '';

        $resp_translate = api_translate($source, $target, $content, $attempts);

        try {
            $sql = "SELECT id, content, language, translation 
                    FROM `_translations` 
                    WHERE `content` = :content 
                    ORDER BY id DESC LIMIT 1";
            $query = $db->prepare($sql);
            $query->bindValue(':content', $content, PDO::PARAM_STR);
            $query->execute();
            $data = $query->fetchAll();

            if (empty($data)) {
                $parts = explode('_', $language);
                $lang = $parts[0] ?? '';

                $sql_check = "SELECT * FROM _content WHERE frase LIKE :word";
                $query_check = $db->prepare($sql_check);
                $query_check->bindValue(':word', '%' . $content . '%', PDO::PARAM_STR);
                $query_check->execute();
                $data_content = $query_check->fetchAll();

                if (empty($data_content)) {
                    $sql_insert_content = "INSERT INTO _content (id, frase, contexto) VALUES (NULL, :word, NULL)";
                    $query_insert_content = $db->prepare($sql_insert_content);
                    $query_insert_content->bindValue(':word', $content, PDO::PARAM_STR);
                    $query_insert_content->execute();
                }

                $sql_insert = "INSERT INTO _translations (id, content, language, translation) VALUES (NULL, :word, :language, :translation)";
                $query = $db->prepare($sql_insert);
                $query->bindValue(':word', $content, PDO::PARAM_STR);
                $query->bindValue(':language', $language, PDO::PARAM_STR);
                $query->bindValue(':translation', $resp_translate, PDO::PARAM_STR);
                $query->execute();
            }
        } catch (PDOException $e) {
            echo 'Database error: ' . $e->getMessage();
            return null;
        }
    }

    return api_curl_request($url, 0, $attempts);
}





$content = isset($_GET['content']) ? $_GET['content'] : '';
if ($content) {
    api_translations_search_by_content($content);

}




################################################################################
include view('api', '_translations');
