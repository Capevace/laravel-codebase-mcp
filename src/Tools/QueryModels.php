<?php

namespace Mateffy\LaravelCodebaseMcp\Tools;

use Mateffy\Introspect\DTO\Model;
use Mateffy\Introspect\Facades\Introspect;
use Mateffy\Introspect\Query\Contracts\ModelQueryInterface;
use Mateffy\Introspect\Query\ModelQuery;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;

class QueryModels
{
    use ApplyToQuery;

    public static function name(): string
    {
        return 'query_laravel_models';
    }

    public static function description(): string
    {
        return <<<EOT
        Query the models in the codebase by applying various filters based on their names, properties, relations, implemented interfaces, or used traits. If no filters are provided, all models will be returned.

        For most filter categories, you can provide multiple values using `_or` parameters. These values are checked with OR logic, meaning a model will match if it satisfies any of the conditions within that specific parameter (e.g., `name_equals_or: ['User', 'Post']` will match models named "User" OR "Post"). Filters applied via `_and` parameters require all provided values to be present (e.g., `has_properties_and: ['name', 'email']` will match models that have both 'name' AND 'email' properties). All distinct filter parameters (e.g., `name_equals_or` and `has_properties_or`) are checked with AND logic, meaning a model must satisfy conditions from all provided filter categories.

        Wildcards (`*`) are only supported for model name and class/interface/trait filters. For property and relation filters exact matching is required.
        When specifying interfaces or traits, you must provide the full classified class string (e.g., `\Illuminate\Contracts\Auth\Authenticatable`).
        For class, interface or trait strings, you can also use wildcards (`*`) to match any part of the class name.
        Property names must be exact.

        ## Name Filters (Supports Wildcards)
        These filters operate on the fully qualified class name of the model. Wildcards (`*`) can be used.

        *   `name_equals_or`: Includes models whose fully qualified class name matches ANY of the provided values.
            Examples:
              - `App\Models\User` (matches the "User" model in `App\Models` namespace)
              - `App\Models\*` (matches all models directly in the `App\Models` namespace)
              - `*User` (matches any model ending with "User", e.g., `App\Models\User`, `Package\AuthUser`)

        *   `name_doesnt_equal_or`: Excludes models whose fully qualified class name matches ANY of the provided values.
            Examples:
              - `App\Models\AdminUser` (excludes the `App\Models\AdminUser` model)
              - `Package\Models\*` (excludes all models in the `Package\Models` namespace)

        *   `name_doesnt_equal_and`: Excludes models only if their fully qualified class name matches ALL of the provided values. (This is generally used for more complex exclusion scenarios where a model must satisfy multiple negative conditions simultaneously).
            Examples:
              - `App\Models\User`, `*Legacy*` (excludes models that are `App\Models\User` AND also have "Legacy" in their name, if such a combination were to exist)

        ## Property Filters (Exact Match Only)
        These filters operate on the exact names of properties defined on the model (e.g., database columns). Wildcards are NOT supported.

        *   `has_properties_or`: Includes models that have ANY of the specified properties.
            Examples:
              - `id`, `uuid` (matches models that have either an `id` or a `uuid` property)

        *   `doesnt_have_properties_or`: Excludes models that have ANY of the specified properties.
            Examples:
              - `created_at`, `updated_at` (excludes models that have either `created_at` or `updated_at` properties)

        *   `has_properties_and`: Includes models that have ALL of the specified properties.
            Examples:
              - `first_name`, `last_name`, `email` (matches models that have `first_name` AND `last_name` AND `email` properties)

        *   `doesnt_have_properties_and`: Excludes models only if they do NOT have ALL of the specified properties. (This is generally used for more complex exclusion scenarios).
            Examples:
              - `is_active`, `deleted_at` (excludes models that lack `is_active` AND lack `deleted_at`)

        ## Fillable Property Filters (Exact Match Only)
        These filters target properties explicitly marked as fillable in the fillable array. Wildcards are NOT supported.

        *   `has_fillable_properties_or`: Includes models that have ANY of the specified fillable properties.
            Examples:
              - `name`, `title` (matches models where either `name` or `title` is fillable)

        *   `doesnt_have_fillable_properties_or`: Excludes models that have ANY of the specified fillable properties.
            Examples:
              - `password`, `remember_token` (excludes models where either `password` or `remember_token` is fillable)

        *   `has_fillable_properties_and`: Includes models that have ALL of the specified fillable properties.
            Examples:
              - `email`, `address` (matches models where both `email` AND `address` are fillable)

        *   `doesnt_have_fillable_properties_and`: Excludes models only if they do NOT have ALL of the specified fillable properties.
            Examples:
              - `status`, `notes` (excludes models that lack `status` AND lack `notes` in their fillable properties)

        ## Hidden Property Filters (Exact Match Only)
        These filters target properties explicitly marked as hidden in the hidden array. Wildcards are NOT supported.

        *   `has_hidden_properties_or`: Includes models that have ANY of the specified hidden properties.
            Examples:
              - `password`, `api_token` (matches models where either `password` or `api_token` is hidden)

        *   `doesnt_have_hidden_properties_or`: Excludes models that have ANY of the specified hidden properties.
            Examples:
              - `id`, `name` (excludes models where either `id` or `name` is hidden)

        *   `has_hidden_properties_and`: Includes models that have ALL of the specified hidden properties.
            Examples:
              - `password`, `two_factor_secret` (matches models where both `password` AND `two_factor_secret` are hidden)

        *   `doesnt_have_hidden_properties_and`: Excludes models only if they do NOT have ALL of the specified hidden properties.
            Examples:
              - `created_at`, `updated_at` (excludes models that lack `created_at` AND lack `updated_at` in their hidden properties)

        ## Relation Filters (Exact Match Only)
        These filters operate on the names of relationships defined on the model. Wildcards are NOT supported.

        *   `has_relations_or`: Includes models that have ANY of the specified relationships.
            Examples:
              - `posts`, `comments` (matches models with either a `posts` or `comments` relationship)

        *   `doesnt_have_relations_or`: Excludes models that have ANY of the specified relationships.
            Examples:
              - `user`, `profile` (excludes models that have either a `user` or `profile` relationship)

        *   `has_relations_and`: Includes models that have ALL of the specified relationships.
            Examples:
              - `orders`, `products` (matches models that have both `orders` AND `products` relationships)

        *   `doesnt_have_relations_and`: Excludes models only if they do NOT have ALL of the specified relationships.
            Examples:
              - `roles`, `permissions` (excludes models that lack both `roles` AND `permissions` relationships)

        ## Interface Filters
        These filters operate on interfaces implemented by the model. You must provide the full classified class string for the interface (e.g., `\Illuminate\Contracts\Auth\Authenticatable`). Wildcards are NOT supported.

        *   `implements_interfaces_or`: Includes models that implement ANY of the specified interfaces.
            Examples:
              - `\Illuminate\Contracts\Auth\Authenticatable`, `\Illuminate\Contracts\Auth\Access\Authorizable` (matches models implementing either `Authenticatable` or `Authorizable`)
              - `*Authenticatable` (matches models implementing any interface that ends with `Authenticatable`)
              - `*Auth*` (matches models implementing any interface that contains `Auth`)

        *   `doesnt_implement_interfaces_or`: Excludes models that implement ANY of the specified interfaces.
            Examples:
              - `\Illuminate\Contracts\Queue\ShouldQueue`, `\JsonSerializable` (excludes models implementing either `ShouldQueue` or `JsonSerializable`)
              - `*Queue*` (excludes models implementing any interface that contains `Queue`)

        *   `implements_interfaces_and`: Includes models that implement ALL of the specified interfaces.
            Examples:
              - `\Illuminate\Contracts\Auth\Authenticatable`, `\Illuminate\Contracts\Auth\Access\Authorizable` (matches models implementing both `Authenticatable` AND `Authorizable`)

        *   `doesnt_implement_interfaces_and`: Excludes models only if they do NOT implement ALL of the specified interfaces.
            Examples:
              - `\ArrayAccess`, `\Countable` (excludes models that lack both `ArrayAccess` AND `Countable` implementations)

        ## Trait Filters
        These filters operate on traits used by the model. You must provide the full classified class string for the trait (e.g., `\Illuminate\Database\Eloquent\Factories\HasFactory`). Wildcards are NOT supported.

        *   `uses_traits_or`: Includes models that use ANY of the specified traits.
            Examples:
              - `\Illuminate\Notifications\Notifiable`, `\Illuminate\Database\Eloquent\SoftDeletes` (matches models using either the `Notifiable` or `SoftDeletes` trait)
              - `*Notifiable` (matches models using any trait that ends with `Notifiable`)

        *   `doesnt_use_traits_or`: Excludes models that use ANY of the specified traits.
            Examples:
              - `\Illuminate\Database\Eloquent\Factories\HasFactory`, `\Illuminate\Database\Eloquent\Concerns\HasUuids` (excludes models using either `HasFactory` or `HasUuids` traits)

        *   `uses_traits_and`: Includes models that use ALL of the specified traits.
            Examples:
              - `\Illuminate\Notifications\Notifiable`, `\Illuminate\Database\Eloquent\Factories\HasFactory` (matches models using both `Notifiable` AND `HasFactory` traits)
            - `*Notifiable`, `*HasFactory` (matches models using any trait that ends with `Notifiable` AND any trait that ends with `HasFactory`)

        *   `doesnt_use_traits_and`: Excludes models only if they do NOT use ALL of the specified traits.
            Examples:
              - `\App\Traits\Auditable`, `\App\Traits\Sluggable` (excludes models that lack both `Auditable` AND `Sluggable` traits)

        *   `extends_classes_or`: Includes models that extend ANY of the specified classes.
            Examples:
              - `\Illuminate\Foundation\Auth\User` (matches models extending either `Model` or `User`)
              - `*User` (matches models extending any class that ends with `User`)

        *   `doesnt_extend_classes_or`: Excludes models that extend ANY of the specified classes.
            Examples:
              - `\Illuminate\Foundation\Auth\User` (excludes models extending either `Model` or `User`)

        *   `doesnt_extend_classes_and`: Excludes models only if they do NOT extend ALL of the specified classes.
            Examples:
              - `\Illuminate\Foundation\Auth\User` (excludes models that lack both `Model` AND `User` classes)

        The tool returns a message indicating the number of models found and a `models` object. The `models` object is a dictionary where keys are the fully qualified class paths of the found models (e.g., `App\Models\User`) and values are the JSON schema representations of each model, detailing its properties, relations, and other attributes.

        Note that any examples provided in the description are for demonstration purposes only and may not reflect the actual models in the codebase.
        EOT;
    }

    /**
     * @param string[] $name_equals_or
     * @param string[] $name_doesnt_equal_or
     * @param string[] $name_doesnt_equal_and
     * @param string[] $has_properties_or
     * @param string[] $doesnt_have_properties_or
     * @param string[] $has_properties_and
     * @param string[] $doesnt_have_properties_and
     * @param string[] $has_relations_or
     * @param string[] $doesnt_have_relations_or
     * @param string[] $has_relations_and
     * @param string[] $doesnt_have_relations_and
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
     * @return array{'message': string, 'views': string[]}
     */
    #[McpTool]
    public function queryModels(
        array $name_equals_or = [],
        array $name_doesnt_equal_or = [],
        array $name_doesnt_equal_and = [],
        array $has_properties_or = [],
        array $doesnt_have_properties_or = [],
        array $has_properties_and = [],
        array $doesnt_have_properties_and = [],
//        array $has_fillable_properties_or = [],
//        array $doesnt_have_fillable_properties_or = [],
//        array $has_fillable_properties_and = [],
//        array $doesnt_have_fillable_properties_and = [],
//        array $has_hidden_properties_or = [],
//        array $doesnt_have_hidden_properties_or = [],
//        array $has_hidden_properties_and = [],
//        array $doesnt_have_hidden_properties_and = [],

//        array $has_guarded_properties_or = [],
//        array $doesnt_have_guarded_properties_or = [],
//        array $has_guarded_properties_and = [],
//        array $doesnt_have_guarded_properties_and = [],
        array $has_relations_or = [],
        array $doesnt_have_relations_or = [],
        array $has_relations_and = [],
        array $doesnt_have_relations_and = [],

        // Classes
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
        /** @var ModelQuery $query */
        $query = Introspect::models();

        // Name
        $this->applyOr(
            $query,
            $name_equals_or,
            fn (ModelQueryInterface $query, string $name) => $query->whereNameEquals($name)
        );

        $this->applyOr(
            $query,
            $name_doesnt_equal_or,
            fn (ModelQueryInterface $query, string $name) => $query->whereNameDoesntEqual($name)
        );

        $this->applyAnd(
            $query,
            $name_doesnt_equal_and,
            fn (ModelQueryInterface $query, string $name) => $query->whereNameDoesntEqual($name)
        );

        // Properties
        $this->applyOr(
            $query,
            $has_properties_or,
            fn (ModelQueryInterface $query, string $name) => $query->whereHasProperty($name)
        );

        $this->applyOr(
            $query,
            $doesnt_have_properties_or,
            fn (ModelQueryInterface $query, string $name) => $query->whereDoesntHaveProperty($name)
        );

        $this->applyAnd(
            $query,
            $has_properties_and,
            fn (ModelQueryInterface $query, string $name) => $query->whereHasProperty($name)
        );

        $this->applyAnd(
            $query,
            $doesnt_have_properties_and,
            fn (ModelQueryInterface $query, string $name) => $query->whereDoesntHaveProperty($name)
        );

        // Fillable
//        $this->applyOr(
//            $query,
//            $has_fillable_properties_or,
//            fn (ModelQueryInterface $query, string $name) => $query->whereHasFillable($name)
//        );
//
//        $this->applyOr(
//            $query,
//            $doesnt_have_fillable_properties_or,
//            fn (ModelQueryInterface $query, string $name) => $query->whereDoesntHaveFillable($name)
//        );
//
//        $this->applyAnd(
//            $query,
//            $has_fillable_properties_and,
//            fn (ModelQueryInterface $query, string $name) => $query->whereHasFillable($name)
//        );
//
//        $this->applyAnd(
//            $query,
//            $doesnt_have_fillable_properties_and,
//            fn (ModelQueryInterface $query, string $name) => $query->whereDoesntHaveFillable($name)
//        );
//
//        // Hidden
//        $this->applyOr(
//            $query,
//            $has_hidden_properties_or,
//            fn (ModelQueryInterface $query, string $name) => $query->whereHasHidden($name)
//        );
//
//        $this->applyOr(
//            $query,
//            $doesnt_have_hidden_properties_or,
//            fn (ModelQueryInterface $query, string $name) => $query->whereDoesntHaveHidden($name)
//        );
//
//        $this->applyAnd(
//            $query,
//            $has_hidden_properties_and,
//            fn (ModelQueryInterface $query, string $name) => $query->whereHasHidden($name)
//        );
//
//        $this->applyAnd(
//            $query,
//            $doesnt_have_hidden_properties_and,
//            fn (ModelQueryInterface $query, string $name) => $query->whereDoesntHaveHidden($name)
//        );

        // Relations
        $this->applyOr(
            $query,
            $has_relations_or,
            fn (ModelQueryInterface $query, string $name) => $query->whereHasRelationship($name)
        );

        $this->applyOr(
            $query,
            $doesnt_have_relations_or,
            fn (ModelQueryInterface $query, string $name) => $query->whereDoesntHaveRelationship($name)
        );

        $this->applyAnd(
            $query,
            $has_relations_and,
            fn (ModelQueryInterface $query, string $name) => $query->whereHasRelationship($name)
        );

        $this->applyAnd(
            $query,
            $doesnt_have_relations_and,
            fn (ModelQueryInterface $query, string $name) => $query->whereDoesntHaveRelationship($name)
        );


        // Classes
        $this->applyOr(
            $query,
            $implements_interfaces_or,
            fn (ModelQueryInterface $query, string $name) => $query->whereImplements($name)
        );

        $this->applyOr(
            $query,
            $doesnt_implement_interfaces_or,
            fn (ModelQueryInterface $query, string $name) => $query->whereDoesntImplement($name)
        );

        $this->applyAnd(
            $query,
            $implements_interfaces_and,
            fn (ModelQueryInterface $query, string $name) => $query->whereImplements($name)
        );

        $this->applyAnd(
            $query,
            $doesnt_implement_interfaces_and,
            fn (ModelQueryInterface $query, string $name) => $query->whereDoesntImplement($name)
        );

        $this->applyOr(
            $query,
            $uses_traits_or,
            fn (ModelQueryInterface $query, string $name) => $query->whereUses($name)
        );

        $this->applyOr(
            $query,
            $doesnt_use_traits_or,
            fn (ModelQueryInterface $query, string $name) => $query->whereDoesntUse($name)
        );

        $this->applyAnd(
            $query,
            $uses_traits_and,
            fn (ModelQueryInterface $query, string $name) => $query->whereUses($name)
        );

        $this->applyAnd(
            $query,
            $doesnt_use_traits_and,
            fn (ModelQueryInterface $query, string $name) => $query->whereDoesntUse($name)
        );

        $this->applyOr(
            $query,
            $extends_classes_or,
            fn (ModelQueryInterface $query, string $name) => $query->whereExtends($name)
        );

        $this->applyOr(
            $query,
            $doesnt_extend_classes_or,
            fn (ModelQueryInterface $query, string $name) => $query->whereDoesntExtend($name)
        );

        $this->applyAnd(
            $query,
            $doesnt_extend_classes_and,
            fn (ModelQueryInterface $query, string $name) => $query->whereDoesntExtend($name)
        );

        $results = $query->get();

        return [
            'message' => $results->isEmpty()
                ? 'No models found.'
                : "Found {$results->count()} models.",
            'models' => $results
                ->mapWithKeys(fn (Model $model) => [$model->classpath => $model->schema()])
                ->all()
        ];
    }
}
