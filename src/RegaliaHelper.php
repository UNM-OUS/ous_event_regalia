<?php

namespace Digraph\Modules\ous_event_regalia;

use Digraph\Helpers\AbstractHelper;
use Digraph\Modules\ous_event_management\Signup;

class RegaliaHelper extends AbstractHelper
{
    public function orderDisplay($orderOrSignup, $degree = true)
    {
        $cells = [];
        $title = null;
        $out = '<div class="regalia-order-card digraph-card">';
        $hatType = 'cap';
        $bandColor = null;
        $liningColor = null;
        $chevronColor = null;
        $order = null;
        if ($orderOrSignup instanceof RegaliaOrder) {
            $title = 'Type/name: ' . $orderOrSignup->name();
            $hatType = $orderOrSignup->hatType();
            $data = $orderOrSignup->get();
            $bandColor = $orderOrSignup->bandColor();
            $liningColor = $orderOrSignup->liningColor();
            $chevronColor = $orderOrSignup->chevronColor();
            $order = implode('; ', $orderOrSignup->orders());
        } elseif ($orderOrSignup instanceof Signup) {
            $title = 'Regalia for: ' . $orderOrSignup->name();
            $data = $orderOrSignup['regalia'];
        } else {
            $data = $orderOrSignup;
        }
        if (!is_array($data)) {
            throw new \Exception("Invalid regalia order data, should be a RegaliaOrder, Signup, or array");
        }
        if ($title) {
            $out .= '<strong>' . $title . '</strong>';
        }
        // parts
        $data['parts'] = @$data['parts'] ?? @$data['order']['parts'];
        $cells['Order'] = $order ?? implode('; ', $data['parts']);
        $hat = @in_array('hat', $data['parts']);
        $hood = @in_array('hood', $data['parts']);
        $robe = @in_array('robe', $data['parts']);
        // sizing
        if ($robe) {
            $cells['Gender'] = $data['size']['gender'];
            $cells['Height'] = $data['size']['height']['ft'] . '\'' . $data['size']['height']['in'] . '"';
            $cells['Weight'] = $data['size']['weight'];
        }
        if ($hatType == 'cap') {
            $cells['Hat size'] = 'ELASTIC';
        } else {
            $cells['Hat size'] = $data['size']['hat'];
        }
        // degree/almamater
        if ($degree) {
            $cells['Degree level'] = preg_replace('/:.*$/', '', $data['degree']['level']);
            $cells['Degree field'] = $data['degree']['field'];
            $cells['Alma mater'] = $data['degree']['institution'];
        }
        // colors
        $cells['Band Color'] = $bandColor;
        $cells['Lining Color'] = $liningColor;
        $cells['Chevron Color'] = $chevronColor;
        // check if anything is "NOT FOUND"
        if ($data['degree']['institution'] == 'NOT FOUND' || $data['degree']['field'] == 'NOT FOUND') {
            $class = 'highlighted-error';
        }
        // print dl
        $out .= '<dl>';
        foreach ($cells as $k => $v) {
            if ($v) {
                if ($v == 'NOT FOUND' || $v == '?') {
                    $class = ' class="notification notification-error" style="margin:0;"';
                } else {
                    $class = '';
                }
                $out .= "<dt>$k</dt><dd$class>$v</dd>";
            }
        }
        $out .= '</dl>';
        $out .= '</div>';
        return $out;
    }

    public function orderTable($orders)
    {
        $out = '<table class="regalia-order-table">';
        $out .= '<tr>';
        $out .= '<th>' . implode('</th><th>', [
            'Type/Name',
            'Last Name',
            'First Name',
            'Hat',
            'Hood',
            'Robe',
            'Size',
            'Degree',
            'Field',
            'School',
        ]) . '</th>';
        $out .= '</tr>';
        $rows = array_map(
            function ($order) {
                return $this->orderTableCells($order);
            },
            $orders
        );
        usort(
            $rows,
            function ($a, $b) {
                // last name
                if ($a[2] && !$b[2]) {
                    return -1;
                }
                if (!$a[2] && $b[2]) {
                    return 1;
                }
                if ($d = strcasecmp($a[2], $b[2])) {
                    return $d;
                }
                // first name
                if ($d = strcasecmp($a[3], $b[3])) {
                    return $d;
                }
                // first column
                if ($d = strcasecmp(strip_tags($a[1]), strip_tags($b[1]))) {
                    return $d;
                }
                return 0;
            }
        );
        foreach ($rows as $cells) {
            $class = array_shift($cells);
            $out .= '<tr class="' . $class . '">';
            $out .= '<td>' . implode('</td><td>', $cells) . '</td>';
            $out .= '</tr>';
        }
        $out .= '</table>';
        return $out;
    }

    public function orderTableCells(RegaliaOrder $order)
    {
        $cache = $this->cms->cache();
        $cid = 'regaliahelper.ordertablecells.' . $order['dso.id'];
        $citem = $cache->getItem($cid);
        if (!$citem->isHit()) {
            $citem->expiresAfter(600 + random_int(0, 600));
            $tags = [$order['dso.id']];
            if ($order->signup()) {
                $tags[] = $order->signup()['dso.id'];
            }
            $citem->tag($tags);
            $citem->set($this->doOrderTableCells($order));
            $cache->save($citem);
        }
        return $citem->get();
    }

    protected function doOrderTableCells(RegaliaOrder $order)
    {
        $cells = [''];
        // parts
        $hat = @in_array('hat', $order['parts']);
        $hood = @in_array('hood', $order['parts']);
        $robe = @in_array('robe', $order['parts']);
        // link/name
        $cells[1] = $order->link();
        if ($order->signup() && $order->signup()->contactInfo()) {
            $cells[1] .= '<br>' . $order->signup()->contactInfo()->email();
        }
        $cells[] = $order->lastName();
        $cells[] = $order->firstName();
        // parts
        $cells[] = $hat ? 'Y' : '';
        $cells[] = $hood ? 'Y' : '';
        $cells[] = $robe ? 'Y' : '';
        // sizing
        $size = '';
        if ($robe) {
            $size .= $order['size.gender'];
            $size .= ', ' . $order['size.height.ft'] . '\'' . $order['size.height.in'];
            $size .= ', ' . $order['size.weight'];
        }
        if ($order->hatType() == 'cap') {
            $size .= '<br>Hat: ' . 'ELASTIC';
        } else {
            $size .= '<br>Hat: ' . $order['size.hat'];
        }
        $cells[] = $size;
        // degree/almamater
        $cells[] = preg_replace('/:.*$/', '', $order['degree.level']);
        $cells[] = $order['degree.field'];
        $almamater = explode(', ', $order['degree.institution']);
        $cells[] = $almamater[0];
        // check if anything is "NOT FOUND"
        if (in_array('NOT FOUND', $cells)) {
            $cells[0] = 'highlighted-error';
        } else {
            // check for other highlight cases
            if ($order->overridesSignup()) {
                $cells[0] = 'highlighted-notice';
                if ($order['save_override']) {
                    $cells[0] = 'highlighted-confirmation';
                }
            }
        }
        return $cells;
    }
}
