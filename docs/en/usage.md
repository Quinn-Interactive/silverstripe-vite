# Usage

## JavaScript and CSS Handling
To include a JavaScript or CSS file in your project:
```php
Vite::javascript('path/to/your/script.js');
Vite::css('path/to/your/style.css');
```

## Preloading Resources
To preload resources for performance optimization:
```php
Vite::preload('path/to/resource', 'asType', 'mimeType');
```

## Development Server
To manually initialize the Vite development server and React refresh:
```php
Vite::configDevServer($isReact = true);
```

## Methods Overview
- `javascript(string $file, array $options = [])`: Registers and loads a JavaScript file.
- `css(string $file, ?string $media = null, array $options = [])`: Registers and loads a CSS file.
- `preload(string $file, ?string $as = null, ?string $type = null)`: Preloads a specified resource.
- `resourcePath(string $resource)`: Resolves the path of a resource using the manifest.
- `resourceURL(string $resource)`: Resolves the URL of a resource using the manifest.

## Template Var

You can use the $Vite template var require css and js or include it inplace or inline.

By default, `<link>` or `<script>` tag is added inplace, where the template tag is.

You can mix and match output options (as appropriate)

- .Async - (js or css) add async attribute for js, or use js/media switching (with `<noscript>` fallback) for css
- .Defer - (js) add defer attribute
- .Inline - (js or css) output file inline with `<script>` or `<style>` tag
- .Media('speech') - (css) override default media attribite for css
- .Preload(0) - (css or js) whether to preload the resource (defaults to true). Not used for inline.
- .Type('something') - (js) override js type attribute (not used for inline)
- .Integrity('sha384-q8i/X+9...') - set integrity attribute (not used for inline)
- .Crossorigin('anonymous') - set crossorigin attribute (defaults to true, not used for inline)
- .Require - (js or css) instead of adding tag inplace, use Silverstripe's Requirements class to inject into page

Be careful not to use the .Require option for template parts that are inside of partial caching.

Usage examples:

```
<!-- insert css link tag into page -->
$Vite.CSS('src/scss/styles.scss')

<!-- add async loading to css link tag (with <noscript> fallback) -->
$Vite.CSS('src/scss/pages/ProductFolder.scss').Async

<!-- Use Silverstripe's Requirements class to inject css link into page (not good inside partial caching) -->
$Vite.CSS('src/scss/pages/ProductFolder.scss').Require

<!-- Add css with inline <style> tag -->
$Vite.CSS('src/scss/pages/ProductFolder.scss').Inline

<!-- insert js link tag into page -->
$Vite.JS('src/js/index.js')

<!-- insert js link tag into page with defer attribute -->
$Vite.JS('src/js/index.js').Defer

<!-- insert js link tag into page with async attribute -->
$Vite.JS('src/js/index.js').Async

<!-- Use Silverstripe's Requirements class js link tag into page -->
$Vite.JS('src/js/index.js').Require

<!-- Add js with inline <script> tag -->
$Vite.JS('src/js/index.js').Inline

<!-- Requirements js with async attribute  -->
$Vite.JS('src/js/index.js').Require.Async
```

## Development Tips
- Use the `isDevServerRunning()` method to conditionally run code only when the Vite server is active.
- Utilize the `getDevServerUrl()` and `getDevServerCheckUrl()` for custom integrations or checks in your development environment.
