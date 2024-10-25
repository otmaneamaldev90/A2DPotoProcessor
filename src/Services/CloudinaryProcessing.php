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

        // Sort photos for consistent ordering
        usort($photos, function ($a, $b) {
            return (int) filter_var($a['photo'], FILTER_SANITIZE_NUMBER_INT) - (int) filter_var($b['photo'], FILTER_SANITIZE_NUMBER_INT);
        });

        foreach ($photos as $key => $photo) {
            $transformation = [
                'quality' => $quality,
                'crop' => $fill == 1 ? 'fill' : 'fit',
                'width' => $width,
                'height' => $height,
            ];

            // Optional filters
            // if ($brightness !== null) {
            //     $transformation[] = ['effect' => "brightness:{$brightness}"];
            // }
            // if ($contrast !== null) {
            //     $transformation[] = ['effect' => "contrast:{$contrast}"];
            // }

            // Apply watermark based on overlay rules
            $applyWatermark = false;
            if (in_array('1', $overlay_images) && $key == 0 && !empty($watermark)) {
                $applyWatermark = true;
            } elseif (in_array('2', $overlay_images) && $key != 0 && $key != (count($photos) - 1) && !empty($watermark)) {
                $applyWatermark = true;
            } elseif (in_array('3', $overlay_images) && $key == (count($photos) - 1) && !empty($watermark)) {
                $applyWatermark = true;
            }

            // if ($applyWatermark) {
            //     $transformation[] = [
            //         'overlay' => $watermark,
            //         'gravity' => 'south_east', // Position the watermark, adjust as needed
            //         'x' => 0,
            //         'y' => 0
            //     ];
            // }

            // Generate URL for the image
            $public_id = "{$this->params['user_id']}/{$this->vehicle_id}/{$photo['photo']}";
            $url = $cloudinary->image($public_id)->toUrl($transformation);

            $results[] = (string) $url;
        }

        return $results;
    }
}
