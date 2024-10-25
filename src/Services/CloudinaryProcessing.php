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
        $quality = $this->params['quality'] ?? 100;
        $width = $this->params['width'] ?? null;
        $height = $this->params['height'] ?? null;
        $fill = $this->params['fill'] ?? 1;
        $overlay_images = $this->params['overlay_images'] ?? [];
        $watermark = $this->params['watermark_images'] ?? '';
        $brightness = $this->params['brightness'] ?? null;
        $contrast = $this->params['contrast'] ?? null;
        $photos = $this->params['photos'] ?? [];

        usort($photos, function ($a, $b) {
            return (int) filter_var($a['photo'], FILTER_SANITIZE_NUMBER_INT) - (int) filter_var($b['photo'], FILTER_SANITIZE_NUMBER_INT);
        });

        foreach ($photos as $key => $photo) {
            // Flatten transformation array
            $transformation = [
                'quality' => $quality,
                'crop' => 'pad', // Ensures padding with background color
                'background' => 'auto', // Auto-detect background color
                'width' => $width,
                'height' => $height,
            ];

            if ($brightness !== null) {
                $transformation['effect'] = "brightness:{$brightness}";
            }
            if ($contrast !== null) {
                $transformation['effect'] .= "|contrast:{$contrast}";
            }

            $applyWatermark = false;
            if (in_array('1', $overlay_images) && $key == 0 && !empty($watermark)) {
                $applyWatermark = true;
            } elseif (in_array('2', $overlay_images) && $key != 0 && $key != (count($photos) - 1) && !empty($watermark)) {
                $applyWatermark = true;
            } elseif (in_array('3', $overlay_images) && $key == (count($photos) - 1) && !empty($watermark)) {
                $applyWatermark = true;
            }

            if ($applyWatermark) {
                $transformation['overlay'] = $watermark;
                $transformation['gravity'] = 'south_east'; // Adjust if needed
                $transformation['x'] = 0;
                $transformation['y'] = 0;
            }

            $public_id = "{$this->params['user_id']}/{$this->vehicle_id}/{$photo['photo']}";
            $url = $cloudinary->image($public_id)->toUrl($transformation);

            $results[] = (string) $url;
        }

        return $results;
    }
}
