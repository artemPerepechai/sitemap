Sitemap
=======

Sitemap and sitemap index builder.

<img src="https://travis-ci.org/samdark/sitemap.svg" />

Features
--------

- Create sitemap files.
- Create sitemap index files.
- Automatically creates new file if 50000 URLs limit is reached.
- Memory efficient buffer of configurable size.

Installation
------------
in composer.json

"require": {
	 "artemPerepechai/sitemap" : "dev-master"
	},
	"repositories": [
		{
			"url": "https://github.com/artemPerepechai/sitemap",
			"type": "vcs"
		}
	],

After that, make sure your application autoloads Composer classes by including
`vendor/autoload.php`.

How to use it
-------------

```php
use samdark\sitemap\Sitemap;
use samdark\sitemap\Index;

// create sitemap
$sitemap = new Sitemap(__DIR__ . '/sitemap.xml');

// add some URLs
$sitemap->setLocation('http://example.com/mylink4')
        ->setLastModified(time())
        ->setFrequency(Sitemap::DAILY)
        ->setAlternateLanguage('en', 'http://example.com/en')
        ->setAlternateLanguage('de', 'http://example.com/de')
        ->setAlternateLanguage('fr', 'http://example.com/fr')
        ->setPriority(0.1)
        ->addItem();

// write it
$sitemap->write();

// get URLs of sitemaps written
$sitemapFileUrls = $sitemap->getSitemapUrls('http://example.com/');

// create sitemap for static files
$staticSitemap = new Sitemap(__DIR__ . '/sitemap_static.xml');

// add some URLs
$staticSitemap->setLocation('http://example.com/about')
              ->setLastModified(time())
              ->addItem();
              
$staticSitemap->setLocation('http://example.com/tos')
              ->setLastModified(time())
              ->addItem();
              
$staticSitemap->setLocation('http://example.com/jobs')
              ->setLastModified(time())
              ->addItem();

// write it
$staticSitemap->write();

// get URLs of sitemaps written
$staticSitemapUrls = $staticSitemap->getSitemapUrls('http://example.com/');

// create sitemap index file
$index = new Index(__DIR__ . '/sitemap_index.xml');

// add URLs
foreach ($sitemapFileUrls as $sitemapUrl) {
    $index->addSitemap($sitemapUrl);
}

// add more URLs
foreach ($staticSitemapUrls as $sitemapUrl) {
    $index->addSitemap($sitemapUrl);
}

// write it
$index->write();
```

Options
-------

There are two methods to configre `Sitemap` instance:
 
- `setMaxUrls($number)`. Sets maximum number of URLs to write in a single file.
  Default is 50000 which is the limit according to specification and most of
  existing implementations.
- `setBufferSize($number)`. Sets number of URLs to be kept in memory before writing it to file.
  Default is 1000. If you have more memory consider increasing it. If 1000 URLs doesn't fit,
  decrease it.
- `setUseIndent($bool)`. Sets if XML should be indented. Default is true.

Running tests
-------------

In order to run tests perform the following commands:

```
composer install
phpunit
```
