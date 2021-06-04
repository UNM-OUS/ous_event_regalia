<?php

namespace Digraph\Modules\ous_event_regalia;

use Digraph\DSO\Noun;
use Digraph\Modules\ous_event_regalia\Regalia\RegaliaPriceField;

class RegaliaPrices extends Noun
{
    function formMap(string $action): array
    {
        $map = parent::formMap($action);
        $map['digraph_title'] = false;
        $map['digraph_body'] = false;
        $map['prices_doctor'] = [
            'label' => 'Doctoral',
            'class' => RegaliaPriceField::class,
            'weight' => 300,
            'field' => 'prices.doctor'
        ];
        $map['prices_master'] = [
            'label' => 'Master',
            'class' => RegaliaPriceField::class,
            'weight' => 300,
            'field' => 'prices.master'
        ];
        $map['prices_bachelor'] = [
            'label' => 'Bachelor',
            'class' => RegaliaPriceField::class,
            'weight' => 300,
            'field' => 'prices.bachelor'
        ];
        return $map;
    }

    public function parentEdgeType(Noun $parent): ?string
    {
        if ($parent instanceof RegaliaOrderGroup) {
            return 'regalia-group-prices';
        }
        return null;
    }
}
