<?php

namespace App\Support\Traits;

/**
 * Shared validation rules for geo-content controllers (Country, City, Place).
 * Covers the rule blocks duplicated across store/update on those entities:
 * media_gallery, travel_info, seasons, events, additional_info, faqs, seo.
 *
 * Entity-specific fields (name, slug, code, parent foreign keys, location_details)
 * stay in each controller because they diverge per entity.
 */
trait GeoContentRules
{
    /**
     * Shared validation rules for geo-content collections.
     *
     * @param  string|null  $tablePrefix  When provided (e.g. 'country', 'city', 'place'),
     *                                    adds `*.id` exists rules pointing at
     *                                    `{prefix}_seasons|events|additional_infos|faqs`
     *                                    so update endpoints can match existing children.
     */
    protected function geoContentRules(?string $tablePrefix = null): array
    {
        $rules = [
            'media_gallery' => 'nullable|array',

            'travel_info.airport' => 'nullable|string|max:255',
            'travel_info.public_transportation' => 'nullable|array',
            'travel_info.taxi_available' => 'boolean',
            'travel_info.rental_cars_available' => 'boolean',
            'travel_info.hotels' => 'boolean',
            'travel_info.hostels' => 'boolean',
            'travel_info.apartments' => 'boolean',
            'travel_info.resorts' => 'boolean',
            'travel_info.visa_requirements' => 'nullable|string|max:5000',
            'travel_info.best_time_to_visit' => 'nullable|string|max:5000',
            'travel_info.travel_tips' => 'nullable|string|max:5000',
            'travel_info.safety_information' => 'nullable|string|max:5000',

            'seasons' => 'nullable|array',
            'seasons.*.name' => 'nullable|string|max:120',
            'seasons.*.months' => 'nullable|array',
            'seasons.*.weather' => 'nullable|string|max:5000',
            'seasons.*.activities' => 'nullable|array',

            'events' => 'nullable|array',
            'events.*.name' => 'nullable|string|max:120',
            'events.*.type' => 'nullable|array',
            'events.*.date' => 'nullable|date',
            'events.*.location' => 'nullable|string|max:255',
            'events.*.description' => 'nullable|string|max:5000',

            'additional_info' => 'nullable|array',
            'additional_info.*.title' => 'required|string|max:200',
            'additional_info.*.content' => 'required|string|max:5000',

            'faqs' => 'nullable|array',
            'faqs.*.question' => 'required|string|max:200',
            'faqs.*.answer' => 'required|string|max:5000',

            'seo.meta_title' => 'nullable|string|max:200',
            'seo.meta_description' => 'nullable|string|max:500',
            'seo.keywords' => 'nullable|string|max:500',
            'seo.og_image_url' => 'nullable|url',
            'seo.canonical_url' => 'nullable|url',
            'seo.schema_type' => 'nullable|string|max:50',
            'seo.schema_data' => 'nullable|array',
        ];

        if ($tablePrefix !== null) {
            $rules['seasons.*.id'] = "nullable|integer|exists:{$tablePrefix}_seasons,id";
            $rules['events.*.id'] = "nullable|integer|exists:{$tablePrefix}_events,id";
            $rules['additional_info.*.id'] = "nullable|integer|exists:{$tablePrefix}_additional_infos,id";
            $rules['faqs.*.id'] = "nullable|integer|exists:{$tablePrefix}_faqs,id";
        }

        return $rules;
    }
}
