# Flowpack.SeoRouting

<!-- TOC -->

* [Flowpack.SeoRouting](#flowpackseorouting)
    * [Sponsoring](#sponsoring)
    * [Introduction](#introduction)
    * [Features](#features)
    * [Installation](#installation)
    * [Configuration](#configuration)
        * [Standard Configuration](#standard-configuration)
        * [Blocklist for redirects](#blocklist-for-redirects)
    * [Thank you](#thank-you)

<!-- TOC -->

## Sponsoring

Thank you [Biallo & Team GmbH](https://www.biallo.de/) for sponsoring the work from Sandstorm on this package.

## Introduction

This package allows you to enforce a trailing slash and/or lower case urls in Flow/Neos.

## Features

This package has 2 main features:

- **trailingSlash**: ensure that all rendered internal links in the frontend end with a trailing slash (e.g. `example.
com/test/` instead of `example.com/test`) and all called URLs without trailing slash will be redirected to the same
  page with a trailing slash
- **toLowerCase**: ensure that camelCase links gets redirected to lowercase (e.g. `example.com/lowercase` instead of
  `example.com/lowerCase`)

You can de- and activate both of them.

Another small feature is to restrict all _new_ Neos pages to have a lowercased `uriPathSegment`. This is done by
extending the `NodeTypes.Document.yaml`.

## Installation

Just require it via composer:

`composer require flowpack/seo-routing`

## Configuration

### Standard Configuration

In the standard configuration we have activated the trailingSlash (to redirect all uris without a / at the end to an uri
with / at the end) and do all redirects with a 301 http status.

*Note: The lowercase redirect is deactivated by default, because you have to make sure, that there is
no Neos page with an `uriPathSegment` with camelCase or upperspace letters - this would lead to redirects in the
neverland.*

```
Flowpack:
  SeoRouting:
    redirect:
      enable:
        trailingSlash: true
        toLowerCase: false
      statusCode: 301
    blocklist:
      '/neos.*': true
```

### Blocklist for redirects

By default, all `/neos` URLs are ignored for redirects. You can extend the blocklist array with regex as you like:

```yaml
Flowpack:
  SeoRouting:
    blocklist:
      '/neos.*': true
```

## Thank you

This package originates from https://github.com/t3n/seo-routing.

Thank you, T3N and associates for your work.
