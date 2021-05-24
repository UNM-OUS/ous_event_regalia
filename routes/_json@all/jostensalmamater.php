<?php
$package->cache_public();
$package['response.ttl'] = 86400;
$package->makeMediaFile('results.json');
$q = $package['url.args.term'];
$definitive = $package['url.args._definitive'] == 'true';

if ($definitive) {
    // get single result from string
    $results = [$cms->helper('jostens')->locateInstitution($q)];
} else {
    // search spreadsheet
    $results = $cms->helper('jostens')->queryInstitution($q);
}

// convert format
$results = array_map(
    function ($e) {
        return [
            'label' => $e['name'],
            'desc' => $e['city'] . ', ' . $e['state'],
            'value' => $e['name'] . ', ' . $e['city'] . ', ' . $e['state'],
        ];
    },
    $results
);
if (!$results || $q == 'NOT FOUND') {
    array_unshift($results, [
        'label' => 'NOT FOUND',
        'desc' => 'We will contact you to determine the closest regalia color match available',
        'value' => 'NOT FOUND',
    ]);
}
if ($definitive) {
    $results = $results[0];
}

// return json encoded
echo json_encode($results);
