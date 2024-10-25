<?php

namespace AutoDealersDigital\PhotoProcessor\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Transformation\Resize;
use Cloudinary\Transformation\Format;
use Cloudinary\Transformation\Quality;
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
        $overlay_images = $this->params['overlay_images'] ?? [];
        $watermark = $this->params['watermark_images'] ?? '';

        if (!empty($photos) && is_array($photos)) {

            // Sort photos based on extracted number, similar to Thumbor logic
            usort($photos, function ($a, $b) {
                $numA = (int) filter_var($a['photo'], FILTER_SANITIZE_NUMBER_INT);
                $numB = (int) filter_var($b['photo'], FILTER_SANITIZE_NUMBER_INT);
                return $numA - $numB;
            });

            foreach ($photos as $key => $photo) {
                $transformation = (new \Cloudinary\Transformation\Transformation())
                    ->resize(Resize::fill($this->params['width'] ?? null, $this->params['height'] ?? null))
                    ->quality(Quality::level($this->params['quality'] ?? 100));

                // Optional brightness and contrast
                if (isset($this->params['brightness'])) {
                    $transformation->effect(Effect::brightness($this->params['brightness']));
                }
                if (isset($this->params['contrast'])) {
                    $transformation->effect(Effect::contrast($this->params['contrast']));
                }

                // Optional background fill color
                if (!empty($this->params['default_bg_color'])) {
                    $transformation->background($this->params['default_bg_color']);
                }

                // Check overlay/watermark logic as per photo index
                $watermark_order = false;
                if (in_array('1', $overlay_images) && $key == 0 && !empty($watermark)) {
                    $watermark_order = true;
                } elseif (in_array('2', $overlay_images) && $key != 0 && $key != (count($photos) - 1) && !empty($watermark)) {
                    $watermark_order = true;
                } elseif (in_array('3', $overlay_images) && $key == (count($photos) - 1) && !empty($watermark)) {
                    $watermark_order = true;
                }

                if ($watermark_order) {
                    $transformation->overlay($watermark);
                }

                // Generate the Cloudinary URL with transformations
                $photo_url_origin = "{$this->params['user_id']}/{$this->vehicle_id}/{$photo['photo']}";
                $result = $cloudinary->image($photo_url_origin)->addTransformation($transformation)->toUrl();

                $results[] = $result;
            }
        } else {
            \Log::warning("No photos found for processing");
        }

        return $results;
    }
}
