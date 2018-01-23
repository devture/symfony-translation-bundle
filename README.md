# Description

Web UI for managing Symfony bundles' translation files inside the actual web application.

This is a port of the similarly named [Silex](https://silex.symfony.com/) bundle: [devture/silex-translation-bundle](https://github.com/devture/silex-translation-bundle)

Finds all source language files (example: `messages.en.json`, `another-domain.en.json`) in a given list of base directories and allows these files to be translated to all given languages.

A `{translationDomain}.{targetLanguage}.json` file is generated and saved next to `{translationDomain}{sourceLanguage}.json` for each locale, whenever the translations for it are saved.

A `{translationDomain}{targetLanguage}.hash.json` file is also saved in the same directory.
It contains "hints" telling the translation system which source translation string a given translation is derived from. This is so that a translation can be considered outdated if the source translation string changes.
At this moment, outdated translations are automatically marked as untranslated in the web UI (that is to say, they are not marked as "already translated, but outdated", but simply as "not translated").


# Installation

Install through composer (`composer require --dev devture/translation-bundle`).

Add to `config/bundles.php`:

```php
Devture\Bundle\TranslationBundle\DevtureTranslationBundle::class => ['dev' => true],
```

## Permissions

Since the translation system needs to save translation files in the project, we need to grant file-writing privileges
to the web server user.

Example:

```bash
$ find /srv/http/my-project/src -type d -name translations | xargs chown :http
$ find /srv/http/my-project/src -type d -name translations | xargs chmod g+w
```


## Configuration

You most likely want this bundle active only for your development (`dev`) environment.
Thus, you can drop the following routing config in `config/packages/dev/devture_translation.yaml`

```yaml
devture_translation:
    source_language_locale_key: en
    paths_to_translate:
        - "%kernel.project_dir%/src"
        - "%kernel.project_dir%/translations"
    locales:
        - {"key": "en", "name": "English"}
        - {"key": "ja", "name": "Japanese"}
    twig_layout_path: "base.html.twig"
```

`locales` needs to contain all languages that the translation system should be active for (**including** the source language).

Multiple paths can be specified in `paths_to_translate`.
Each is scanned for files matching the following pattern: `*/translations/<some translation domain>.<source_language_locale_key>.json`.

`twig_layout_path` is the path to your layout file, which would contain the translation system.
The only requirement is that it defines a `content` block. The translation system would render within it.

Example layout file:

```twig
<!doctype html>
<html>
	<body>
		<h1>Website</h1>
		{% block content %}{% endblock %}
	</body>
</html>
```


## Routing example

You most likely want this bundle active only for your development (`dev`) environment.
Thus, you can drop the following routing config in `config/routes/dev/DevtureTranslationBundle.yaml`:

```yaml
DevtureTranslationBundleWebsite:
    prefix: /{_locale}/translation
    resource: "@DevtureTranslationBundle/Resources/config/routes/website.yaml"
    requirements:
        _locale: "en|ja"
```

The Web UI is available at the `devture_translation.manage` route.


## Styling

This bundle relies on [Bootstrap](http://getbootstrap.com/) v4 for styling.
Unless you install and include it (somewhere in your `twig_layout_path` template), things would look ugly.

Additionally, you can make the pages look prettier by including a flag icon for each language somewhere in your layout or CSS file.

```html
<style>
	.devture-translation-flag {
		border: 1px solid #dbdbdb;
		width: 20px;
		height: 13px;
		display: inline-block;
		vertical-align: text-top;
	}
	.devture-translation-flag.en {
		background: url('/images/flag/en_US.png') no-repeat;
	}
	.devture-translation-flag..ja {
		background: url('/images/flag/ja_JP.png') no-repeat;
	}
</style>
```
