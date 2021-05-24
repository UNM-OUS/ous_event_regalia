<?php
namespace Digraph\Modules\event_regalia\Regalia;

use Digraph\Modules\ous_event_management\Chunks\AbstractChunk;

class RegaliaPlacedChunk extends AbstractChunk
{
    protected $label = 'Assigned regalia order';

    public function body_complete()
    {
        $order = $this->signup->regaliaOrder();
        $group = $order->orderGroup();
        echo $group->body();
        echo $this->signup->cms()->helper('regalia')->orderDisplay(
            $order, false
        );
    }

    public function complete(): bool
    {
        return true;
    }

    public function form_map(): array
    {
        return [];
    }

    public function body_edit()
    {
        echo 'Not editable here';
    }

    protected function buttonText_edit()
    {
        return "";
    }

    protected function buttonText_editIncomplete()
    {
        return "";
    }

}
