<?php

namespace AutoDealersDigital\PhotoProcessor\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Transformation\Resize;
use Cloudinary\Transformation\Effect;
use Cloudinary\Transformation\Background;

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
            $url = $cloudinary->image($public_id);

            $transformation = [];

            if (!empty($watermark)) {
                $apply_overlay = false;
                if (in_array('1', $overlay_images) && $key == 0) $apply_overlay = true;
                if (in_array('2', $overlay_images) && $key != 0 && $key != (count($photos) - 1)) $apply_overlay = true;
                if (in_array('3', $overlay_images) && $key == (count($photos) - 1)) $apply_overlay = true;

                if ($apply_overlay) {
                    $transformation[] = [
                        'overlay' => $watermark,
                        'width' => $width,
                        'height' => $height,
                        'crop' => 'fill',
                        'flags' => 'layer_apply'
                    ];
                }
            }

            // Add quality
            $transformation[] = ['quality' => $quality];

            // Add fill with the dominant color or auto padding
            if ($fill == 1) {
                $resize = Resize::fill()->width($width)->height($height);
            } else {
                $resize = Resize::pad()->width($width)->height($height)->background($this->determineBackground());
            }

            // Add brightness
            if ($brightness !== null && is_numeric($brightness)) {
                // $url= $url->effect('brightness', $brightness);
            }

            // Add contrast
            if ($contrast !== null && is_numeric($contrast)) {
                // $url = $url->effect('contrast', $contrast);
            }

            $url = $url->addTransformation($transformation)
                ->resize($resize)
                ->toUrl();

            $results[] = (string) $url;
        }

        if (empty($photos)) {
            \Log::warning("No photos were found");
        }

        return $results;
    }



    private function determineBackground()
    {
        if (!empty($this->params['default_bg_color'])) {
            $hex = ltrim($this->params['default_bg_color'], '#');
            return Background::rgb($hex);
        }

        if (!empty($this->params['default_bg_color_blur'])) {
            return Background::generativeFill();
        }

        if (empty($this->params['default_bg_color']) && empty($this->params['default_bg_color_blur'])) {
            return Background::predominant();
        }
    }
}
