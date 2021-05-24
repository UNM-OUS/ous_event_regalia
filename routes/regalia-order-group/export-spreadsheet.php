<?php
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

$package->cache_noStore();

/** @var \Digraph\Modules\ous_event_regalia\RegaliaOrderGroup */
$group = $package->noun();

/** @var \Digraph\Media\MediaHelper */
$media = $cms->helper('media');
$asset = $media->create(
    $group->name() . '.xlsx',
    function ($dest) use ($group, $cms) {
        $writer = WriterEntityFactory::createXLSXWriter();
        $cms->helper('filesystem')->put('', $dest, true);
        $writer->openToFile($dest);
        // add headers
        $headers = [
            'Type/Name',
            'Last Name',
            'First Name',
            'Gender',
            'Height',
            'Weight',
            'Hat Size',
            'Degree',
            'Field',
            'School',
            'City',
            'State',
            'Band Color',
            'Lining Color 1',
            'Chevron Color 1',
            'Order',
        ];
        $writer->addRow(
            WriterEntityFactory::createRow(
                array_map(
                    'Box\Spout\Writer\Common\Creator\WriterEntityFactory::createCell',
                    $headers
                )
            )
        );
        // loop through orders
        foreach ($group->assignedOrders() + $group->extraOrders() as $order) {
            $cells = [];
            $class = '';
            // parts
            $hat = @in_array('hat', $order['parts']);
            $hood = @in_array('hood', $order['parts']);
            $robe = @in_array('robe', $order['parts']);
            // link/name
            $cells[] = $order->name();
            $cells[] = $order->lastName();
            $cells[] = $order->firstName();
            // sizing
            if ($robe) {
                $cells[] = $order['size.gender'];
                $cells[] = $order['size.height.ft'] . '\'' . $order['size.height.in'];
                $cells[] = $order['size.weight'];
            } else {
                $cells[] = '';
                $cells[] = '';
                $cells[] = '';
            }
            if ($order->hatType() == 'cap') {
                $cells[] = 'ELASTIC';
            } else {
                $cells[] = $order['size.hat'];
            }
            // degree/almamater
            $cells[] = preg_replace('/:.*$/', '', $order['degree.level']);
            $cells[] = $order['degree.field'];
            $almamater = explode(', ', $order['degree.institution']);
            $cells[] = $almamater[0];
            $cells[] = @$almamater[1] ?? '?';
            $cells[] = @$almamater[2] ?? '?';
            // colors
            $cells[] = $order->bandColor();
            $cells[] = $order->liningColor();
            $cells[] = $order->chevronColor();
            // order type
            $orders = $order->orders();
            $cells[] = implode(
                ';' . PHP_EOL,
                array_map(
                    function ($e) {
                        return str_replace('&nbsp;', ' ', $e);
                    },
                    $orders
                )
            );
            // check if anything is "NOT FOUND"
            // if (in_array('NOT FOUND', $cells)) {
            //     $class = 'highlighted-error';
            // } else {
            //     // check for other highlight cases
            //     if ($order->overridesSignup()) {
            //         $class = 'highlighted-notice';
            //         if ($order['save_override']) {
            //             $class = 'highlighted-confirmation';
            //         }
            //     }
            // }
            $writer->addRow(
                WriterEntityFactory::createRow(
                    array_map(
                        'Box\Spout\Writer\Common\Creator\WriterEntityFactory::createCell',
                        array_map(
                            '\strval',
                            $cells
                        )
                    )
                )
            );
        }
        // close file
        $writer->close();
    },
    ['exported spreadsheet', $group['dso.id']],
    300
);

$package->redirect($asset['url']);
