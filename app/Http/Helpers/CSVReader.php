<?php

namespace TLCMap\Http\Helpers;

class CSVReader
{
    /**
     * Read an CSV file data into an array.
     *
     * @param string $file
     *   The path of the CSV file.
     * @return array
     *   Each row of the CSV file will be an element in the result array. Each element will be an associative array
     *   with the column header as the key and the cell value as the value.
     */
    public static function read($file)
    {
        $file = fopen($file, 'r');

        $headers = fgetcsv($file); // read the first line as headers

        $rows = [];
        while (($data = fgetcsv($file)) !== false) {
            $row = array();
            foreach ($headers as $index => $header) {
                $value = $data[$index];
                $row[$header] = $value !== '' ? $value : null;
            }
            $rows[] = $row;
        }

        fclose($file);
        return $rows;
    }
}
