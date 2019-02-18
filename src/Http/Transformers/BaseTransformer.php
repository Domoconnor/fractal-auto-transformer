<?php 
namespace AutoTransformer\Http\Transformers;

use League\Fractal\TransformerAbstract;
use AutoTransformer\Traits\AutoWireTrait;
use AutoTransformer\Traits\SmartTransformerTrait;

class BaseTransformer extends TransformerAbstract
{
    use SmartTransformerTrait, AutoWireTrait;
    // Override the automatic transformer mapping if the transformer name does not match the class
    // e.g. Customer::class => NotCustomerTransformer::class
    protected $transformerMap = [
    ];
    
    public function __construct()
    {
        $this->bootSmartTransformerTrait();
    }
}