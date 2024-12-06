# GHAP (Gazetteer of Historical Australian Places)

## About

Search and contribute to placenames in Australia with the Gazetteer of Historical Australian Places (GHAP, 'The 
Gazetteer'). GHAP, for the first time, makes easily available aggregated data on 'all' placenames in
Australia, based on ANPS data, including historical names. We have cleaned up coordinates for more than two thirds of 
334,208 ANPS placenames and provided a user friendly search and filter interface and web service.

Search by exact or fuzzy match, for all places within a region, and apply filters to narrow results. Save results in 
standard formats. Because people come here to search for places, it's also a great place to contribute your place 
related research so that other can find it. Whether placenames are new to the GHAP or already there, add them in 
(multiple instances are 'attestations' for people to find out about through links to your research).

The GHAP can help answer that simple question - "What's here?"

It has two main aspects:

1. ANPS Data: Placename data aggregated by ANPS from official state and federal records and other sources. This is the 
'official' record of placenames.
2. User Contributions: Information about places contributed by researchers and community. This has several functions:
   - to enhance understanding and appreciation of meaning of place in Australia, or places important to Australians 
     (including overseas)
   - crowd source historical, indigenous and other placenames not already in the ANPS Gazetteer
   - crowd source ‘attestations’ or historical instances and mentions of placenames
   - to associate places with their many meanings
   - linking to source information and other datasets
   - provide a spatio-temporal index to humanities research and culture in and about Australia
   - provide access to this information with search and filter user interfaces, web services and visualisations and 
     compatibility with other spatiotemporal systems

## Developers' notes

### Displaying HTML content/properties

__CAUTION__: ALWAYS filter the HTML provided by users before displaying.

The following properties allow user to input HTML:

- `description` and `source` on place record.
- `description`, `citation`, `rights`, and `warning` on layer record.
- `description`, `citation`, `rights`, and `warning` on multilayer record.

To avoid potential XSS attacks, the values of these properties MUST go through the
`\TLCMap\Http\Helpers\HtmlFilter::simple()` method before they get displayed in the page. 
