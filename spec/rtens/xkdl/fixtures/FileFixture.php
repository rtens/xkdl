<?php
namespace spec\rtens\xkdl\fixtures;

use watoki\scrut\Fixture;

/**
 * @property ConfigFixture config <-
 */
class FileFixture extends Fixture {

    private function getUserFolder() {
        return $this->config->getConfig()->userFolder();
    }

    public function givenTheFolder($name) {
        $folder = $this->getUserFolder() . '/' . $name;
        @mkdir($folder, 0777, true);
    }

    public function givenTheFile_WithContent($path, $content) {
        $file = $this->getUserFolder() . '/' . $path;
        file_put_contents($file, $content);
    }

    public function thenThereShouldBeAFile_WithTheContent($path, $content) {
        $fullPath = $this->getUserFolder() . '/' . $path;
        $this->spec->assertFileExists($fullPath);
        $this->spec->assertEquals($content, file_get_contents($fullPath));
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

} 