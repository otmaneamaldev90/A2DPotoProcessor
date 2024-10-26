<?php

namespace AutoDealersDigital\PhotoProcessor\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Transformation\Resize;
use Cloudinary\Transformation\Effect;

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
        $quality = $this->params['quality'] ?? 100;
        $brightness = $this->params['brightness'] ?? null;
        $contrast = $this->params['contrast'] ?? null;
        $width = $this->params['width'] ?? 800;
        $height = $this->params['height'] ?? 600;
        $fill = $this->params['fill'] ?? 1;
        $overlay_images = $this->params['overlay_images'] ?? [];
        $watermark = $this->params['watermark_images'] ?? '';

        // Sort photos numerically by the 'photo' key
        usort($photos, function ($a, $b) {
            $numA = (int) filter_var($a['photo'], FILTER_SANITIZE_NUMBER_INT);
            $numB = (int) filter_var($b['photo'], FILTER_SANITIZE_NUMBER_INT);
            return $numA - $numB;
        });

        foreach ($photos as $key => $photo) {
            $public_id = "{$this->params['user_id']}/{$this->vehicle_id}/{$photo['photo']}";
            $transformation = [];

            // Add quality
            $transformation[] = ['quality' => $quality];

            // Add fill
            if ($fill == 1) {
                if (!empty($this->params['default_bg_color'])) {
                    $hex = ltrim($this->params['default_bg_color'], '#');
                    $transformation[] = ['background' => "rgb:$hex"];
                } elseif (!empty($this->params['default_bg_color_blur'])) {
                    $transformation[] = ['background' => 'blur'];
                } else {
                    $transformation[] = ['background' => 'auto'];
                }
            }

            // Add brightness
            if ($brightness !== null && is_numeric($brightness)) {
                $transformation[] = ['effect' => Effect::brightness($brightness)];
            }

            // Add contrast
            if ($contrast !== null && is_numeric($contrast)) {
                $transformation[] = ['effect' => Effect::contrast($contrast)];
            }

            // Add watermark directly to the image
            if (!empty($watermark)) {
                $watermark_order = false;
                if (in_array('1', $overlay_images) && $key == 0) {
                    $watermark_order = true;
                }
                if (in_array('2', $overlay_images) && $key != 0 && $key != (count($photos) - 1)) {
                    $watermark_order = true;
                }
                if (in_array('3', $overlay_images) && $key == (count($photos) - 1)) {
                    $watermark_order = true;
                }
                if ($watermark_order) {
                    $transformation[] = ['overlay' => 'image:' . $watermark];
                }
            }

            $url = $cloudinary->image($public_id)
                ->resize(Resize::fit($width, $height))
                ->addTransformation($transformation)
                ->toUrl();

            $results[] = (string) $url;
        }

        if (empty($photos)) {
            \Log::warning("No photos were found");
        }

        return $results;
    }
}
