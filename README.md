# wp-xili-lang-to-polylang

WordPress plugin which helps to migrate your site from xili-language to Polylang. It migrates post and page language codes and their references.

The plugin was written to migrate one particular site. I'm no WordPress, xili-language or Polylang expert so please do not contact me for further assistance.

## How this is supposed to work

Before switching the plugins:

1. Install and enable the plugin to the WP site with `xili-language` plugin enabled.
1. Go to site admin and select XLTP from the left navigation menu.
1. The plugin detects the `xili-language` (function `get_post_language()` is defined) and generates an JSON array of posts, pages and their language codes.
1. Copy this to you favourite text editor.

Switch the plugins:

1. Deactivate `xili-language` plugin.
1. Install and activate `polylang` plugin.
1. Make initial settings. In my case there was two languages:
    * Name: `suomi`, locale: `fi_FI`, slug: `fi`, flag: none (languages do not have flags)
    * Name: `svenska`, locale: `sv_SE`, slug: `sv`, flag: none
1. Go to Languages > Settings > URL modifications
    * Choose: `The language is set from content`
    * Choose: `Keep /language/ in pretty parmalinks

Make the migration:

1. Make sure the language codes in the JSON (sitting in your favourite text editor) equals to your Polylang slugs (in my case `fi` and `sv`). In my sample case the `xili-language` had used `sv_se` for Swedish so I had to search and replace `"sv_se"` with `"sv"`.
1. Go to XLTP > paste the JSON to the textarea and click the Profit! button.

After this process you're done.

## References:

 * [xili-language on GitHub](https://github.com/dev-xiligroup/xili-language-plugin)
 * [Polylang function reference](https://polylang.pro/doc/function-reference/)
 