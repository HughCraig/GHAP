<?php

namespace TLCMap\Models;

use Illuminate\Database\Eloquent\Model;

class ParseTime extends Model
{

    // Table name, optional if follows Laravel convention
    protected $table = 'tlcmap.parse_times';

    // Define fillable fields
    protected $fillable = ['text_size', 'parse_time'];

    /**
     * Calculate the linear regression parameters (slope and intercept).
     * 
     * @param $data
     * @return array
     */
    public static function calculateLinearRegression($data)
    {
        $n = count($data);
        $sumX = $data->sum('text_size');
        $sumY = $data->sum('parse_time');
        $sumXY = $data->reduce(function ($carry, $item) {
            return $carry + ($item->text_size * $item->parse_time);
        }, 0);
        $sumX2 = $data->reduce(function ($carry, $item) {
            return $carry + ($item->text_size * $item->text_size);
        }, 0);

        // Calculate slope (m) and intercept (b)
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        return [
            'slope' => $slope,
            'intercept' => $intercept,
        ];
    }

    /**
     * Predict parse time based on text size using the linear regression model.
     * 
     * @param float $textSize
     * @param float $slope
     * @param float $intercept
     * @return float
     */
    public static function predictTime($textSize, $slope, $intercept)
    {
        return $slope * $textSize + $intercept;
    }

    /**
     * Store the text size and parse time into the database.
     * 
     * @param array $data
     * @return void
     */
    public static function storeParseTime($data)
    {
        self::create($data);
    }

    /**
     * Get all stored parse time data.
     * 
     * @return \Illuminate\Support\Collection
     */
    public static function getAllData()
    {
        return self::all();
    }
}