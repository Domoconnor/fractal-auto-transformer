<?php

namespace AutoTransformer\Traits;

use Request;
use League\Fractal\Resource\NullResource;
use Illuminate\Support\Str;

trait SmartTransformerTrait
{
    /**
     * Get possible includes
     */
    public function bootSmartTransformerTrait()
    {
        $this->availableIncludes = array_merge($this->availableIncludes, $this->availableIncludes());
    }

    /**
     * Transformer - creates transformer output item. Attributes is used to define fields.
     *
     * @param $model
     * @return output array
     */
    public function transform($model)
    {
        $data = $this->attributes($model);

        // Handle special
        $data['__meta'] = $this->meta($model);
        $data['__links'] = $this->links($model);

        return $data;
    }

    /**
     * Get link to current item
     *
     * @param $model
     * @return array links
     */
    public function links($model)
    {
        if (method_exists($model, 'getTable')) {
            return ['self' => config('app.url')."/v2/".Str::singular($model->getTable())."/$model->id"];
        }

        return [];
    }

    protected function availableIncludes()
    {
        return [];
    }
}
