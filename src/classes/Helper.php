<?php

namespace PrettyFormsLaravel;

use Illuminate\Console\AppNamespaceDetectorTrait;

class Helper {
    use AppNamespaceDetectorTrait;
    
    static function getCurrentAppNamespace() {
        $object = new self;
        return $object->getAppNamespace();
    }
}
