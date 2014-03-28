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

    protected function setUp() {
        parent::setUp();
        $this->spec->undos[] = function () {
            $this->cleanUp();
        };
    }

    private function delete($dir) {
        foreach (glob($dir . '/*') as $file) {
            if (is_file($file)) {
                @unlink($file);
            } else {
                $this->delete($file);
            }
        }
        @rmdir($dir);
    }

    public function cleanUp() {
        $this->delete($this->config->tmpDir());
    }

    public function givenTheFolder($name) {
        $folder = $this->getUserFolder() . '/' . $name;
        @mkdir($folder, 0777, true);
        $this->spec->undos[] = function () use ($folder) {
            @rmdir($folder);
        };
    }

    public function givenTheFile_WithContent($path, $content) {
        $file = $this->getUserFolder() . '/' . $path;
        file_put_contents($file, $content);
        $this->spec->undos[] = function () use ($file) {
            @unlink($file);
        };
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