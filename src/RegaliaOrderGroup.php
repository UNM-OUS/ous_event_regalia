<?php
namespace Digraph\Modules\ous_event_regalia;

use Digraph\DSO\Noun;
use Digraph\Modules\ous_event_management\SignupWindow;

class RegaliaOrderGroup extends Noun
{
    protected $allOrders;

    public function actions($links)
    {
        if (!$this['locked']) {
            $links['add-signups'] = '!id/add-signups';
        }
        $links['export-spreadsheet'] = '!id/export-spreadsheet';
        return $links;
    }

    public function childEdgeType($child)
    {
        if ($child instanceof SignupWindow) {
            return 'regalia-group-signupwindow';
        }else {
            return null;
        }
    }

    public function assignedOrders(): array
    {
        return array_filter(
            $this->allOrders(),
            function (RegaliaOrder $order) {
                return !$order['extra'];
            }
        );
    }

    public function extraOrders(): array
    {
        return array_filter(
            $this->allOrders(),
            function (RegaliaOrder $order) {
                return $order['extra'];
            }
        );
    }

    public function allOrders(): array
    {
        if ($this->allOrders === null) {
            $this->allOrders = $this->cms()->helper('graph')->children(
                $this['dso.id'],
                'regalia-group-order',
                1,
                '${digraph.name} asc'
            );
        }
        return $this->allOrders;
    }

    public function parent()
    {
        return null;
    }

    public function parentUrl($verb = 'display')
    {
        if ($verb == 'display') {
            return $this->cms()->helper('urls')->parse('_regalia-management');
        }
        return parent::parentUrl($verb);
    }

    public function formMap(string $action): array
    {
        $map = parent::formMap($action);
        $map['digraph_body']['label'] = 'Instructions for recipients';
        $map['digraph_body']['tips']['regalia'] = 'Use this field to provide instructions for the people receiving this regalia, such as where to pick it up, or to indicate to them that it will be available at the event.';
        $map['digraph_title'] = false;
        $map['regalia_hattype'] = [
            'label' => 'Hat type',
            'class' => 'select',
            'field' => 'hattype',
            'call' => [
                'options' => [[
                    'cap' => 'Cap (mortarboard)',
                    'tam' => 'Tam',
                ]],
            ],
            'required' => true,
            'default' => 'cap',
            'weight' => 300,
        ];
        $map['regalia_lock'] = [
            'label' => 'Lock this order group, preventing adding new orders through the bulk-adding tool, and prevent existing orders from being transferred to other order groups. This should be checked immediately before pulling/finalizing the order to send to Jostens.',
            'class' => 'checkbox',
            'field' => 'locked',
            'weight' => 200,
            'default' => false,
        ];
        return $map;
    }
}
