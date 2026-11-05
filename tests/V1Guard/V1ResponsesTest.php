<?php

namespace Tests\V1Guard;

use TLCMap\Http\Helpers\GeneralFunctions;
use Tests\V2\Support\Fixtures;
use Tests\V2\TestCase;

/**
 * Characterisation tests: record the v1 responses as golden files so later work can prove v1 is unchanged.
 * Regenerate a missing golden file with UPDATE_GOLDEN=1.
 */
class V1ResponsesTest extends TestCase
{
    private $firstPublicLayer;

    private function extended(string $text): string
    {
        return '<ExtendedData><Data name="description"><value><![CDATA[' . $text . ']]></value></Data>'
            . '<Data name="Parish"><value><![CDATA[St Mary]]></value></Data></ExtendedData>';
    }

    /**
     * The application stores a record's dates as text plus millisecond timestamps (udatestart/udateend),
     * computed on save; the fixture stores the same pair.
     */
    private function dates(string $start, string $end): array
    {
        return [
            'datestart' => $start,
            'dateend' => $end,
            'udatestart' => GeneralFunctions::dataToUnixtimestamp($start),
            'udateend' => GeneralFunctions::dataToUnixtimestamp($end),
        ];
    }

    private function buildFixture(): void
    {
        $this->firstPublicLayer = Fixtures::layer([
            'name' => 'Fixture Layer One',
            'description' => 'First public layer',
            'creator' => 'A. Creator',
            'publisher' => 'A. Publisher',
            'contact' => 'contact@example.org',
            'citation' => 'Creator (2020) Fixture Layer One',
            'language' => 'en',
            'license' => 'CC BY 4.0 licence text',
            'rights' => 'Rights held by the creator',
            'temporal_from' => '1850',
            'temporal_to' => '1900',
            'latitude_from' => -34,
            'latitude_to' => -32,
            'longitude_from' => 150,
            'longitude_to' => 152,
            'created' => '2020-02-02',
        ]);
        $second = Fixtures::layer(['name' => 'Fixture Layer Two', 'description' => 'Second public layer']);
        $private = Fixtures::layer(['name' => 'Fixture Private Layer', 'public' => false]);

        Fixtures::record([
            'dataset_id' => $this->firstPublicLayer, 'title' => 'Fixture Place Alpha', 'placename' => 'Fixture Place Alpha',
            'description' => 'Alpha description', 'lat' => -33.0, 'lon' => 151.0, 'state' => 'NSW', 'lga' => 'Fixture LGA',
            'feature_term' => 'Hill', 'source' => 'Fixture source', 'external_url' => 'http://example.org/alpha',
            'dataset_order' => 1,
            'extended_data' => $this->extended('Alpha contributor text'),
        ] + $this->dates('1856', '1856'));
        Fixtures::record([
            'dataset_id' => $this->firstPublicLayer, 'title' => 'Fixture Place', 'placename' => 'Fixture Place Beta',
            'description' => 'Beta description', 'lat' => -33.5, 'lon' => 151.5, 'state' => 'NSW',
            'dataset_order' => 2,
            'extended_data' => $this->extended('Beta contributor text'),
        ] + $this->dates('1856-03-04', '1856-03-04'));
        Fixtures::record([
            'dataset_id' => $second, 'title' => 'Fixture Place Gamma', 'placename' => 'Fixture Place Gamma',
            'description' => 'Gamma description', 'lat' => -20.0, 'lon' => 140.0, 'state' => 'QLD',
            'dataset_order' => 1, 'extended_data' => $this->extended('Gamma contributor text'),
        ]);
        Fixtures::record([
            'dataset_id' => $private, 'title' => 'Fixture Place Private', 'placename' => 'Fixture Place Private',
            'lat' => -33.2, 'lon' => 151.2, 'dataset_order' => 1,
        ] + $this->dates('1857', '1857'));
        Fixtures::record([
            'title' => 'Fixture Place', 'placename' => 'Fixture Place ANPS One',
            'lat' => -33.1, 'lon' => 151.1, 'state' => 'NSW', 'feature_term' => 'Creek',
        ]);
        Fixtures::record([
            'title' => 'Fixture Place ANPS Two', 'placename' => 'Fixture Place ANPS Two',
            'lat' => -10.0, 'lon' => 120.0, 'state' => 'WA',
        ]);
    }

    private function assertMatchesGolden(string $name, string $url): void
    {
        $this->buildFixture();
        if ($url === '{feed}') {
            $url = '/layers/' . $this->firstPublicLayer . '/json';
        }

        $response = $this->get($url);
        $response->assertStatus(200);
        $body = $response->baseResponse->getContent();
        $actual = json_decode($body, true);
        $this->assertNotNull($actual, 'v1 returned a body that is not JSON: ' . substr($body, 0, 200));

        // metadata.url is the request URL; it is the only volatile part, and the
        // placeholder keeps the golden file independent of the host.
        if (isset($actual['metadata']['url'])) {
            $actual['metadata']['url'] = '<url>';
        }

        $path = __DIR__ . '/golden/' . $name . '.json';
        if (!file_exists($path)) {
            if (getenv('UPDATE_GOLDEN') === '1') {
                file_put_contents($path, json_encode($actual, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
                $this->markTestIncomplete("Wrote golden file $name.json");
            }
            $this->fail("Missing golden file $name.json; run with UPDATE_GOLDEN=1.");
        }

        $expected = json_decode(file_get_contents($path), true);
        $this->assertSame($expected, $actual);
    }

    public function test_places_name_search()
    {
        $this->assertMatchesGolden('places_name_search', '/places?format=json&name=Fixture%20Place');
    }

    public function test_places_bbox_search()
    {
        $this->assertMatchesGolden('places_bbox_search', '/places?format=json&bbox=150,-34,152,-32');
    }

    public function test_places_date_search()
    {
        $this->assertMatchesGolden('places_date_search', '/places?format=json&datefrom=1850&dateto=1860');
    }

    public function test_layer_feed()
    {
        $this->assertMatchesGolden('layer_feed', '{feed}');
    }

    public function test_layer_catalogue()
    {
        $this->assertMatchesGolden('layer_catalogue', '/layers/json');
    }
}
