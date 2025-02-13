<?php

namespace Plank\Mediable;

use Plank\Mediable\Exceptions\MediaUpload\FileSizeException;
use Plank\Mediable\Exceptions\MediaUpload\FileExistsException;
use Plank\Mediable\Exceptions\MediaUpload\InvalidHashException;
use Plank\Mediable\Exceptions\MediaUpload\FileNotFoundException;
use Plank\Mediable\Exceptions\MediaUpload\ConfigurationException;
use Plank\Mediable\Exceptions\MediaUpload\FileNotSupportedException;

class MongoMediaUploader extends MediaUploader
{

       /**
     * Process the file upload.
     *
     * Validates the source, then stores the file onto the disk and creates and stores a new Media instance.
     *
     * @return MongoMedia
     * @throws ConfigurationException
     * @throws FileExistsException
     * @throws FileNotFoundException
     * @throws FileNotSupportedException
     * @throws FileSizeException
     * @throws InvalidHashException
     */
    public function upload(): MongoMedia
    {
        $this->verifyFile();

        $model = $this->populateModel($this->makeModel());

        $this->manipulateImage($model);

        if (is_callable($this->before_save)) {
            call_user_func($this->before_save, $model, $this->source);
        }

        $this->verifyDestination($model);
        $this->writeToDisk($model);
        $model->save();

        return $model;
    }

       /**
     * Generate an instance of the `Media` class.
     * @return MongoMedia
     */
    private function makeModel(): MongoMedia
    {
        $class = $this->config['mongo_model'] ?? MongoMedia::class;

        return new $class;
    }


}
