<?php

namespace meta\GeneralBundle\Twig;

class DeepLinkingExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            'deeplinks' => new \Twig_Filter_Method($this, 'convertDeepLinks'),
        );
    }

    public function convertDeepLinks($text)
    {

        return $text;
    }

    public function getName()
    {
        return 'deep_linking_extension';
    }
}