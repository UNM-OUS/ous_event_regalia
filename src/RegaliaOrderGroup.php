<?php

namespace Digraph\Modules\ous_event_regalia;

use Digraph\DSO\Noun;
use Digraph\Modules\ous_event_management\SignupWindow;

class RegaliaOrderGroup extends Noun
{
    protected $allOrders, $allOrderIDs;
    protected $regaliaPrices = false;

    /**
     * Returns the regalia prices object to use for this order group, which will
     * be either (in this order of priority):
     *  * the newest direct child of this group
     *  * the newest prices from before this group was created
     *  * the oldest prices in the system
     *
     * @return RegaliaPrices|null
     */
    public function regaliaPrices(): ?RegaliaPrices
    {
        if ($this->regaliaPrices === false) {
            if (!($this->regaliaPrices = $this->regaliaPricesChild())) {
                // no child, find most recent before this cdate
                $search = $this->cms()->factory()->search();
                $search->where('${dso.type} = "regalia-prices" AND ${dso.date.created} < :time');
                $search->order('${dso.date.created} desc');
                $search->limit(1);
                if ($result = $search->execute(['time' => $this['dso.created.date']])) {
                    $this->regaliaPrices = reset($result);
                } else {
                    $search->where('${dso.type} = "regalia-prices"');
                    $search->order('${dso.date.created} asc');
                    if ($result = $search->execute(['time' => $this['dso.created.date']])) {
                        $this->regaliaPrices = reset($result);
                    } else {
                        $this->regaliaPrices = null;
                    }
                }
            }
        }
        return $this->regaliaPrices;
    }

    protected function regaliaPricesChild(): ?RegaliaPrices
    {
        $children = $this->cms()->helper('graph')->children($this['dso.id'], 'regalia-group-prices');
        return $children ? end($children) : null;
    }

    public function prices(): array
    {
        if ($this->regaliaPrices()) {
            return $this->regaliaPrices()['prices'];
        }
        $this->cms()->helper('notifications')->notice('No regalia-prices found for ' . $this->name(), 'no-regalia-prices-' . $this['dso.id']);
        return [
            'doctor' => [
                'hood' => 0,
                'gown' => 0,
                'cap' => 0,
                'tam' => 0,
                'package c/g' => 0,
                'package t/g' => 0
            ],
            'master' => [
                'hood' => 0,
                'gown' => 0,
                'cap' => 0,
                'tam' => 0,
                'package c/g' => 0,
                'package t/g' => 0
            ],
            'bachelor' => [
                'hood' => 0,
                'gown' => 0,
                'cap' => 0,
                'tam' => 0,
                'package c/g' => 0,
                'package t/g' => 0
            ]
        ];
    }

    public function orderPrice(RegaliaOrder $order): int
    {
        $level = preg_replace('/:.*$/', '', $order['degree.level']);
        return array_sum(
            array_map(
                function ($item) use ($level) {
                    return $this->orderItemPrice($item, $level);
                },
                $order->orders()
            )
        );
    }

    public function orderItemPrice(string $item, string $level): int
    {
        $level = strtolower($level);
        $item = str_replace('&nbsp;', ' ', $item);
        $item = strtolower($item);
        $prices = $this->prices();
        return @$prices[$level][$item] ?? 0;
    }

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
        } else {
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

    public function allOrderIDs(): array
    {
        if ($this->allOrderIDs === null) {
            $this->allOrderIDs = $this->cms()->helper('graph')->childIDs(
                $this['dso.id'],
                'regalia-group-order',
                1
            );
        }
        return $this->allOrderIDs;
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
