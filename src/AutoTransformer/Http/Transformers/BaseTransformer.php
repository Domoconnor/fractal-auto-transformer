<?php 
namespace AutoTransformer\Http\Transformers;

use League\Fractal\TransformerAbstract;
use AutoTransformer\Traits\AutoWire;
use AutoTransformer\Traits\SmartTransformer;

class BaseTransformer extends TransformerAbstract
{
    use SmartTransformer, AutoWire;
    // Override the automatic transformer mapping if the transformer name does not match the class
    // e.g. Customer::class => NotCustomerTransformer::class
    protected $transformerMap = [
    ];
    
    public function __construct()
    {
        $this->bootSmartTransformerTrait();
    }
}