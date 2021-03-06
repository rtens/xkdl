<?php
namespace spec\rtens\xkdl\fixtures;

use watoki\scrut\Fixture;

/**
 * @property ConfigFixture config <-
 */
class FileFixture extends Fixture {

    private $useHome = true;

    public function setUp() {
        parent::setUp();
        @mkdir($this->getUserFolder(), 0777, true);
    }

    public function givenPathsAreRelativeToTheUserFolder() {
        $this->useHome = false;
    }

    private function getUserFolder() {
        return $this->useHome ? $this->config->getConfig()->homeFolder() : $this->config->getConfig()->userFolder();
    }

    public function givenTheFolder($name) {
        $folder = $this->getUserFolder() . '/' . $name;
        @mkdir($folder, 0777, true);
    }

    public function givenTheFile_WithContent($path, $content) {
        $file = $this->getUserFolder() . '/' . $path;
        @mkdir(dirname($file), 0777, true);
        file_put_contents($file, $content);
    }

    public function thenThereShouldBeAFile_WithTheContent($path, $content) {
        $fullPath = $this->getUserFolder() . '/' . $path;
        $this->spec->assertFileExists($fullPath);
        $this->spec->assertEquals($content, file_get_contents($fullPath));
    }

    public function thenThereShouldBeAFile_ThatContains($path, $content) {
        $fullPath = $this->getUserFolder() . '/' . $path;
        $this->spec->assertFileExists($fullPath);
        $this->spec->assertContains($content, file_get_contents($fullPath));
    }

    public function thenThereShouldBeAFile($path) {
        $fullPath = $this->getUserFolder() . '/' . $path;
        $this->spec->assertFileExists($fullPath);
    }

    public function thenThereShouldBeAFolder($path) {
        $fullPath = $this->getUserFolder() . '/' . $path;
        $this->spec->assertFileExists($fullPath);
    }

    public function thenThereShouldBeNoFile($path) {
        $this->spec->assertFileNotExists($this->getUserFolder() . '/' . $path);
    }

    public function then_ShouldBeEmpty($directory) {
        $files = [];
        foreach (glob($this->getUserFolder() . '/' . $directory . '/*') as $file) {
            $files[] = basename($file);
        }
        $this->spec->assertEmpty($files, json_encode($files));
    }

} 