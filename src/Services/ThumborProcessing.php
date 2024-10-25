<?php

namespace AutoDealersDigital\PhotoProcessor\Services;

use Beeyev\Thumbor\Thumbor;
use Beeyev\Thumbor\Manipulations\Fit;



class ThumborProcessing
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
        $filters = [];

        $quality = $this->params['quality'] ?? 100;
        $photos = $this->params['photos'] ?? [];
        $fill = $this->params['fill'] ?? 1;
        $overlay_images = $this->params['overlay_images'] ?? [];
        $watermark = $this->params['watermark_images'] ?? '';
        $brightness = $this->params['brightness'] ?? null;
        $contrast = $this->params['contrast'] ?? null;

        $filters[] = "quality({$quality})";

        if ($fill == 1) {
            if (!empty($this->params['default_bg_color'])) {
                $hex = ltrim($this->params['default_bg_color'], '#');
                $filters[] = "fill($hex)";
            } elseif (!empty($this->params['default_bg_color_blur'])) {
                $filters[] = "fill(blur)";
            } else {
                $filters[] = "fill(auto)";
            }
        }

        if ($brightness != null && is_numeric($brightness)) {
            $filters[] = "brightness({$brightness})";
        }
        if ($contrast != null && is_numeric($contrast)) {
            $filters[] = "contrast({ $contrast})";
        }

        $filters = array_filter($filters);

        if (!empty($photos) && is_array($photos)) {

            usort($photos, function ($a, $b) {
                $numA = (int) filter_var($a['photo'], FILTER_SANITIZE_NUMBER_INT);
                $numB = (int) filter_var($b['photo'], FILTER_SANITIZE_NUMBER_INT);
                return $numA - $numB;
            });

            foreach ($photos as $key => $photo) {
                $thumbor = new Thumbor(
                    config('photo_processor.services.thumbor.url'),
                    config('photo_processor.services.thumbor.secret')
                );

                $thumbor->resizeOrFit(
                    $this->params['width'] ?? null,
                    $this->params['height'] ?? null,
                    Fit::FIT_IN
                );

                $watermark_order = false;
                $filtersString = implode(":", $filters);

                if (in_array('1', $overlay_images) && $key == 0 && !empty($watermark)) {
                    $watermark_order = true;
                }

                if (in_array('2', $overlay_images) && $key != 0 && $key  != (count($photos) - 1) && !empty($watermark)) {
                    $watermark_order = true;
                }

                if (in_array('3', $overlay_images) &&  $key  == (count($photos) - 1)  && !empty($watermark)) {
                    $watermark_order = true;
                }

                if ($watermark_order) {
                    $filtersString =  $filtersString  . ":watermark($watermark,0,0,0)";
                }

                $thumbor->addFilter($filtersString);
                $photo_url_origin = "{$this->params['user_id']}/{$this->vehicle_id}/{$photo['photo']}";
                $thumbor->imageUrl($photo_url_origin);
                $results[] = $thumbor->get();
            }
        } else {
            \Log::warning("No Photo were found");
        }

        return $results;
    }
}
