<?php

namespace AutoDealersDigital\PhotoProcessor\Services;

use Cloudinary\Cloudinary;

class CloudinaryProcessing
{
    protected $params;
    protected $vehicle_id;
    protected $cloudinary;

    public function __construct($params, $vehicle_id)
    {
        $this->params = $params;
        $this->vehicle_id = $vehicle_id;

        // Initialize Cloudinary
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => config('photo_processor.services.cloudinary.cloud_name'),
                'api_key' => config('photo_processor.services.cloudinary.api_key'),
                'api_secret' => config('photo_processor.services.cloudinary.api_secret'),
            ],
        ]);
    }

    public function process()
    {
        $results = [];

        // Set quality from parameters
        $quality = $this->params['quality'] ?? 100;

        // Process each photo
        $photos = $this->params['photos'] ?? [];
        if (!empty($photos) && is_array($photos)) {
            foreach ($photos as $photo) {
                $photo_url_origin = "{$this->params['user_id']}/{$this->vehicle_id}/{$photo['photo']}";

                // Generate Cloudinary URL with quality
                $url = $this->cloudinary->getUrl($photo_url_origin, [
                    'quality' => $quality,
                ]);

                $results[] = $url;
            }
        } else {
            \Log::warning("No Photo were found");
        }

        return $results;
    }
}
