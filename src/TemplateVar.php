<?php

namespace Somar\Vite;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Dev\Debug;
use SilverStripe\View\HTML;
use SilverStripe\View\TemplateGlobalProvider;
use SilverStripe\View\ViewableData;

/**
 * TemplateVar
 * 
 * Provide template vars Silverstripe can use to require css and js or include it inplace or inline.
 * By default, <link> or <script> tag is added inplace, where the template tag is.
 * 
 * You can mix and match output options (as appropriate)
 * 
 * .Async - (js or css) add async attribute for js, or use js/media switching (with <noscript> fallback) for css
 * .Defer - (js) add defer attribute
 * .Inline - (js or css) output file inline with <script> or <style> tag
 * .Media('speech') - (css) override default media attribite for css
 * .Preload(0) - (css or js) whether to preload the resource (defaults to true). Not used for inline.
 * .Type('something') - (js) override js type attribute (not used for inline)
 * .Integrity('sha384-q8i/X+9...') - set integrity attribute (not used for inline)
 * .Crossorigin('anonymous') - set crossorigin attribute (defaults to true, not used for inline)
 * .Require - (js or css) instead of adding tag inplace, use Silverstripe's Requirements class to inject into page
 * 
 * Be careful not to use the .Require option for template parts that are inside of partial caching.
 * 
 * Usage examples:
 * 
 * <!-- insert css link tag into page -->
 * $Vite.CSS('src/scss/styles.scss')
 * 
 * <!-- add async loading to css link tag (with <noscript> fallback) -->
 * $Vite.CSS('src/scss/pages/ProductFolder.scss').Async
 * 
 * <!-- Use Silverstripe's Requirements class to inject css link into page (not good inside partial caching) -->
 * $Vite.CSS('src/scss/pages/ProductFolder.scss').Require
 * 
 * <!-- Add css with inline <style> tag -->
 * $Vite.CSS('src/scss/pages/ProductFolder.scss').Inline
 * 
 * <!-- insert js link tag into page -->
 * $Vite.JS('src/js/index.js')
 * 
 * <!-- insert js link tag into page with defer attribute -->
 * $Vite.JS('src/js/index.js').Defer
 * 
 * <!-- insert js link tag into page with async attribute -->
 * $Vite.JS('src/js/index.js').Async
 * 
 * <!-- Use Silverstripe's Requirements class js link tag into page -->
 * $Vite.JS('src/js/index.js').Require
 * 
 * <!-- Add js with inline <script> tag -->
 * $Vite.JS('src/js/index.js').Inline
 * 
 * <!-- Requirements js with async attribute  -->
 * $Vite.JS('src/js/index.js').Require.Async
 * 
 */
class TemplateVar extends ViewableData implements TemplateGlobalProvider
{
    /**
     * kind of source css or js
     */
    private ?string $filetype = null;

    /**
     * media attribute, used for CSS
     */
    private ?string $media = null;

    /**
     * Output the file inline as a script or style tag
     */
    private bool $inline = false;

    /**
     * Use Requirements system to add tags instead of writing them in place
     */
    private bool $require = false;

    /**
     * vite path to the file to be included
     */
    private ?string $file = null;

    /**
     * Options for tag, saved in an array with the key as the option name
     *
     * - 'preload' : Boolean. Preload the resource (defaults to true) (js & css)
     * - 'async' : Boolean value to set async attribute to script tag or async with js and media for css (js & css)
     * - 'defer' : Boolean value to set defer attribute to script tag (js)
     * - 'type' : Override script type= value. (js)
     * - 'integrity' : SubResource Integrity hash (js & css)
     * - 'crossorigin' : Cross-origin policy for the resource, defaults to true (js & css)
     */
    private array $options = [
        'preload' => true,
    ];

    public function Preload(bool $preload = true): self
    {
        $this->options['preload'] = $preload;
        return $this;
    }

    public function Async(bool $async = true): self
    {
        $this->options['async'] = $async;
        return $this;
    }

    public function Defer(bool $defer = true): self
    {
        $this->options['defer'] = $defer;
        return $this;
    }
    public function Inline(bool $inline = true): self
    {
        $this->inline = $inline;
        return $this;
    }
    public function Require(bool $require = true): self
    {
        $this->require = $require;
        return $this;
    }
    public function Type(string $type = ''): self
    {
        $this->options['type'] = $type;
        return $this;
    }
    public function Integrity(string $integrity = ''): self
    {
        $this->options['integrity'] = $integrity;
        return $this;
    }
    public function Crossorigin(string $crossorigin = ''): self
    {
        $this->options['crossorigin'] = $crossorigin;
        return $this;
    }
    public function Media(?string $media = null): self
    {
        $this->media = $media;
        return $this;
    }

    public function CSS($file, ?string $media = null)
    {
        $this->file = $file;
        $this->filetype = 'css';
        $this->media = $media;
        return $this;
    }
    public function JS($file)
    {
        $this->file = $file;
        $this->filetype = 'js';
        return $this;
    }
    public function Javascript($file)
    {
        return $this->JS($file);
    }

    public function forTemplate()
    {
        switch ($this->filetype) {
            case 'css':
                return $this->processCSS();
            case 'js':
                return $this->processJS();
            default:
                # code...
                return null;
        }
    }

    private function processJS(): mixed
    {
        // are we outputing this as an inline <style>?
        if ($this->inline) {
            $content = $this->getInlineTagContent();
            $html = HTML::createTag('script', [], $content);
            return $html;
        }

        // are we using the Requirements to inject this?
        if ($this->require) {
            Vite::javascript($this->file, $this->options);
            return null;
        }

        $src = self::viteTransformFilepath($this->file);
        $options = [
            'src' => $src,
            'type' => $this->type ?? 'module',
        ];
        $options = array_merge($options, $this->options);
        unset($options['preload']);
        $html = HTML::createTag('script', $options);
        return $html;
    }

    private function getInlineTagContent(): ?string
    {
        $vite = Vite::singleton();
        $root = Director::baseFolder();
        $file = $vite->resourcePath($this->file);
        $filepath = Controller::join_links($root, $file);
        if (file_exists($filepath)) {
            $html = file_get_contents($filepath);
            return $html;
        }
        return null;
    }

    private function processCSS(): mixed
    {
        // are we outputing this as an inline <style>?
        if ($this->inline) {
            $content = $this->getInlineTagContent();
            $html = HTML::createTag('style', [], $content);
            return $html;
        }

        // are we using the Requirements to inject this?
        if ($this->require) {
            Vite::css($this->file, $this->media, $this->options);
            return null;
        }

        // if not, then we're writing out tags inplace

        // are we using async media js method?
        $async = isset($this->options['async']) && $this->options['async'];

        // prepare to write out tags
        $href = self::viteTransformFilepath($this->file);
        $media = $this->media ?? 'all';
        $onload = $async ? "this.media='{$media}'" : null;
        $preloadFile = [
            'path' => $this->file,
            'as'   => 'style',
            'type' => 'text/css',
        ];
        $preload = $this->preload || $async;
        $preloadTag = $preload ? Vite::singleton()->createPreloadTag($preloadFile) : null;
        $noscriptTag = $async ? HTML::createTag('noscipt', [], HTML::createTag('link', [
            'rel'  => 'stylesheet',
            'type' => 'text/css',
            'href' => $href,
        ])) : null;
        $linkTag = HTML::createTag('link', [
            'rel'         => 'stylesheet',
            'type'        => 'text/css',
            'href'        => $href,
            'media'       => $async ? 'print' : $media,
            'onload'      => $onload,
            'crossorigin' => $this->crossorigin,
        ]);
        $html = join('', [$linkTag, $preloadTag, $noscriptTag]);
        return $html;
    }

    public static function get_template_global_variables()
    {
        return [
            'Vite' => [
                'method'  => 'Vite',
                'casting' => 'HTMLText',
            ]

        ];
    }

    public static function Vite(): self
    {
        return new TemplateVar();
    }

    public static function ViteResourceURL($path): string
    {
        return self::viteTransformFilepath($path);
    }

    // create deferred css includes that will work with caching (not in head using Requirements)
    public static function ViteDeferCSS($path): string
    {
        $path = self::viteTransformFilepath($path);
        $html = <<<EOT
        <link rel="stylesheet" type="text/css" media="print" href="{$path}" onload="this.media='all'">
        <link rel="preload" href="{$path}" as="style">
        <noscript><link rel="stylesheet" type="text/css" href="{$path}"></noscript>
        EOT;
        return $html;
    }

    public static function ViteInlineCSS($path): string
    {
        $vite = Vite::singleton();
        $root = Director::baseFolder();
        $file = $vite->resourcePath($path);
        $filepath = Controller::join_links($root, $file);
        $css = file_get_contents($filepath);
        $html = HTML::createTag('style', [], $css);
        return $html;
    }

    private static function viteTransformFilepath(string $path): string
    {
        $vite = Vite::singleton();
        if ($vite->isDevServerRunning()) {
            return $vite->devServerResourceUrl($path);
        }
        return $vite->manifestProvider->resolveURL($path);
    }

}
