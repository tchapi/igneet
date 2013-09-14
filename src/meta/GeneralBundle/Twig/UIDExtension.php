<?php

namespace meta\GeneralBundle\Twig;

use Symfony\Component\Routing\Router;

const _BASE_MULTIPLIER = 11111111111111;

class UIDExtension extends \Twig_Extension
{

    public function getFilters()
    {
        return array(
            'to_uid' => new \Twig_Filter_Method($this, 'toUId'),
            'from_uid' => new \Twig_Filter_Method($this, 'fromUId'),
        );
    }

    public function toUId($baseId) {
        return base_convert($baseId * _BASE_MULTIPLIER, 10, 36);
    }

    public function fromUId($uid) {
        return (int) base_convert($uid, 36, 10) / _BASE_MULTIPLIER;
    }

    public function getName()
    {
        return 'uid';
    }

}