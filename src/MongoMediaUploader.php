<?php

namespace Plank\Mediable;

use Plank\Mediable\Helpers\File;
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
     * Validate input and convert to Media attributes
     * @param  MongoMedia $model
     * @return MongoMedia
     *
     * @throws ConfigurationException
     * @throws FileNotFoundException
     * @throws FileNotSupportedException
     * @throws FileSizeException
     */
    protected function populateModel(MongoMedia $model): MongoMedia
    {
        $model->size = $this->verifyFileSize($this->source->size() ?? 0);
        $model->mime_type = $this->verifyMimeType($this->selectMimeType());
        $model->extension = $this->verifyExtension(
            $this->source->extension()
                ?? File::guessExtension($model->mime_type)
        );
        $model->aggregate_type = $this->inferAggregateType($model->mime_type, $model->extension);

        $model->disk = $this->disk ?: $this->config['default_disk'];
        $model->directory = $this->directory;
        $model->filename = $this->generateFilename();

        if ($this->alt) {
            $model->alt = $this->alt;
        }

        return $model;
    }


       /**
     * Generate an instance of the `Media` class.
     * @return MongoMedia
     */
    protected function makeModel(): MongoMedia
    {
        $class = $this->config['mongo_model'] ?? MongoMedia::class;

        return new $class;
    }


}
