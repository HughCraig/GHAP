<?php

namespace TLCMap\ViewConfig;

use TLCMap\Http\Helpers\HtmlFilter;
use TLCMap\Models\Collection;
use TLCMap\Models\Dataset;
use TLCMap\Models\SavedSearch;
use Illuminate\Support\Facades\Storage;

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
            "quantity",
            "logQuantity",
            "route_id",
            "route_original_id",
            "stop_idx",
            "route_title",
            "route_description",
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
            "quantity" => 'Quantity',
            "logQuantity" => "Log of Quantity",
            "stop_idx" => "Route Stop Number",
            "route_id" => "Route ID",
            "route_original_id" => "Route Original ID",
            "route_title" => 'Route Title',
            "route_description" => "Route Description",
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

        if (!empty($dataset->image_path)) {
            $imageUrl = Storage::disk('public')->url('images/' . $dataset->image_path);
            $content .= '<img src="' . $imageUrl . '" alt="Layer Image">';
        }

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
     * Create the content of the info block for a visiting an private layer.
     *
     * @return string
     *   The HTML content.
     */
    public static function createRestrictedDatasetInfoBlockContent()
    {
        $content = '';
        $content .= '<div class="warning-message"><strong>Warning</strong><br>This map either does not exist or has been set to "private" and therefore cannot be displayed.</div>';
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

        if (!empty($collection->image_path)) {
            $imageUrl = Storage::disk('public')->url('images/' . $collection->image_path);
            $content .= '<img src="' . $imageUrl . '" alt="Collection Image">';
        }

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

    /**
     * Create the detail content of the list pane for a dataset.
     *
     * @param SavedSearch $savedSearch
     *   The saved search object.
     * @return string
     *   The HTML content.
     */
    public static function createSavedSearchListPaneContent(SavedSearch $savedSearch)
    {
        $content = '';
        if (!empty($savedSearch->description)) {
            $content .= "<div>" . HtmlFilter::simple($savedSearch->description) . "</div>";
        }
        if (!empty($savedSearch->warning)) {
            $content .= '<div class="warning-message"><strong>Warning</strong><br>' . HtmlFilter::simple($savedSearch->warning) . '</div>';
        }
        $url = url("/places") . $savedSearch->query;
        $content .= '<p><a target="_blank" href="' . $url . '">View saved search details</a></p>';
        return $content;
    }
}
