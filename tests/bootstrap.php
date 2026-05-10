<?php

$loader = require __DIR__.'/../../../vendor/autoload.php';

$loader->addPsr4('Lalalili\\CourseCore\\', __DIR__.'/../../course-core/src/', true);
$loader->addPsr4('Lalalili\\CourseFilament\\', __DIR__.'/../src/', true);
$loader->addPsr4('Lalalili\\CourseFilament\\Tests\\', __DIR__.'/', true);
$loader->addClassMap([
    'Lalalili\\CourseFilament\\Pages\\CoursePackageHealth' => __DIR__.'/../src/Pages/CoursePackageHealth.php',
]);

return $loader;
