<?php
namespace Digraph\Modules\event_regalia;

use Digraph\DSO\Noun;

class BillingIndex extends Noun
{
    function formMap(string $action): array
    {
        $map = parent::formMap($action);
        $map['digraph_title'] = false;
        return $map;
    }
}
