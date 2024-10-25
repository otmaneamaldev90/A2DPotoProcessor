<?php

namespace AutoDealersDigital\PhotoProcessor;

use AutoDealersDigital\PhotoProcessor\Services\CloudinaryProcessing;
use AutoDealersDigital\PhotoProcessor\Services\ThumborProcessing;

class PhotoProcessor
{
    protected $params;
    protected $vehicle_id;
    protected $service;

    public function __construct(array $params, $vehicle_id)
    {
        $this->params = $params;
        $this->vehicle_id = $vehicle_id;

        // Determine which service to use based on the config or the params passed
        $this->service = $params['service'] ?? config('photo_processor.default_service');
    }

    public function process()
    {
        $default_params = [
            'classified_id' => 1,
            'width' => 800,
            'height' => 600,
            'main' => false,
            'overlaid' => 1,
            'overlay_images' => null,
            'watermark_images' => null,
            'overlay_id' => null,
            'fill' => 1,
            'photos' => null,
            'quality' => 100,
            'user_id' => null,
            'default_photo' => 'no_photo.jpg',
            'has_default_photo' => false,
        ];

        // Mapping of services to their corresponding classes
        $servicesMap = [
            'cloudinary' => CloudinaryProcessing::class,
            'thumbor'    => ThumborProcessing::class,
        ];

        // Get the appropriate service class
        $processorClass = $servicesMap[$this->service] ?? ThumborProcessing::class;

        // Merge default and passed params
        $this->params = array_merge($default_params, $this->params);

        // Instantiate the service and process the photos
        $processor = new $processorClass($this->params, $this->vehicle_id);
        return $processor->process();
    }
}
