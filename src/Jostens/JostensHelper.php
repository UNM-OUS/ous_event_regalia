<?php
namespace Digraph\Modules\event_regalia\Jostens;

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Digraph\Helpers\AbstractHelper;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

class JostensHelper extends AbstractHelper
{
    protected function cache(): TagAwareAdapterInterface
    {
        return $this->cms->cache();
    }

    public function queryInstitution(string $query): array
    {
        return $this->querySheet(__DIR__ . '/institutions.xlsx', $query, ['name', 'city']);
    }

    public function locateInstitution(string $query): ?array
    {
        $query = preg_split('/, */', strtoupper($query));
        return $this->locateRow(
            __DIR__ . '/institutions.xlsx',
            [
                'name' => $query[0],
                'city' => @$query[1],
                'state' => @$query[2],
            ]
        );
    }

    public function queryDegree(string $query): array
    {
        return $this->querySheet(__DIR__ . '/degrees.xlsx', $query, ['degree']);
    }

    public function locateDegree(string $query): ?array
    {
        $query = strtoupper($query);
        return $this->locateRow(
            __DIR__ . '/degrees.xlsx',
            [
                'degree' => $query,
            ]
        );
    }

    protected function locateRow($file, $q): ?array
    {
        $citem = $this->cache()->getItem(md5(serialize(['jostens', $file, $q])));
        if (!$citem->isHit()) {
            $citem->expiresAfter(86400);
            $citem->set($this->doLocateRow($file, $q));
            $this->cache()->save($citem);
        }
        return $citem->get();
    }

    protected function doLocateRow($file, $q): ?array
    {
        $reader = ReaderEntityFactory::createReaderFromFile($file);
        $reader->open($file);
        $headers = null;
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->getCells();
                if (!$headers) {
                    $headers = array_flip(array_map(function ($cell) {
                        return strtolower(trim($cell->getValue()));
                    }, $cells));
                } else {
                    // row data keyed by headers in first row
                    $rowData = array_map(function ($i) use ($cells) {return @$cells[$i]->getValue();}, $headers);
                    $count = count($q);
                    if (count(array_intersect_assoc($q, $rowData)) == $count) {
                        return $rowData;
                    }
                }
            }
        }
        return null;
    }

    protected function querySheet($file, $query, $cols): array
    {
        $results = [];
        $reader = ReaderEntityFactory::createReaderFromFile($file);
        $reader->open($file);
        $headers = null;
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->getCells();
                if (!$headers) {
                    $headers = array_flip(array_map(function ($cell) {
                        return strtolower(trim($cell->getValue()));
                    }, $cells));
                } else {
                    // row data keyed by headers in first row
                    $rowData = array_map(function ($i) use ($cells) {return @$cells[$i]->getValue();}, $headers);
                    $score = 0;
                    foreach ($cols as $c) {
                        $pos = stripos($rowData[$c], $query);
                        if ($pos !== false) {
                            $score += $pos === 0 ? 2 : 1;
                        }
                    }
                    if ($score) {
                        $rowData['sort_score'] = $score;
                        $results[] = $rowData;
                    }
                }
            }
        }
        usort($results, function ($a, $b) {
            return $b['sort_score'] - $a['sort_score'];
        });
        return $results;
    }
}
