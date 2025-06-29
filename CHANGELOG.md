# Changelog

All notable changes to `mateffy/laravel-codebase-mcp` will be documented in this file.

## 0.0.3 (_2025-06-29_)

- Temporary fix for dependency issue in `php-mcp/server` and ReactPHP. Using forked version of `php-mcp/server` to resolve the issue.
  You might need to add the following repositories to the `composer.json` file:
  ```json
  "repositories": [
      {
          "type": "vcs",
          "url": "https://github.com/leantime/php-mcp-server.git"
      },
      {
          "type": "vcs",
          "url": "https://github.com/Leantime/reactphp-http.git"
      }
  ]
  ```

## 0.0.2 (_2025-05-22_)

- Fix missing parameter documentation

## 0.0.1 (_2025-05-22_)

- Initial release of the package (technically still in beta)
