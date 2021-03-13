<?php

namespace DNABeast\BladeImageCrop\Tests;

use Orchestra\Testbench\TestCase;
use DNABeast\BladeImageCrop\BladeImageCropServiceProvider;

class ExampleTest extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [BladeImageCropServiceProvider::class];
    }
    
    /** @test */
    public function true_is_true()
    {
        $this->assertTrue(true);
    }
}
