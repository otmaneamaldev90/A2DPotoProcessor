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
        $cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => config('photo_processor.services.cloudinary.cloud_name'),
                'api_key'    => config('photo_processor.services.cloudinary.api_key'),
                'api_secret' => config('photo_processor.services.cloudinary.api_secret'),
            ]
        ]);

        $results = [];
        $photos = $this->params['photos'] ?? [];
        $quality = $this->params['quality'] ?? 100; // Default to 100 if not provided
        $fill = $this->params['fill'] ?? 1; // Default fill option

        // Sort photos numerically by the 'photo' key
        usort($photos, function ($a, $b) {
            $numA = (int) filter_var($a['photo'], FILTER_SANITIZE_NUMBER_INT);
            $numB = (int) filter_var($b['photo'], FILTER_SANITIZE_NUMBER_INT);
            return $numA - $numB;
        });

        // Generate URLs with quality and background fill for each sorted photo
        foreach ($photos as $photo) {
            $public_id = "{$this->params['user_id']}/{$this->vehicle_id}/{$photo['photo']}";
            $image = $cloudinary->image($public_id)->quality($quality);

            // Apply fill options based on the fill parameter
            if ($fill == 1) {
                if (!empty($this->params['default_bg_color'])) {
                    $hex = ltrim($this->params['default_bg_color'], '#');
                    $image->background($hex);
                } elseif (!empty($this->params['default_bg_color_blur'])) {
                    $image->background('blur');
                } else {
                    $image->background('auto');
                }
            }

            $url = $image->toUrl();
            $results[] = (string) $url;
        }

        return $results;
    }
}
