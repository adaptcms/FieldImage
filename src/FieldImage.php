<?php

namespace Adaptcms\FieldImage;

use Adaptcms\Base\Models\Package;

class FieldImage
{
  /**
  * On Install
  *
  * @return void
  */
  public function onInstall()
  {
    Package::syncPackageFolder(get_class());
  }
}
