<?php

namespace Drupal\deprecated_module;

class DeprecatedClass {
  public function doSomething() {
    // file_create_url is deprecated in Drupal 9.3.0.
    $url = file_create_url('public://test.txt');
  }
}