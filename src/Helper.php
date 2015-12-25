<?php

namespace PrettyFormsLaravel;

use Illuminate\Console\AppNamespaceDetectorTrait;

class Helper
{
    use AppNamespaceDetectorTrait;

    public static function getCurrentAppNamespace()
    {
        $object = new self;

        return $object->getAppNamespace();
    }
}
