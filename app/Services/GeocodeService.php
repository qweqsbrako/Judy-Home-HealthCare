<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GeocodeService
{
    private string $apiKey;
    private string $baseUrl = 'https://maps.googleapis.com/maps/api/geocode/json';

    public function __construct()
    {
        $this->apiKey = config('services.google.geocoding_api_key', 'AIzaSyAa6jl4duYHgSa0o3dZh1yUvZ9dUtOAMEU');
    }

    /**
     * Convert coordinates to a human-readable address
     *
     * @param float $latitude
     * @param float $longitude
     * @return string|null
     */
    public function getAddressFromCoordinates(float $latitude, float $longitude): ?string
    {
        // Create a cache key based on coordinates (rounded to 4 decimal places for caching)
        $cacheKey = 'geocode_' . round($latitude, 4) . '_' . round($longitude, 4);

        // Check cache first (cache for 30 days)
        return Cache::remember($cacheKey, now()->addDays(30), function () use ($latitude, $longitude) {
            try {
                $response = Http::timeout(10)->get($this->baseUrl, [
                    'latlng' => "{$latitude},{$longitude}",
                    'key' => $this->apiKey,
                    'language' => 'en',
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    if ($data['status'] === 'OK' && !empty($data['results'])) {
                        // Get the first result's formatted address
                        return $data['results'][0]['formatted_address'];
                    }

                    Log::warning('Geocoding API returned status: ' . $data['status']);
                }

                Log::error('Geocoding API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Geocoding error: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Get a shorter, more readable location name (city, area)
     *
     * @param float $latitude
     * @param float $longitude
     * @return string|null
     */
    public function getShortAddressFromCoordinates(float $latitude, float $longitude): ?string
    {
        $cacheKey = 'geocode_short_' . round($latitude, 4) . '_' . round($longitude, 4);

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($latitude, $longitude) {
            try {
                $response = Http::timeout(10)->get($this->baseUrl, [
                    'latlng' => "{$latitude},{$longitude}",
                    'key' => $this->apiKey,
                    'language' => 'en',
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    if ($data['status'] === 'OK' && !empty($data['results'])) {
                        $result = $data['results'][0];
                        
                        // Extract relevant address components
                        $components = $result['address_components'];
                        $locationParts = [];

                        foreach ($components as $component) {
                            // Get neighborhood, locality (city), or administrative area
                            if (in_array('neighborhood', $component['types']) ||
                                in_array('sublocality', $component['types'])) {
                                $locationParts[] = $component['long_name'];
                            } elseif (in_array('locality', $component['types'])) {
                                $locationParts[] = $component['long_name'];
                            }
                        }

                        // If we found specific location parts, return them
                        if (!empty($locationParts)) {
                            return implode(', ', array_unique($locationParts));
                        }

                        // Otherwise, return the formatted address
                        return $result['formatted_address'];
                    }

                    Log::warning('Geocoding API returned status: ' . $data['status']);
                }

                return null;
            } catch (\Exception $e) {
                Log::error('Geocoding error: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Batch geocode multiple coordinates
     *
     * @param array $coordinates Array of ['latitude' => float, 'longitude' => float]
     * @return array
     */
    public function batchGeocode(array $coordinates): array
    {
        $results = [];

        foreach ($coordinates as $index => $coord) {
            if (isset($coord['latitude']) && isset($coord['longitude'])) {
                $results[$index] = $this->getShortAddressFromCoordinates(
                    $coord['latitude'],
                    $coord['longitude']
                );
            } else {
                $results[$index] = null;
            }
        }

        return $results;
    }
}