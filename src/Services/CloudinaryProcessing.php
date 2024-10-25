<?php

namespace AutoDealersDigital\PhotoProcessor\Services;

use Cloudinary\Cloudinary;

class CloudinaryProcessing
{
    protected $params;
    protected $vehicle_id;

    public function __construct($params, $vehicle_id)
    {
        $this->params = $params;
        $this->vehicle_id = $vehicle_id;
    }

    public function process()
    {
        $results = [];
        $photos = $this->params['photos'] ?? [];
        $quality = $this->params['quality'] ?? 100;
        $contrast = $this->params['contrast'] ?? null;

        $cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => config('photo_processor.services.cloudinary.cloud_name'),
                'api_key'    => config('photo_processor.services.cloudinary.api_key'),
                'api_secret' => config('photo_processor.services.cloudinary.api_secret'),
            ]
        ]);

        if (!empty($photos) && is_array($photos)) {
            usort($photos, function ($a, $b) {
                $numA = (int)filter_var($a['photo'], FILTER_SANITIZE_NUMBER_INT);
                $numB = (int)filter_var($b['photo'], FILTER_SANITIZE_NUMBER_INT);
                return $numA - $numB;
            });

            foreach ($photos as $key => $photo) {
                $transformations = [];
                $transformations[] = ['quality' => $quality];

                if ($contrast !== null && is_numeric($contrast)) {
                    $transformations[] = ['effect' => 'contrast:' . $contrast];
                }

                $photo_url_origin = "{$this->params['user_id']}/{$this->vehicle_id}/{$photo['photo']}";

                if (isset($photo_url_origin) && !empty($photo_url_origin)) {
                    $result = $cloudinary->uploadApi()->upload($photo_url_origin, ['transformation' => $transformations]);
                    $results[] = $result['secure_url'];
                } else {
                    \Log::warning("Photo URL origin is null or empty");
                }
            }
        } else {
            \Log::warning("No photos were found");
        }

        return $results;
    }
}
