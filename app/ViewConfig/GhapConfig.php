<?php

namespace TLCMap\ViewConfig;

use TLCMap\Http\Helpers\HtmlFilter;
use TLCMap\Models\Collection;
use TLCMap\Models\Dataset;

/**
 * Class of common configurations for GHAP.
 */
class GhapConfig
{
    /**
     * Common blocked fields for place features.
     *
     * @return string[]
     *   List of field names.
     */
    public static function blockedFields()
    {
        return [
            "OBJECTID",
            "id",
            "title",
            "name",
            "udatestart",
            "udateend",
            "layer",
            "TLCMapLinkBack",
            "TLCMapDataset",
        ];
    }

    /**
     * Common property-label mapping.
     *
     * @return array
     *   The associative array containing property-label mapping.
     */
    public static function fieldLabels()
    {
        return [
            "placename" => "Place Name",
            "StartDate" => "Date Start",
            "EndDate" => "Date End",
            "datestart" => "Date Start",
            "dateend" => "Date End",
            "latitude" => "Latitude",
            "longitude" => "Longitude",
            "state" => "State",
            "lga" => "LGA",
            "feature_term" => "Feature Term",
            "source" => "Source",
            "original_data_source" => "Original Data Source",
            "linkback" => "Link Back",
            "type" => "Type",
            "description" => 'Description',
            "parish" => 'Parish',
            "warning" => 'Warning',
        ];
    }

    /**
     * Create the content of the info block for a dataset.
     *
     * @param Dataset $dataset
     *   The dataset object.
     * @return string
     *   The HTML content.
     */
    public static function createDatasetInfoBlockContent(Dataset $dataset)
    {
        $content = '';
        if (!empty($dataset->description)) {
            $content .= "<div>" . HtmlFilter::simple($dataset->description) . "</div>";
        }
        if (!empty($dataset->warning)) {
            $content .= '<div class="warning-message"><strong>Warning</strong><br>' . HtmlFilter::simple($dataset->warning) . '</div>';
        }
        $content .= '<p><a href="https://tlcmap.org/help/guides/ghap-guide/" target="_blank">Help</a> | <a href="https://tlcmap.org/help/guides/ghap-guide/" target="_blank">Share</a></p>';
        return $content;
    }

    /**
     * Create the content of the info block for a collection.
     *
     * @param Collection $collection
     *   The collection object.
     * @return string
     *   The HTML content.
     */
    public static function createCollectionInfoBlockContent(Collection $collection)
    {
        $content = '';
        if (!empty($collection->description)) {
            $content .= "<div>" . HtmlFilter::simple($collection->description) . "</div>";
        }
        if (!empty($collection->warning)) {
            $content .= '<div class="warning-message"><strong>Warning</strong><br>' . HtmlFilter::simple($collection->warning) . '</div>';
        }
        $content .= '<p><a href="https://tlcmap.org/help/guides/ghap-guide/" target="_blank">Help</a> | <a href="https://tlcmap.org/help/guides/ghap-guide/" target="_blank">Share</a></p>';
        return $content;
    }

    /**
     * Create the detail content of the list pane for a dataset.
     *
     * @param Dataset $dataset
     *   The dataset object.
     * @return string
     *   The HTML content.
     */
    public static function createDatasetListPaneContent(Dataset $dataset)
    {
        $content = '';
        if (!empty($dataset->description)) {
            $content .= "<div>" . HtmlFilter::simple($dataset->description) . "</div>";
        }
        if (!empty($dataset->warning)) {
            $content .= '<div class="warning-message"><strong>Warning</strong><br>' . HtmlFilter::simple($dataset->warning) . '</div>';
        }
        $url = $dataset->public ? url("publicdatasets/{$dataset->id}") : url("mydatasets/{$dataset->id}");
        $content .= '<p><a target="_blank" href="' . $url . '">View layer details</a></p>';
        return $content;
    }
}
