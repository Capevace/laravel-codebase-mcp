<?php

namespace Mateffy\LaravelCodebaseMcp\Tools;

use Mateffy\Introspect\Facades\Introspect;
use Mateffy\Introspect\Query\Contracts\ClassQueryInterface;
use Mateffy\Introspect\Query\ClassQuery;
use PhpMcp\Server\Attributes\McpTool;

class QueryClasses
{
    use ApplyToQuery;

    public static function name(): string
    {
        return 'query_laravel_classes';
    }

    public static function description(): string
    {
        return <<<EOT
        Query the classes in the codebase by applying various filters based on their fully qualified names, implemented interfaces, extended classes, or used traits. If no filters are provided, all classes will be returned.

        For most filter categories, you can provide multiple values using `_or` parameters. These values are checked with OR logic, meaning a class will match if it satisfies any of the conditions within that specific parameter (e.g., `name_equals_or: ['UserController', 'PostController']` will match classes named "UserController" OR "PostController"). Filters applied via `_and` parameters require all provided values to be present (e.g., `implements_interfaces_and: ['\Illuminate\Contracts\Queue\ShouldQueue', '\Illuminate\Contracts\Support\Renderable']` will match classes that implement both `ShouldQueue` AND `Renderable` interfaces). All distinct filter parameters (e.g., `name_equals_or` and `implements_interfaces_or`) are checked with AND logic, meaning a class must satisfy conditions from all provided filter categories.

        Wildcards (`*`) are supported for all filters, matching against the fully qualified class name, interface name, trait name or extended class name.

        ## Name Filters (Supports Wildcards)
        These filters operate on the fully qualified class name. Wildcards (`*`) can be used.

        *   `name_equals_or`: Includes classes whose fully qualified name matches ANY of the provided values.
            Examples:
              - `App\Http\Controllers\UserController` (matches the "UserController" class in `App\Http\Controllers` namespace)
              - `App\Http\Controllers\*` (matches all classes directly in the `App\Http\Controllers` namespace)
              - `*Controller` (matches any class ending with "Controller", e.g., `App\Http\Controllers\UserController`, `Package\AuthController`)

        *   `name_doesnt_equal_or`: Excludes classes whose fully qualified name matches ANY of the provided values.
            Examples:
              - `App\Http\Middleware\Authenticate` (excludes the `App\Http\Middleware\Authenticate` class)
              - `Package\Services\*` (excludes all classes in the `Package\Services` namespace)

        *   `name_doesnt_equal_and`: Excludes classes only if their fully qualified name matches ALL of the provided values. (This is generally used for more complex exclusion scenarios where a class must satisfy multiple negative conditions simultaneously).
            Examples:
              - `App\Exceptions\Handler`, `*Legacy*` (excludes classes that are `App\Exceptions\Handler` AND also have "Legacy" in their name, if such a combination were to exist)

        ## Interface Filters (Supports Wildcards)
        These filters operate on interfaces implemented by the class. You must provide the full classified class string for the interface (e.g., `\Illuminate\Contracts\Auth\Authenticatable`). Wildcards can be used.

        *   `implements_interfaces_or`: Includes classes that implement ANY of the specified interfaces.
            Examples:
              - `\Illuminate\Contracts\Auth\Authenticatable`, `\Illuminate\Contracts\Auth\Access\Authorizable` (matches classes implementing either `Authenticatable` or `Authorizable`)
              - `*Authenticatable` (matches classes implementing any interface that ends with `Authenticatable`)
              - `*Auth*` (matches classes implementing any interface that contains `Auth`)

        *   `doesnt_implement_interfaces_or`: Excludes classes that implement ANY of the specified interfaces.
            Examples:
              - `\Illuminate\Contracts\Queue\ShouldQueue`, `\JsonSerializable` (excludes classes implementing either `ShouldQueue` or `JsonSerializable`)
              - `*Queue*` (excludes classes implementing any interface that contains `Queue`)

        *   `implements_interfaces_and`: Includes classes that implement ALL of the specified interfaces.
            Examples:
              - `\Illuminate\Contracts\Auth\Authenticatable`, `\Illuminate\Contracts\Auth\Access\Authorizable` (matches classes implementing both `Authenticatable` AND `Authorizable`)

        *   `doesnt_implement_interfaces_and`: Excludes classes only if they do NOT implement ALL of the specified interfaces.
            Examples:
              - `\ArrayAccess`, `\Countable` (excludes classes that lack both `ArrayAccess` AND `Countable` implementations)

        ## Trait Filters (Supports Wildcards)
        These filters operate on traits used by the class. You must provide the full classified class string for the trait (e.g., `\Illuminate\Database\Eloquent\Factories\HasFactory`). Wildcards can be used.

        *   `uses_traits_or`: Includes classes that use ANY of the specified traits.
            Examples:
              - `\Illuminate\Notifications\Notifiable`, `\Illuminate\Database\Eloquent\SoftDeletes` (matches classes using either the `Notifiable` or `SoftDeletes` trait)
              - `*Notifiable` (matches classes using any trait that ends with `Notifiable`)

        *   `doesnt_use_traits_or`: Excludes classes that use ANY of the specified traits.
            Examples:
              - `\Illuminate\Database\Eloquent\Factories\HasFactory`, `\Illuminate\Database\Eloquent\Concerns\HasUuids` (excludes classes using either `HasFactory` or `HasUuids` traits)

        *   `uses_traits_and`: Includes classes that use ALL of the specified traits.
            Examples:
              - `\Illuminate\Notifications\Notifiable`, `\Illuminate\Database\Eloquent\Factories\HasFactory` (matches classes using both `Notifiable` AND `HasFactory` traits)
            - `*Notifiable`, `*HasFactory` (matches classes using any trait that ends with `Notifiable` AND any trait that ends with `HasFactory`)

        *   `doesnt_use_traits_and`: Excludes classes only if they do NOT use ALL of the specified traits.
            Examples:
              - `\App\Traits\Auditable`, `\App\Traits\Sluggable` (excludes classes that lack both `Auditable` AND `Sluggable` traits)

        ## Extends Class Filters (Supports Wildcards)

        These filters operate on classes extended by other classes. You must provide the full classified class string for the extended class (e.g., `\Illuminate\Foundation\Auth\User`). Wildcards can be used.

        *   `extends_classes_or`: Includes classes that extend ANY of the specified classes.
            Examples:
              - `\Illuminate\Foundation\Auth\User` (matches classes extending either `Model` or `User`)
              - `*User` (matches classes extending any class that ends with `User`)

        *   `doesnt_extend_classes_or`: Excludes classes that extend ANY of the specified classes.
            Examples:
              - `\Illuminate\Foundation\Auth\User` (excludes classes extending either `Model` or `User`)

        *   `doesnt_extend_classes_and`: Excludes classes only if they do NOT extend ALL of the specified classes.
            Examples:
              - `\Illuminate\Foundation\Auth\User` (excludes classes that lack both `Model` AND `User` classes)

        The tool returns a message indicating the number of classes found and a `classes` object. The `classes` object is a dictionary where keys are the fully qualified class paths of the found classes (e.g., `App\Models\User`) and values are the ClassData DTO  of each class.

        Note that any examples provided in the description are for demonstration purposes only and may not reflect the actual classes in the codebase.
        EOT;
    }

    /**
     * @param string[] $name_equals_or
     * @param string[] $name_doesnt_equal_or
     * @param string[] $implements_interfaces_or
     * @param string[] $doesnt_implement_interfaces_or
     * @param string[] $implements_interfaces_and
     * @param string[] $doesnt_implement_interfaces_and
     * @param string[] $uses_traits_or
     * @param string[] $doesnt_use_traits_or
     * @param string[] $uses_traits_and
     * @param string[] $doesnt_use_traits_and
     * @param string[] $extends_classes_or
     * @param string[] $doesnt_extend_classes_or
     * @param string[] $doesnt_extend_classes_and
     * @return array{'message': string, 'classes': string[]}
     */
    #[McpTool]
    public function queryClasses(
        array $name_equals_or = [],
        array $name_doesnt_equal_or = [],
        array $name_doesnt_equal_and = [],
        array $implements_interfaces_or = [],
        array $doesnt_implement_interfaces_or = [],
        array $implements_interfaces_and = [],
        array $doesnt_implement_interfaces_and = [],
        array $uses_traits_or = [],
        array $doesnt_use_traits_or = [],
        array $uses_traits_and = [],
        array $doesnt_use_traits_and = [],
        array $extends_classes_or = [],
        array $doesnt_extend_classes_or = [],
        array $doesnt_extend_classes_and = [],
    ): array
    {
        /** @var ClassQuery $query */
        $query = Introspect::classes();

        // Name
        $this->applyOr(
            $query,
            $name_equals_or,
            fn (ClassQueryInterface $query, string $name) => $query->whereNameEquals($name)
        );

        $this->applyOr(
            $query,
            $name_doesnt_equal_or,
            fn (ClassQueryInterface $query, string $name) => $query->whereNameDoesntEqual($name)
        );

        $this->applyAnd(
            $query,
            $name_doesnt_equal_and,
            fn (ClassQueryInterface $query, string $name) => $query->whereNameDoesntEqual($name)
        );

        // Classes
        $this->applyOr(
            $query,
            $implements_interfaces_or,
            fn (ClassQueryInterface $query, string $name) => $query->whereImplements($name)
        );

        $this->applyOr(
            $query,
            $doesnt_implement_interfaces_or,
            fn (ClassQueryInterface $query, string $name) => $query->whereDoesntImplement($name)
        );

        $this->applyAnd(
            $query,
            $implements_interfaces_and,
            fn (ClassQueryInterface $query, string $name) => $query->whereImplements($name)
        );

        $this->applyAnd(
            $query,
            $doesnt_implement_interfaces_and,
            fn (ClassQueryInterface $query, string $name) => $query->whereDoesntImplement($name)
        );

        $this->applyOr(
            $query,
            $uses_traits_or,
            fn (ClassQueryInterface $query, string $name) => $query->whereUses($name)
        );

        $this->applyOr(
            $query,
            $doesnt_use_traits_or,
            fn (ClassQueryInterface $query, string $name) => $query->whereDoesntUse($name)
        );

        $this->applyAnd(
            $query,
            $uses_traits_and,
            fn (ClassQueryInterface $query, string $name) => $query->whereUses($name)
        );

        $this->applyAnd(
            $query,
            $doesnt_use_traits_and,
            fn (ClassQueryInterface $query, string $name) => $query->whereDoesntUse($name)
        );

        $this->applyOr(
            $query,
            $extends_classes_or,
            fn (ClassQueryInterface $query, string $name) => $query->whereExtends($name)
        );

        $this->applyOr(
            $query,
            $doesnt_extend_classes_or,
            fn (ClassQueryInterface $query, string $name) => $query->whereDoesntExtend($name)
        );

        $this->applyAnd(
            $query,
            $doesnt_extend_classes_and,
            fn (ClassQueryInterface $query, string $name) => $query->whereDoesntExtend($name)
        );

        $results = $query->get();

        return [
            'message' => $results->isEmpty()
                ? 'No classes found.'
                : "Found {$results->count()} classes.",
            'classes' => $results->all()
        ];
    }
}
