<?php

namespace meta\GeneralBundle\Services;
 
class TextService
{
    public function slugify($text) 
    { 
        // replace non letter or digits by - 
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text); 

        // trim 
        $text = trim($text, '-'); 

        if (function_exists('iconv')){
          // transliterate 
          $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text); 
        }

        // lowercase 
        $text = strtolower($text); 

        // remove unwanted characters 
        $text = preg_replace('~[^-\w]+~', '', $text); 

        if (empty($text)) return false;

        return $text; 
    } 
}