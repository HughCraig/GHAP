<?php
/*
 * Benjamin McDonnell
 * For TLCMap project, University of Newcastle
 * 
 * Some helper methods for the RegisterController
 * Converting the output into chunks, geoJson, KML, etc
 */

namespace TLCMap\Http\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GeneralFunctions
{
    /**
     * NO LONGER USED AS IT IS MORE CONVENIENT TO CONVERT TO STRING AND RETURN IT USING dateMatchesRegexAndConvertString
     *
     * {$dateString} is a STRING representing a DATE
     * Return true if date matches regex, else false
     *
     * accepted formats (6 ISO friendly, 2 Excel friendly):
     *      - Year  (accepts negatives, leading zeroes, 1 to n characters, cannot be 0)                             Results in an array of size 2: [0] is full string, [1] is Year
     *      - Year-Month yyyy-mm format
     *      - Year-Month-Day (as above, and day/month cannot be single digits... eg: 1993-02-12 not 1993-2-12)      Results in an array of size 5: [1] is Year, [3] is Month, [4] is Day
     *      - Year-Month-DayThh      AS above but with time in hours                                                Results in an array of size 7: [1] is Year, [3] is Month, [4] is Day, [6] is Hour
     *      - Year-Month-DayThh:mm      AS above but with time in hours and minutes, minutes must be 2 digits       Results in an array of size 9: [1] is Year, [3] is Month, [4] is Day, [6] is Hour, [8] is minute
     *      - Year-Month-DayThh:mm:ss (as above but with time)      seconds must be 2 digits                        Results in an array of size 11: [1] is Year, [3] is Month, [4] is Day, [6] is Hour, [8] is Minute, [10] is Second
     *      - Year-Month-DayThh:mm:ss.sss (as above but with decimal seconds)                                       Results in an array of size 12: [1] is Year, [3] is Month, [4] is Day, [6] is Hour, [8] is Minute, [10] is Second, [11] is decimal seconds
     *      - Day/Month/Year (year month and day rules as above)                                                    Results in an array of size 4: [1] is Day [2] is Month, [3] is Year
     *      - Day/Month/Year hh:mm (as above but with time, no seconds as this is how excel exports csv)            Results in an array of size 8: [1] is Day, [2] is Month, [3] is Year, [6] is Hour, [7] is Minute
     */
    // public static function dateMatchesRegex($dateString) {
    //     if (preg_match(
    //         '/^(-?[0-9]*[1-9]+0*)(-(0?[1-9]|1[012])-(0?[1-9]|[12][0-9]|3[01])(T(0?[0-9]|1[0-9]|2[0-3])(:(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])(:(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])([.][0-9]+)?)?)?)?)?$/',
    //         $dateString) ) return true;
    //     if (preg_match(
    //         '/^(0?[1-9]|[12][0-9]|3[01])\/(0?[1-9]|1[012])\/(-?[0-9]*[1-9]+0*)( ((0?[0-9]|1[0-9]|2[0-3]):(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])))?$/',
    //         $dateString) ) return true;
    //     return false;
    // }

    //As above but it will return the actual STRING
    public static function dateMatchesRegexAndConvertString($dateString)
    {

        if (empty($dateString)) {
            return ""; // be forgiving of some rows not having dates.
        }
        // if someone puts in dates like 00/00/1862 or 1862-00-00
        if (preg_match('/[1-9]+-00-00/', $dateString)) {
            return substr($dateString, 0, strpos($dateString, "-"));
        }
        if (preg_match('/00\/00\/[1-9]+/', $dateString)) {
            return strstr($dateString, "/", 1);
        }
        if (preg_match('/^([0-9]{4})-(0[1-9]|1[012])$/', $dateString)) {
            return $dateString;
        } //yyyy-mm
        if (preg_match(
            '/^(-?[0-9]*[1-9]+0*)(-(0?[1-9]|1[012])-(0?[1-9]|[12][0-9]|3[01])(T(0?[0-9]|1[0-9]|2[0-3])(:(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])(:(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])([.][0-9]+)?)?)?)?)?$/',
            $dateString)) {
            return $dateString;
        } //return the string
        if (preg_match(
            '/^(0?[1-9]|[12][0-9]|3[01])\/(0?[1-9]|1[012])\/(-?[0-9]*[1-9]+0*)( ((0?[0-9]|1[0-9]|2[0-3]):(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])))?$/',
            $dateString, $out)) {

            return self::slashDateArrayToDashDateString($out);
        } //return the converted string

        return -1; //return false
    }

    /**
     * $a is the date from/to sent via $parameters   (the date we inputted into the search form)
     * $b is the date in the database that we are checking
     *
     * $fail_value is a value that will fail on boolean check above this function. This allows the calling function to specify what value they would like returned if this function fails
     *  eg return $that->dateCompare($parameters['dateto'], $v->datestart, -1) >= 0
     *      if we return NULL or FALSE, the comparison FALSE >= 0 is TRUE which we dont want for sorting purposes
     *      if we instead return -1, the comparison -1 >= 0 is FALSE which is ideal.
     *          Very situational and hacky, just saves us a null check...
     *
     * Dates in this context are TEXT (string) objects of the format (-YYYY)YYYY-MM-DD Where the negative is for BC dates, and dates can be 4-8 digits long
     *  eg      1923-12-23   -20000-01-01    etc
     *
     * We should NOT need to check a complex regex match here because we are enforcing all dataitem uploads follow a specific regex AND enforcing the form input follows the regex before this section
     *
     * Returns 1 if a is a date that comes AFTER b, -1 if a is a date that comes BEFORE b, or 0 if they are the same date, returns NULL if $a or $b are improper format
     */
    public static function dateCompare($a, $b, $fail_value = null)
    {
        if ($a == null || $b == null) return $fail_value;

        $reg1 = '/^(-?\d+)(-(\d+)-(\d+)(T(\d+(:(\d+)(:(\d+)([.]\d+)?)?)?))?)?$/'; //[1] is Year, [3] is Month, [4] is Day, [6] is Hour, [8] is Minute, [10] is Second, [11] is decimal seconds
        $reg2 = '/^(\d+)\/(\d+)\/(-?\d+)( ((\d+):(\d+)))?$/'; //[1] is day, [2] is month, [3] is year, [6] is hour, [7] is minute

        preg_match($reg1, $a, $aout);                       //match a to first regex
        if (!$aout) {                                       //if it does not match
            preg_match($reg2, $a, $aout);                   //try match to 2nd regex
            if (!$aout) return $fail_value;                 //if it still doesn't match, return fail
            $aout = self::slashDateArrayToDashDateArray($aout);   //convert to dash based
        }
        preg_match($reg1, $b, $bout);       //same for b
        if (!$bout) {
            preg_match($reg2, $b, $bout);
            if (!$bout) return $fail_value;
            $bout = self::slashDateArrayToDashDateArray($bout);
        }

        if (strlen($aout[1]) == 2) $aout[1] = "20" + $aout[1]; //if 2 digit date with no leading zeroes, assume they mean 21st century. (eg) 20 => 2020, 0020 => 20
        if (strlen($bout[1]) == 2) $bout[1] = "20" + $bout[1];

        //Compare loop
        for ($i = 1; $i < 12; $i++) {
            if ($i == 2 || $i == 5 || $i == 7 || $i == 9) continue; //skip useless values
            if (!array_key_exists($i, $aout) && array_key_exists($i, $bout)) return -1; //if one has more accuracy then it comes after, eg 2020 is before 2020-01-01
            if (!array_key_exists($i, $bout) && array_key_exists($i, $aout)) return 1;

            //not both null, 
            if (array_key_exists($i, $aout) && array_key_exists($i, $bout)) {
                if (floatval($aout[$i]) < floatval($bout[$i])) return -1; //if year is BEFORE or AFTER the compare value, we don't need to bother checking month, day, etc
                if (floatval($aout[$i]) > floatval($bout[$i])) return 1;
            } else return 0; //both null therefor equal

        }
        return 0; //they must be equal
    }

    //assumes array of at least len 4
    static function slashDateArrayToDashDateArray($arr)
    {
        $out = [null, $arr[3], null, $arr[2], $arr[1]];
        if (array_key_exists(6, $arr)) array_merge($out, [null, $arr[6], null, $arr[7]]); //null then hour then null then min
        return $out;
    }

    static function slashDateArrayToDashDateString($arr)
    {
        $out = $arr[3] . "-" . $arr[2] . "-" . $arr[1];
        if (array_key_exists(6, $arr)) $out .= "T" . $arr[6] . ":" . $arr[7];
        return $out;
    }

    public static function dateCompareOld($a, $b, $fail_value = null)
    {
        //Check it fits the desired REGEX, split into sections (y m d) return null if not proper format
        if ($a == null || $b == null) return $fail_value;

        preg_match('/^(-?[^-]*)(-([^-]*)-([^-]*))?/', $a, $aout); //doesnt handle time yet, 0 is full match, 1 is year, 2 is m and d, 3 is m, 4 is d
        preg_match('/^(-?[^-]*)(-([^-]*)-([^-]*))?/', $b, $bout);

        if (!$aout || !$bout) return $fail_value;

        if ((float)$aout[1] == (float)$bout[1]) {
            if (array_key_exists(2, $aout) && array_key_exists(2, $bout)) {
                if ((float)$aout[3] == (float)$bout[3]) {
                    if ((float)$aout[4] == (float)$bout[4]) return 0; //if a == b return 0
                    return ((float)$aout[4] > (float)$bout[4]) ? 1 : -1; //if a > b return 1, else return -1
                }
                return ((float)$aout[3] > (float)$bout[3]) ? 1 : -1; //if a > b return 1, else return -1
            } else return 0; //if years match and one or more of the parameters is JUST a year, return equality
        }
        return ((float)$aout[1] > (float)$bout[1]) ? 1 : -1; //if a > b return 1, else return -1        
    }

    //Replace all non-alphanumeric characters with underscores
    public static function replaceWithUnderscores($str){
        return preg_replace('/[^a-zA-Z0-9]/', '_', $str);
    }
    
    /**
     * Returns the median of an array of numbers
     * 
     * @param array $arr The array of numbers
     * @return float The median of the array
     */
    public static function getMedian($arr) {
        sort($arr);
        $count = count($arr);
        $middleIndex = floor($count / 2);
    
        if ($count % 2) {
            return $arr[$middleIndex];
        } else {
            return ($arr[$middleIndex - 1] + $arr[$middleIndex]) / 2;
        }
    }

    /**
     * Returns the standard deviation of an array of numbers
     * 
     * @param array $arr The array of numbers
     * @return float The standard deviation of the array
     */
    public static function getStandardDeviation($arr) {
        $mean = array_sum($arr) / count($arr);
        $variance = 0.0;
        foreach ($arr as $i) {
            $variance += pow($i - $mean, 2);
        }
        return (float)sqrt($variance / count($arr));
    }

    /**
     * Validates a user-uploaded image file.
     * Check for file size and type.
     * 
     * @param UploadedFile $file The image file to be validated.
     * @return bool Returns true for valid, false otherwise.
     */
    public static function validateUserUploadImage($file)
    {
        $maxSize = config('app.max_upload_image_size');
        // Validate file size
        if ($file->getSize() > $maxSize) {
            return false;
        }

        // Validate file type
        if (!$file->isValid() || !$file->isFile() || !$file->guessExtension() || !in_array($file->guessExtension(), ['jpeg', 'jpg', 'png', 'gif', 'bmp', 'svg' , 'webp'])) {
            return false;
        }

        return true;
    }

    public static function validateUserUploadText($file)
    {

        $maxSize = config('app.text_max_upload_file_size');
        $allowedExtensions = config('app.allowed_text_file_types');

        // Check the file size
        if ($file->getSize() > $maxSize) {
            return false; 
        }

        // Check the file extension
        $extension = $file->getClientOriginalExtension();
        if (!in_array(strtolower($extension), $allowedExtensions)) {
            return false; 
        }

        // Get the file content as an array of lines
        $fileContent = file($file->getRealPath(), FILE_IGNORE_NEW_LINES);

        // Ensure the file is valid UTF-8
        if (!mb_detect_encoding(implode("\n", $fileContent), 'UTF-8', true)) {
            return false; // File is not valid UTF-8 text
        }

        // Join the lines into a single string with \n for each line break
        $fileContentString = implode("\n", $fileContent);

        return json_encode($fileContentString); 
    }


    /**
     * Calulates the distance between two points by coordinates
     * 
     * @param float $lat1 Latitude of the first point
     * @param float $lon1 Longitude of the first point
     * @param float $lat2 Latitude of the second point
     * @param float $lon2 Longitude of the second point
     * @return float The distance between the two points in Kilometers
     */
    public static function getDistance($lat1, $lon1, $lat2, $lon2) {

        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;

        return ($miles * 1.609344);
        
    }

}