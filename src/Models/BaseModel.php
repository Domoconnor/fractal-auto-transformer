<?php
namespace AutoTransformer\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    /**
     * getModelTransformer
     * Attempt to work out transformer location from model info
     * @return Transformer
     */
    public static function getModelTransformer()
    {
        // try and guess
        $transformer = str_replace('Models', 'Http\Transformers', static::class).'Transformer';
        return class_exists($transformer) ? new $transformer('test') : null;
    }
}