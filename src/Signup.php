<?php
namespace Digraph\Modules\ous_event_regalia;

use Digraph\DSO\Noun;
use Digraph\Modules\ous_event_management\Signup as Ous_event_managementSignup;
use Digraph\Modules\ous_event_regalia\Regalia\RegaliaOrderChunk;
use Digraph\Modules\ous_event_regalia\Regalia\RegaliaPlacedChunk as RegaliaRegaliaPlacedChunk;

class Signup extends Ous_event_managementSignup
{
    public function regaliaOrder(RegaliaOrder $set = null): ?RegaliaOrder
    {
        if ($set) {
            $this->cms()->helper('edges')->deleteParents($this['dso.id'], 'signup-regalia-order');
            $this->cms()->helper('edges')->create($set['dso.id'], $this['dso.id']);
        }
        $parents = $this->cms()->helper('graph')->parents($this['dso.id'], 'signup-regalia-order');
        return $parents ? reset($parents) : null;
    }

    public function regaliaGroup(RegaliaOrderGroup $set = null): ?RegaliaOrderGroup
    {
        if ($this->regaliaOrder()) {
            return $this->regaliaOrder()->orderGroup();
        }
        return null;
    }

    public function parentEdgeType(Noun $parent): ?string
    {
        if ($parent instanceof RegaliaOrder) {
            return 'signup-regalia-order';
        }
        return parent::parentEdgeType($parent);
    }

    public function regaliaRequirement(): string
    {
        $return = 'none';
        $type = $this->signupWindow()['signup_windowtype'];
        foreach ($this->allEvents() as $event) {
            $setting = $event['regalia.' . $type];
            if ($setting == 'required') {
                return 'required';
            }
            if ($setting == 'optional') {
                $return = 'optional';
            }
        }
        return $return;
    }

    protected function myChunks(): array
    {
        $chunks = parent::myChunks();
        if ($this->regaliaRequirement() == 'required') {
            $chunks['regalia'] = RegaliaOrderChunk::class;
        } elseif ($this->regaliaRequirement() == 'optional') {
            $chunks['regalia'] = RegaliaOrderChunk::class;
        }
        if ($this->regaliaOrder()) {
            if ($this->regaliaGroup() && $this->regaliaGroup()['locked']) {
                $chunks['regalia-placed'] = RegaliaRegaliaPlacedChunk::class;
            }
        }
        return $chunks;
    }
}
