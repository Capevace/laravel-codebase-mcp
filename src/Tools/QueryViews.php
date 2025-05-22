<?php

namespace Mateffy\LaravelCodebaseMcp\Tools;

use Illuminate\Support\Facades\Config;
use Mateffy\Introspect\Facades\Introspect;
use Mateffy\Introspect\Query\Contracts\ViewQueryInterface;
use Mateffy\Introspect\Query\ViewQuery;
use PhpMcp\Server\Attributes\McpResource;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;

class QueryViews
{
    public static function name(): string
    {
        return 'query_laravel_views';
    }

    public static function description(): string
    {
        return <<<EOT
        Query the views in the Laravel codebase by applying filters using view names. Views from libraries and packages are also included in the query. If no filters are provided, all views will be returned, including those from libraries and packages.

        You can provide multiple values for each filter parameter, which are checked with OR logic (e.g., if you provide `['foo', 'bar']` for `name_equals`, views matching 'foo' OR 'bar' will be included). However, the filters themselves are checked with AND logic (e.g., if you use both `name_equals` and `uses`, the results must satisfy *both* conditions).

        All name matchers support wildcards (`*`) and will match against absolute view strings, including namespaces (e.g., `filament::*`) and component prefixes (`components...` for `<x-bla-bla />` components). Views named `index` that are placed in a subdirectory will also match on the subdirectory name (e.g., `filament::*button` will match `filament::components.button.index`).

        ## name_equals
        Adds a filter to only include views that match ANY of the specified names.
        Examples:
          - `welcome` (matches "welcome")
          - `pages.welcome` (matches "pages.welcome")
          - `pages.*` (matches all views in the "pages" directory like "pages.home", "pages.about")
          - `pages*` (matches views starting with "pages" like "pages.home" but also "pageselector")
          - `components.ui.button` (matches "components.ui.button")
          - `components*button` (matches all components with "button" at the end in the app's namespace)
          - `components.*.button` (matches all components in a subdirectory with "button" at the end in the app's namespace)
          - `filament::*` (matches all views in the "filament" namespace)
          - `filament::components.*` (matches all views in the "filament" namespace that are components)

        ## name_doesnt_equal
        Adds a filter to only include views that do NOT match ANY of the specified names. This filter works exactly like `name_equals`, but in reverse: any views that match ANY of the specified names will be excluded.
        Examples:
          - `filament::*` (excludes all views in the "filament" namespace)
          - `*::components*` (excludes components in any namespace)
          - `*button*` (excludes all views with "button" in the name)

        ## uses
        Adds a filter to only include views that use the specified views, either via `@include`/`@component` directives or via `<x-bla-bla />` component syntax. The name logic for matching used views works the same as `name_equals`.
        Examples:
          - `partials.*` (matches all views that use any partials)
          - `components.ui.button` (matches all views that use a button component)
          - `filament::components.button` (matches all views that use the Filament button component)
          - `filament::*` (matches all views that use any views from the Filament package)

        ## doesnt_use
        Adds a filter to only include views that do NOT use the specified views, either via `@include`/`@component` directives or via `<x-bla-bla />` component syntax. This filter works exactly like `uses`, but in reverse: any views that use ANY of the specified names will be excluded.
        Examples:
          - `partials.*` (excludes all views that use any partials)
          - `components.ui.button` (excludes all views that use a button component)
          - `filament::components.button` (excludes all views that use the Filament button component)
          - `filament::*` (excludes all views that use any views from the Filament package)

        ## used_by
        Adds a filter to only include views that are used by views matching the values provided. For example, you can query for all components that are used on a specific page by including the page name here. Note that this only works one level deep and will not include any views used by views that are themselves used by the views that match the values provided. The name logic for matching views that use the current view works the same as `name_equals`.
        Examples:
          - `pages.admin.*` (matches all views that are used by any admin pages).

        ## not_used_by
        Adds a filter to only include views that are NOT used by views matching the values provided. This filter works exactly like `used_by`, but in reverse: any views that are used by ANY of the specified names will be excluded.
        Examples:
          - `pages.admin.*` (excludes all views that are used by any admin pages).

        Note that any examples provided in the description are for demonstration purposes only and may not reflect the actual views in the codebase.
        EOT;
    }
    /**
     * @param string[] $name_equals
     * @param string[] $name_doesnt_equal
     * @param string[] $used_by
     * @param string[] $not_used_by
     * @param string[] $uses
     * @param string[] $doesnt_use
     * @return array{'message': string, 'views': string[]}
     */
    #[McpTool]
    public function queryViews(
        array $name_equals = [],
        array $name_doesnt_equal = [],
        array $used_by = [],
        array $not_used_by = [],
        array $uses = [],
        array $doesnt_use = [],
    ): array
    {
        /** @var ViewQuery $query */
        $query = Introspect::views();

        if (count($name_equals) > 0) {
            $query->whereNameEquals($name_equals, all: false);
        }

        if (count($name_doesnt_equal) > 0) {
            $query->whereNameDoesntEqual($name_doesnt_equal, all: false);
        }

        if (count($uses) > 0) {
            $query->or(function (ViewQueryInterface $query) use ($uses) {
                foreach ($uses as $name) {
                    $query->whereUses($name);
                }

                return $query;
            });
        }

        if (count($doesnt_use) > 0) {
            $query->or(function (ViewQueryInterface $query) use ($doesnt_use) {
                foreach ($doesnt_use as $name) {
                    $query->whereDoesntUse($name);
                }

                return $query;
            });
        }

        if (count($used_by) > 0) {
            $query->or(function (ViewQueryInterface $query) use ($used_by) {
                foreach ($used_by as $name) {
                    $query->whereUsedBy($name);
                }

                return $query;
            });
        }

        if (count($not_used_by) > 0) {
            $query->or(function (ViewQueryInterface $query) use ($not_used_by) {
                foreach ($not_used_by as $name) {
                    $query->whereNotUsedBy($name);
                }

                return $query;
            });
        }

        $results = $query->get();

        return [
            'message' => $results->isEmpty()
                ? 'No views found.'
                : "Found {$results->count()} views.",
            'views' => $results->all()
        ];
    }
}
