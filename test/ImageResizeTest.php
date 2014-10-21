<?php

namespace Test;

use Slim\Middleware\ImageResize;
use Slim\Middleware\ImageResize\DefaultMutator;

class ImageResizeTest extends \PHPUnit_Framework_TestCase
{

    public function testShouldBeTrue()
    {
        $this->assertTrue(true);
    }

    public function testShouldParseDimensions()
    {
        $_SERVER["DOCUMENT_ROOT"] = "/var/www/www.example.com/public";

        $middleware = new ImageResize();
        $parsed = $middleware->mutator->parse("images/viper-400x200.jpg");
        $this->assertEquals($parsed["filename"], "viper-400x200");
        $this->assertEquals($parsed["basename"], "viper-400x200.jpg");
        $this->assertEquals($parsed["extension"], "jpg");
        $this->assertEquals($parsed["dirname"], "images");
        $this->assertEquals($parsed["original"], "viper");
        $this->assertEquals($parsed["size"], "400x200");
        $this->assertEquals($parsed["width"], "400");
        $this->assertEquals($parsed["height"], "200");
        $this->assertEquals($parsed["source"], "/var/www/www.example.com/public/images/viper.jpg");
        //$this->assertEquals($parsed["cache"], "/var/www/www.example.com/public/cache/images/viper-400x200.jpg");
        $this->assertNull($parsed["signature"]);
    }

    public function testParseShouldReturnFalse()
    {
        $middleware = new ImageResize();
        $parsed = $middleware->mutator->parse("images/viper-new.jpg");
        $this->assertFalse($parsed);
    }

    public function testShouldTestForAllowedExtension()
    {
        $middleware = new ImageResize();
        $this->assertTrue($middleware->allowedExtension("jpg"));
        $this->assertTrue($middleware->allowedExtension("png"));
        $this->assertFalse($middleware->allowedExtension("pdf"));
    }

    public function testAllSizesShouldBeAllowed()
    {
        $middleware = new ImageResize();
        $this->assertTrue($middleware->allowedSize("100x100"));
        $this->assertTrue($middleware->allowedSize("x666"));
        $this->assertTrue($middleware->allowedSize("666x"));
    }

    public function testSpecificSizesShouldBeAllowed()
    {
        $middleware = new ImageResize(array("sizes" => array("100x100", "150x")));
        $this->assertTrue($middleware->allowedSize("100x100"));
        $this->assertTrue($middleware->allowedSize("150x"));
        $this->assertFalse($middleware->allowedSize("666x666"));
    }

    public function testShouldGenerateSignature()
    {
        $signature = ImageResize::signature(array("size" => "100x200", "secret" => "s11kr3t"));
        $this->assertEquals($signature, "e28fe00b3c925c09");
    }

    public function testSignatureShouldNotBeNeeded()
    {
        $middleware = new ImageResize();
        $this->assertTrue($middleware->validSignature());
    }

    public function testShouldValidateSignature()
    {
        $middleware = new ImageResize(array("secret" => "s11kr3t"));
        $signature = ImageResize::signature(array("size" => "100x200", "secret" => "s11kr3t"));
        $this->assertFalse($middleware->validSignature());
        $this->assertTrue($middleware->validSignature(array("signature" => $signature, "size" => "100x200")));
    }

    public function testImagesShouldBeAllowed()
    {
        $middleware = new ImageResize(array(
            "sizes" => array("100x200", "100x100"),
            "secret" => "s11kr3t"
        ));

        $valid = ImageResize::signature(array("size" => "100x200", "secret" => "s11kr3t"));
        $valid_2 = ImageResize::signature(array("size" => "100x100", "secret" => "s11kr3t"));

        $this->assertTrue($middleware->allowed(array(
            "signature" => $valid,
            "size" => "100x200",
            "extension" => "jpg")));

        $this->assertTrue($middleware->allowed(array(
            "signature" => $valid_2,
            "size" => "100x100",
            "extension" => "png")));
    }

    public function testImagesShouldNotBeAllowed()
    {
        $middleware = new ImageResize(array(
            "extensions" => array("jpg", "png"),
            "sizes" => array("100x200", "100x100"),
            "secret" => "s11kr3t"
        ));

        $valid = ImageResize::signature(array("size" => "100x200", "secret" => "s11kr3t"));
        $valid_2 = ImageResize::signature(array("size" => "666x666", "secret" => "s11kr3t"));
        $invalid = ImageResize::signature(array("size" => "100x200", "secret" => "t00r"));

        $this->assertFalse($middleware->allowed(array(
            "signature" => $invalid,
            "size" => "100x200",
            "extension" => "jpg")));
        $this->assertFalse($middleware->allowed(array(
            "signature" => $valid_2,
            "size" => "666x666",
            "extension" => "png")));
        $this->assertFalse($middleware->allowed(array(
            "signature" => $valid,
            "size" => "100x200",
            "extension" => "pdf")));
    }
}
