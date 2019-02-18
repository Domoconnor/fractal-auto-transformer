<?php

namespace AutoTransformer\Traits;

use League\Fractal\Resource\NullResource;

trait AutoWireTrait
{
    /**
     * automatically figure out whether include is collection or item from $data
     *
     * @param $data - Model or collection to transform
     * @param $transformer - Transformer to use (will figure out automatically if not provided)
     * @param $resourceKey - Resource Key to use (will figure out automatically if not provided)
     *
     */
    public function auto($data, $transformer = null, $resourceKey = null)
    {
        if (!$data || empty($data)) {
            return new NullResource();
        }

        if (is_a($data, 'Illuminate\Database\Eloquent\Collection')) {
            return $this->collection($data, $transformer, $resourceKey);
        } elseif (is_a($data, 'Illuminate\Support\Collection') && is_a($data->first(), 'Illuminate\Database\Eloquent\Model')) {
            return $this->collection($data, $transformer, $resourceKey);
        } else {
            return $this->item($data, $transformer, $resourceKey);
        }
    }

    /**
     * Transform a single model
     *
     * @param $data - Model or collection to transform
     * @param $transformer - Transformer to use
     * @param $resourceKey - Resource Key to use
     *
     */
    public function item($model, $transformer = null, $resourceKey = null)
    {
        // Get transformer type
        list($transformer, $resourceKey) = $this->loadTransformerDetails($model, $transformer, $resourceKey);

        return parent::item($model, $transformer, $resourceKey);
    }

    /**
     * Transform a collection of models
     *
     * @param $data - Model or collection to transform
     * @param $transformer - Transformer to use
     * @param $resourceKey - Resource Key to use
     *
     * @return Transformed items
     */
    public function collection($model, $transformer = null, $resourceKey = null)
    {
        if (!$model->isEmpty()) {
            // Use first item to guess transformer type
            list($transformer, $resourceKey) = $this->loadTransformerDetails($model->first(), $transformer, $resourceKey);
        }

        return parent::collection($model, $transformer, $resourceKey);
    }


    /**
     * loadTransformerDetails
     * Attempt to work out transformer & resource key from $model
     *
     * @param $model - Model to transform
     * @param $transformer - Override Transformer
     * @param $resourceKey - Override Resource Key
     *
     * @return [Transformer, resource_key]
     */
    public function loadTransformerDetails($model, $transformer, $resourceKey)
    {
        // Fail if no model
        if (!$model) {
            throw new \Exception("Cannot transform null");
        }

        if (!$transformer) {
            // If no transformer override set, check our transformer map
            $class = get_class($model);
            if (isset($this->transformerMap[$class])) {
                $transformer = new $this->transformerMap[$class];
            } else {
                // If not in map, see if model specifies
                $transformer = $model::getModelTransformer();

                if (empty($transformer)) {
                    throw new \Exception("No registered transformer found for model ".get_class($model));
                }
            }
        }

        // Use model table as resource key
        if (!$resourceKey) {
            $resourceKey = str_singular($model->getTable());
        }

        return [$transformer, $resourceKey];
    }

    /**
     * If an include is available and no method is defined to handle it,
     * use include name to work out correct methods to call and include via auto
     *
     */
    public function __call($method, $arguments)
    {
        $attemptedInclude = lcfirst(str_replace('include', '', $method));

        // If its available
        if (in_array($attemptedInclude, $this->availableIncludes)) {
            return $this->auto($arguments[0]->{$attemptedInclude});
        }
    }
}
