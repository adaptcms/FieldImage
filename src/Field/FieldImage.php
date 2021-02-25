<?php

namespace Adaptcms\FieldImage\Field;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

use Adaptcms\Base\Models\PackageField;
use Adaptcms\Fields\FieldType;
use Adaptcms\FieldImage\Traits\HasImageMigrations;

class FieldImage extends FieldType
{
  use HasImageMigrations;

  /**
  * @var array
  */
  public $defaultSettings = [
    'options' => [
      'is_sortable'        => false,
      'is_searchable'      => false,
      'is_required_create' => false,
      'is_required_edit'   => false
    ],
    'action_rules' => [
      'index'  => false,
      'create' => true,
      'edit'   => true,
      'show'   => true,
      'search' => false
    ]
  ];

  /**
  * @var boolean
  */
  public $shouldNotSetData = true;

  /**
  * Single Image Migration Command
  *
  * @return string
  */
  public function singleImageMigrationCommand()
  {
    return '$table->string(":columnName")->nullable();';
  }

  /**
  * Multiple Images Migration Command
  *
  * @return string
  */
  public function multipleImagesMigrationCommand()
  {
    return '$table->json(":columnName")->nullable();';
  }

  /**
  * Format Name
  *
  * @param PackageField $packageField
  *
  * @return string
  */
  public function formatName(PackageField $packageField)
  {
    $name = Str::singular($packageField->name);
    if ($packageField->meta['mode'] === 'multiple') {
      $name = Str::plural($packageField->name);
    }

    return $name;
  }

  /**
  * Format Column Name
  *
  * @param PackageField $packageField
  *
  * @return string
  */
  public function formatColumnName(PackageField $packageField)
  {
    $name = Str::singular($packageField->name);
    if ($packageField->meta['mode'] === 'multiple') {
      $name = Str::plural($packageField->name);
    }

    return strtolower($name);
  }

  /**
  * With Form Meta
  *
  * @param Request      $request
  * @param PackageField $packageField
  *
  * @return array
  */
  public function withFormMeta(Request $request, PackageField $packageField)
  {
    $meta = [];

    // set media info to view
    $columnName = $packageField->column_name;

    $customModel = $packageField->package->customModel();

    $routeParams = $request->route()->parameters();

    if (!empty($routeParams['itemId'])) {
      $model = $customModel->find($routeParams['itemId']);

      if (!empty($model)) {
        $meta = $model->getMedia($columnName);
      }
    }

    return $meta;
  }

  /**
  * After Model Store
  *
  * @param Model        $model
  * @param Request      $request
  * @param PackageField $packageField
  *
  * @return void
  */
  public function afterModelStore($model, Request $request, PackageField $packageField)
  {
    $this->afterModelSave($model, $request, $packageField);
  }

  /**
  * After Model Update
  *
  * @param Model        $model
  * @param Request      $request
  * @param PackageField $packageField
  *
  * @return void
  */
  public function afterModelUpdate($model, Request $request, PackageField $packageField)
  {
    $this->afterModelSave($model, $request, $packageField);
  }

  /**
  * After Model Save
  *
  * @param Model        $model
  * @param Request      $request
  * @param PackageField $packageField
  *
  * @return void
  */
  public function afterModelSave($model, Request $request, PackageField $packageField)
  {
    // init vars
    $columnName = $packageField->column_name;
    $isMultiple = ($packageField->meta['mode'] === 'multiple');

    if ($isMultiple) {
      $values = is_null($model->$columnName) ? [] : $model->$columnName;
    } else {
      $values = is_null($model->$columnName) ? [] : [ $model->$columnName ];
    }

    // delete existing file(s) if there are any
    $media = $model->getMedia($columnName);
    $removeImages = $request->removeImages;

    if ($media->count() && !empty($removeImages)) {
      $mediaToDelete = $media->whereIn('id', $removeImages);

      // unset column value if no media no longer present
      if ($mediaToDelete->count() === $media->count()) {
        $model->$columnName = null;

        $model->save();
      }

      foreach ($mediaToDelete as $item) {
        // \Log::info('deleted file: ' . $item->file_name);

        $findKey = array_search($item->getFullUrl(), $values);

        if ($findKey !== false) {
          unset($values[$findKey]);
        }

        $item->delete();
      }
    }

    // \Log::info($values);

    // ensure files have been uploaded
    $fileData = $request->$columnName;

    if (!empty($fileData)) {
      // handle uploading the file contents
      $handleFile = function ($file) use ($model, $columnName) {
        $filename = $file->getClientOriginalName();

        // save file to media collection
        $uploadedFile = $model
          ->addMedia($file->path())
          ->usingName($filename)
          ->usingFileName($filename)
          ->toMediaCollection($columnName);

        // \Log::info('uploaded file: ' . $uploadedFile->getFullUrl());

        return $uploadedFile->getFullUrl();
      };

      // set column data to either a json array of file urls
      // or a single file url string
      foreach ($fileData as $file) {
        $values[] = $handleFile($file);
      }

      if (!empty($values)) {
        if ($isMultiple) {
          $model->$columnName = $values;
        } else {
          $model->$columnName = $values[0];
        }

        // \Log::info($values);

        // save image data
        $model->save();
      }
    }
  }

  /**
  * Create Field Rules
  *
  * @return array
  */
  public function createFieldRules()
  {
    return [
      'meta.mode' => 'required'
    ];
  }

  /**
  * Update Field Rules
  *
  * @return array
  */
  public function updateFieldRules()
  {
    return [
      'meta.mode' => 'required'
    ];
  }
}
