<?php

namespace AutoTransformer\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;
use AutoTransformer\Http\Transformers\BaseTransformer;
use AutoTransformer\Models\BaseModel;
use Customer\Http\Transformers\CustomJsonApiSerializer;
use League\Fractal\Resource\Item;
use League\Fractal\Scope;
use League\Fractal\Manager;
use League\Fractal\Resource\NullResource;
use DB;

class AutoTransformerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../../config.php' => config_path('transformer.php'),
        ]);

        // If we're in debug mode start the query log so we can dump it out as part of the debug helper
        if (config('app.debug', false)) {
            DB::enableQueryLog();
        }
        
        $fractal = $this->app->make('League\Fractal\Manager');
        response()->macro('item', function ($item, \League\Fractal\TransformerAbstract $transformer, $namespace = null, $status = 200, array $headers = [], $meta = []) use ($fractal) {
            $resource = new Item($item, $transformer, $namespace);
            if (!empty($meta)) {
                $resource->setMeta($meta);
            }
            $scope = new Scope($fractal, $resource);
            return response()->json(
                $scope->toArray(),
                $status,
                $headers
            );
        });
        response()->macro('collection', function ($collection, \League\Fractal\TransformerAbstract $transformer, $namespace = null, $status = 200, array $headers = [], $meta = []) use ($fractal) {
            $resource = new \League\Fractal\Resource\Collection($collection, $transformer, $namespace);
            if (!empty($meta)) {
                $resource->setMeta($meta);
            }
            return response()->json(
                $fractal->createData($resource)->toArray(),
                $status,
                $headers
            );
        });

        Response::macro(
            'jsonApi',
            function ($model, $transformer = null, $type = null, $code = 200, $headers = []) {
                $baseTransformer = new BaseTransformer;
                if (is_a($model, 'Illuminate\Support\Collection')) {
                    $resource = $baseTransformer->collection($model, $transformer, $type);
                } elseif (is_a($model, 'Illuminate\Pagination\LengthAwarePaginator')) {
                    $resource = $baseTransformer->collection($model, $transformer, $type);
                    $resource->setPaginator(new IlluminatePaginatorAdapter($model));
                } elseif (empty($model)) {
                    $resource = new NullResource();
                } elseif (is_array($model)) {
                    $resource = $baseTransformer->item(collect($model), $transformer, $type);
                } else {
                    $resource = $baseTransformer->item($model, $transformer, $type);
                }
                $manager = new Manager();
                $include = request()->has('include') ? request()->get('include') : '';
                $serializer = config('transformer.serializer');
                $manager->parseIncludes($include)->setSerializer(new $serializer);
                $payload = $manager->createData($resource)->toArray();
                if (config("app.debug", false)) {
                    $payload['info'] = AutoTransformerServiceProvider::debug();
                }
                // Disable again once data is ready to go.
                return response()->json($payload, $code, $headers);
            }
        );
    }

    public static function debug()
    {
        $output = ['queries' => []];
        $queryLog = DB::getQueryLog();
        foreach ($queryLog as $q) {
            $output['queries'][] = [
                // Swap bindings in to query for easier testing
                'query' => static::queryFlatten($q['query'], $q['bindings']),
                'time' => $q['time']
            ];
        }
        return $output;
    }
    public static function queryFlatten($query, $args)
    {
        foreach ($args as $arg) {
            // Parse dates
            if (is_a($arg, 'DateTime')) {
                $arg = $arg->format('Y-m-d H:i:s');
            }
            // Swap first ? we find with first val & keep going
            $query = preg_replace('/\?/', "'{$arg}'", $query, 1);
        }
        return $query;
    }
}