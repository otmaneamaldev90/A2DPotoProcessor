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
        $width = $this->params['width'] ?? 800;
        $height = $this->params['height'] ?? 600;
        $quality = $this->params['quality'] ?? 100;
        $fill = $this->params['fill'] ?? 1;
        $overlay_images = $this->params['overlay_images'] ?? [];
        $watermark = $this->params['watermark_images'] ?? '';
        $brightness = $this->params['brightness'] ?? null;
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

                if ($fill == 1) {
                    if (!empty($this->params['default_bg_color'])) {
                        $hex = ltrim($this->params['default_bg_color'], '#');
                        $transformations[] = ['effect' => 'fill:rgb:' . $hex];
                    } elseif (!empty($this->params['default_bg_color_blur'])) {
                        $transformations[] = ['effect' => 'blur'];
                    } else {
                        $transformations[] = ['crop' => 'fill'];
                    }
                }

                if ($brightness !== null && is_numeric($brightness)) {
                    $transformations[] = ['effect' => 'brightness:' . $brightness];
                }

                if ($contrast !== null && is_numeric($contrast)) {
                    $transformations[] = ['effect' => 'contrast:' . $contrast];
                }

                $transformations[] = ['quality' => $quality];
                $transformations[] = ['width' => $width, 'height' => $height];

                $photo_url_origin = "{$this->params['user_id']}/{$this->vehicle_id}/{$photo['photo']}";

                if ($this->shouldApplyWatermark($overlay_images, $key, $photos, $watermark)) {
                    $transformations[] = ['overlay' => $watermark];
                }

                $result = $cloudinary->uploadApi()->upload($photo_url_origin, ['transformation' => $transformations]);
                $results[] = $result->getSecureUrl();
            }
        } else {
            \Log::warning("No photos were found");
        }

        return $results;
    }

    private function shouldApplyWatermark($overlay_images, $key, $photos, $watermark)
    {
        if (in_array('1', $overlay_images) && $key == 0 && !empty($watermark)) {
            return true;
        }

        if (in_array('2', $overlay_images) && $key != 0 && $key != (count($photos) - 1) && !empty($watermark)) {
            return true;
        }

        if (in_array('3', $overlay_images) && $key == (count($photos) - 1) && !empty($watermark)) {
            return true;
        }

        return false;
    }
}
