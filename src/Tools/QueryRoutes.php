<?php

namespace Mateffy\LaravelCodebaseMcp\Tools;

use Mateffy\Introspect\Facades\Introspect;
use Mateffy\Introspect\Query\Contracts\RouteQueryInterface;
use Mateffy\Introspect\Query\RouteQuery;
use Mateffy\Introspect\DTO\Route;
use PhpMcp\Server\Attributes\McpTool;

class QueryRoutes
{
    use ApplyToQuery;

    public static function name(): string
    {
        return 'query_laravel_routes';
    }

    public static function description(): string
    {
        return <<<EOT
        Query the routes in the Laravel codebase by applying various filters based on their names, URIs, associated controllers, applied middleware, or defined parameters. If no filters are provided, all routes will be returned.

        For most filter categories, you can provide multiple values using `_or` parameters. These values are checked with OR logic, meaning a route will match if it satisfies any of the conditions within that specific parameter (e.g., `name_equals_or: ['login', 'register']` will match routes named "login" OR "register"). Filters applied via `_and` parameters require all provided values to be present (e.g., `uses_middleware_and: ['auth', 'web']` will match routes that use both 'auth' AND 'web' middleware). All distinct filter parameters (e.g., `name_equals_or` and `uses_controller_or`) are checked with AND logic, meaning a route must satisfy conditions from all provided filter categories.

        Wildcards (`*`) are supported for route name, path (URI), and controller filters. For middleware and parameter filters, exact matching is required.

        ## Name Filters (Supports Wildcards)
        These filters operate on the named routes (e.g., `route('posts.show')`). Wildcards (`*`) can be used.

        *   `name_equals_or`: Includes routes whose name matches ANY of the provided values.
            Examples:
              - `login` (matches the route named "login")
              - `admin.*` (matches all routes whose names start with "admin.")
              - `*.show` (matches all routes whose names end with ".show")

        *   `name_doesnt_equal_or`: Excludes routes whose name matches ANY of the provided values.
            Examples:
              - `debugbar.*` (excludes all routes related to debugbar)
              - `api.v1.*` (excludes all API v1 routes)

        *   `name_doesnt_equal_and`: Excludes routes only if their name matches ALL of the provided values. (This is generally used for more complex exclusion scenarios where a route must satisfy multiple negative conditions simultaneously).
            Examples:
              - `admin.users.delete`, `*.force_delete` (excludes routes that are named "admin.users.delete" AND also end with ".force_delete", if such a combination were to exist)

        ## Controller Filters (Supports Wildcards for Class Name)
        These filters operate on the controller class or class method used by the route. When specifying a controller, you must provide the full classified class string (e.g., `App\Http\Controllers\UserController`). Wildcards (`*`) can be used for the controller class name.

        *   `uses_controller_or`: Includes routes that use ANY of the specified controllers (and optionally, methods).
            Examples:
              - `App\Http\Controllers\UserController` (matches routes handled by `UserController`)
              - `App\Http\Controllers\*Controller` (matches routes handled by any controller ending with "Controller" in the `App\Http\Controllers` namespace)
              - `App\Http\Controllers\PostController, index` (matches routes handled by the `index` method of `PostController`)

        *   `doesnt_use_controller_or`: Excludes routes that use ANY of the specified controllers (and optionally, methods).
            Examples:
              - `App\Http\Controllers\Admin\*` (excludes routes handled by any controller in the `App\Http\Controllers\Admin` namespace)
              - `App\Http\Controllers\Api\UserController, show` (excludes routes handled by the `show` method of `Api\UserController`)

        *   `uses_controller_and`: Includes routes that use ALL of the specified controllers (and optionally, methods). This is typically useful when a route's action points to a method that itself might internally use another controller method (less common in standard Laravel routing, but possible in more advanced setups or when combined with other filters).
            Examples:
              - `App\Http\Controllers\UserController, store`, `App\Http\Controllers\Auth\RegisterController` (matches routes that use `UserController@store` AND `RegisterController`, if a route were to satisfy both conditions)

        *   `doesnt_use_controller_and`: Excludes routes only if they do NOT use ALL of the specified controllers (and optionally, methods).
            Examples:
              - `App\Http\Controllers\Legacy\*`, `*V1Controller` (excludes routes that do not use any legacy controller AND do not use any V1 controller)

        ## Middleware Filters (Exact Match Only)
        These filters operate on the exact names of middleware applied to the route (e.g., `auth`, `throttle:60,1`). Wildcards are NOT supported.

        *   `uses_middleware_or`: Includes routes that use ANY of the specified middleware.
            Examples:
              - `auth`, `guest` (matches routes that use either the `auth` or `guest` middleware)

        *   `doesnt_use_middleware_or`: Excludes routes that use ANY of the specified middleware.
            Examples:
              - `web`, `verified` (excludes routes that use either the `web` or `verified` middleware)

        *   `uses_middleware_and`: Includes routes that use ALL of the specified middleware.
            Examples:
              - `auth`, `admin` (matches routes that use both `auth` AND `admin` middleware)

        *   `doesnt_use_middleware_and`: Excludes routes only if they do NOT use ALL of the specified middleware.
            Examples:
              - `api`, `sanctum` (excludes routes that lack both `api` AND `sanctum` middleware)

        ## Parameter Filters (Exact Match Only)
        These filters operate on the exact names of route parameters (e.g., `{id}`, `{slug}`). Wildcards are NOT supported.

        *   `has_parameter_or`: Includes routes that have ANY of the specified parameters.
            Examples:
              - `id`, `uuid` (matches routes with either an `{id}` or `{uuid}` parameter)

        *   `doesnt_have_parameter_or`: Excludes routes that have ANY of the specified parameters.
            Examples:
              - `post`, `user` (excludes routes that have either a `{post}` or `{user}` parameter)

        *   `has_parameter_and`: Includes routes that have ALL of the specified parameters.
            Examples:
              - `user`, `post` (matches routes that have both `{user}` AND `{post}` parameters)

        *   `doesnt_have_parameter_and`: Excludes routes only if they do NOT have ALL of the specified parameters.
            Examples:
              - `category`, `product` (excludes routes that lack both `{category}` AND `{product}` parameters)

        The tool returns a message indicating the number of routes found and a `routes` object. The `routes` object is an array of dictionaries, where each dictionary represents a route and details its properties like `uri`, `name`, `methods`, `action`, `middleware`, and `parameters`.

        Note that any examples provided in the description are for demonstration purposes only and may not reflect the actual routes in the codebase.
        EOT;
    }

    /**
     * @param string[] $name_equals_or
     * @param string[] $name_doesnt_equal_or
     * @param string[] $name_doesnt_equal_and
     * @param string[] $uses_controller_or
     * @param string[] $doesnt_use_controller_or
     * @param string[] $uses_controller_and
     * @param string[] $doesnt_use_controller_and
     * @param string[] $uses_middleware_or
     * @param string[] $doesnt_use_middleware_or
     * @param string[] $uses_middleware_and
     * @param string[] $doesnt_use_middleware_and
     * @param string[] $has_parameter_or
     * @param string[] $doesnt_have_parameter_or
     * @param string[] $has_parameter_and
     * @param string[] $doesnt_have_parameter_and
     * @return array{'message': string, 'routes': string[]}
     */
    #[McpTool]
    public function queryRoutes(
        array $name_equals_or = [],
        array $name_doesnt_equal_or = [],
        array $name_doesnt_equal_and = [],
        array $uses_controller_or = [],
        array $doesnt_use_controller_or = [],
        array $uses_controller_and = [],
        array $doesnt_use_controller_and = [],
        array $uses_middleware_or = [],
        array $doesnt_use_middleware_or = [],
        array $uses_middleware_and = [],
        array $doesnt_use_middleware_and = [],
        array $has_parameter_or = [],
        array $doesnt_have_parameter_or = [],
        array $has_parameter_and = [],
        array $doesnt_have_parameter_and = [],
    ): array
    {
        $query = Introspect::routes();

        $this->applyOr(
            $query,
            $name_equals_or,
            fn (RouteQueryInterface $query, string $name) => $query->whereNameEquals($name)
        );

        $this->applyOr(
            $query,
            $name_doesnt_equal_or,
            fn (RouteQueryInterface $query, string $name) => $query->whereNameDoesntEqual($name)
        );

        $this->applyAnd(
            $query,
            $name_doesnt_equal_and,
            fn (RouteQueryInterface $query, string $name) => $query->whereNameDoesntEqual($name)
        );

        $this->applyOr(
            $query,
            $uses_controller_or,
            fn (RouteQueryInterface $query, string $name) => $query->whereUsesController($name)
        );

        $this->applyOr(
            $query,
            $doesnt_use_controller_or,
            fn (RouteQueryInterface $query, string $name) => $query->whereDoesntUseController($name)
        );

        $this->applyAnd(
            $query,
            $uses_controller_and,
            fn (RouteQueryInterface $query, string $name) => $query->whereUsesController($name)
        );

        $this->applyAnd(
            $query,
            $doesnt_use_controller_and,
            fn (RouteQueryInterface $query, string $name) => $query->whereDoesntUseController($name)
        );

        $this->applyOr(
            $query,
            $uses_middleware_or,
            fn (RouteQueryInterface $query, string $name) => $query->whereUsesMiddleware($name)
        );

        $this->applyOr(
            $query,
            $doesnt_use_middleware_or,
            fn (RouteQueryInterface $query, string $name) => $query->whereDoesntUseMiddleware($name)
        );

        $this->applyAnd(
            $query,
            $uses_middleware_and,
            fn (RouteQueryInterface $query, string $name) => $query->whereUsesMiddleware($name)
        );

        $this->applyAnd(
            $query,
            $doesnt_use_middleware_and,
            fn (RouteQueryInterface $query, string $name) => $query->whereDoesntUseMiddleware($name)
        );

        $this->applyOr(
            $query,
            $has_parameter_or,
            fn (RouteQueryInterface $query, string $name) => $query->whereHasParameter($name)
        );

        $this->applyOr(
            $query,
            $doesnt_have_parameter_or,
            fn (RouteQueryInterface $query, string $name) => $query->whereDoesntHaveParameter($name)
        );

        $this->applyAnd(
            $query,
            $has_parameter_and,
            fn (RouteQueryInterface $query, string $name) => $query->whereHasParameter($name)
        );

        $this->applyAnd(
            $query,
            $doesnt_have_parameter_and,
            fn (RouteQueryInterface $query, string $name) => $query->whereDoesntHaveParameter($name)
        );


        $results = $query->get();

        return [
            'message' => $results->isEmpty()
                ? 'No routes found.'
                : "Found {$results->count()} routes.",
            'routes' => $results
                ->map(fn (\Illuminate\Routing\Route $route) => Route::fromRoute($route)->toArray())
                ->all()
        ];
    }
}
