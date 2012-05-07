=== Zemanta ===
Contributors: zemanta
Tags: images, links, tags, keywords, articles, seo, embed, enrich, media, semantic web, multimedia, video, youtube, maps, wikipedia, google maps, related, related content, books, zemanta, widget
Requires at least: 2.8
Tested up to: 3.3.1
Stable Tag: 1.1.2

Zemanta finds related content while you write your post, so you can add pictures, links and widgets with a single click.

== Description ==

Zemanta **recommends content** while you write your post. It helps you enhance it with images, videos, links, and SEO optimized tags. It's blogging on steroids!

Zemanta brings you:

* **Links**: Wikipedia, Amazon, IMDB, YouTube, Google Maps, CrunchBase, RottenTomatoes, MusicBrainz, MySpace, Last.fm, Snooth, Hulu, Wikinvest, Uptake, Muzu.tv,
* **Images**: Getty, Flickr, Wikipedia
* **Articles**: Major news sources like BBC and CNN and over 30,000 blogs

A simple point and click interface allows you to add only recommendations that you like. Recommended content is blogger friendly with clearly marked licenses (Creative Commons or similar).

Not having to search for related content saves you time and increases the efficiency of your editorial process. You can specify your own blog(s) and Zemanta will recommend links to your related posts!

Amazon affiliate support allows you to quickly link to books, music CD's and DVD's and earn money in the process.

You can try it out without installing the plug-in through [Firefox or Internet Explorer extension](http://www.zemanta.com/download "Zemanta download").
Get to know [more about Zemanta](http://www.zemanta.com).

== Installation ==

1. There's a download button on the right side of this text, download the file.
2. Unzip the file, copy `zemanta` directory to `wp-content/plugins` on your server.
3. Inside WordPress dasboard there's the `Plugins` menu, click on it. Then click on `Activate` link under Zemanta plug-in.
4. Start writing a new post. See the suggested content after you write a few sentences!

== Frequently Asked Questions ==

= What languages do you support? =

Right now we support only English, but if you blog in other languages about trademarks and buzzwords Zemanta might still be useful.

= Can Zemanta recommend me my own photos? =

Yes. You can setup Flickr account in Preferences and we'll recommend pictures from your account.

= Can Zemanta recommend me my own blog posts? =

Yes, see tutorial on [How to: Use Zemanta to recommend articles from your own blog(s)](http://www.zemanta.com/blog/how-to-use-zemanta-to-recommend-articles-from-your-own-blogs/)

= Do you care about copyright at all? =

Yes, very much. Content that we are recommending is copyright cleared - either licenced as Creative Commons and similar or approved by stock photo providers. However we can only inform you about the license and it is your decision wheter it is acceptable to you.

= I have another question =

Please take a look at [Zemanta Official FAQ](http://www.zemanta.com/faq/ "Zemanta FAQ").

== Screenshots ==

1. Zemanta sidebar
2. Post write page with Zemanta, some sample content and suggestions present.

== Changelog ==

= 1.1.2 =
* Fixed duplicating images bug in uploader
* Minor changes

= 1.1.0 =

* WordPress Featured Images support (WordPress 3.1+)
* All network interaction using WP_Http

= 1.0.8 =

* Minor changes and optimizations
* Change message should be visible now after plugin update

= 1.0.7 =

* Image uploader path change message

= 1.0.6 =

* Missing views fix

= 1.0.5 =

* Image uploader fixes

= 1.0.4 =

* Settings Updates
* Legacy Settings Support

= 1.0.0 =

* Added Settings API and fix for WP < 2.9
* Re-factor Settings Page
* Media Updates

= 0.8.2 =

* Report a missing API key and retry fetch until we can obtain one
* Update oldest supported version to 2.7

= 0.8.1 =

* Added missing views

= 0.8 =

* Complete code refactoring
* Image uploading using WP filesystem

= 0.7.3 =

* Supress errors on chmod calls to prevent cryptic "unexpected output" warnings
* Reworked hooks changed in 0.7.2

= 0.7.2 =

* Changed hooks to prevent javascript trying to load twice

= 0.7.1 =

* Fixed bug with WP running on php4

= 0.7.0 =

* Fixed bug with publishing scheduled posts
* Fixed image downloader
* Fixed sidebar positioning

= 0.6.6 =

* Compatibility check with WP 3.0
* Loader location changed

= 0.6.5 =

* Fixed short form php open tags

= 0.6.4 =

* Fixed sidebar positioning problems for the new Zemanta widget

= 0.6.3 =

* Fixed bug with image downloader - problems with downloading images with whitespace in image name

= 0.6.2 =

* Speed up of widget loading (using Amazon CloudFront instead of Amazon S3)
* readme.txt and INSTALL text review
