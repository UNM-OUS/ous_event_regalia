<?php

namespace Digraph\Modules\ous_event_regalia\Regalia;

use Digraph\CMS;
use Formward\FieldInterface;
use Formward\Fields\Container;
use Formward\Fields\Number;

class RegaliaPriceField extends Container
{
    public function __construct(string $label, string $name = null, FieldInterface $parent = null, CMS $cms = null)
    {
        parent::__construct($label, $name, $parent);
        // gender
        $this['hood'] = new Number('Hood');
        $this['gown'] = new Number('Gown');
        $this['cap'] = new Number('Cap');
        $this['tam'] = new Number('Tam');
        $this['package c/g'] = new Number('Package Cap/Gown');
        $this['package t/g'] = new Number('Package Tam/Gown');
    }
}
