<?php





function api_t($word){
    api_search_word_on_service($word);
    // die();
    // api_translations_search_by_content($word);
    return $word;   
}