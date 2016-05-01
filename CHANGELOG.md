# Changelog

## Version 2.0 - _under development_ :construction: :beetle:
- New seeding command `php artisan es:seed` added to seed models to the index.
- Index deletion command now only prompts for confirmation in production environments.
- Index creation command now prompts for confirmation if the `--reset` option is specified in a production environment.
- Index creation command now accepts a comma separated `--models` parameter to automatically add property mappings. Note that the signature of the `createIndex` changed, any child class needs to adapt to the new method and the new optional parameter.
- Short parameter version of `--reset` for the index creation command removed.
- Global analyzers can now be set in the Elodex configuration and will be automatically added during index creation.
- New Command `es:get-settings` to print index settings.


## Version 1.0
- [Scrolling][Elodex Scrolling] added.
- [Highlighting][Elodex Highlighting] support.
- [Suggestions][Elodex Suggestions] support.
- `limit`, `take` and `offset` added to the [search query class][Elodex Search].
- Metadata of search results is now returned as a collection instead of an array.
- The `getItems` method on the `SearchResult` class was renamed to `getModels`.
- The `all` method on the index repository no longer returns models but a search result instance.
- New command `php artisan make:es:sync-handler` added to generate a default implementation for index synchronizations of a model class.


## Version 0.9
- First pre-release.


[Elodex Scrolling]: https://github.com/Elodex/Documentation/blob/develop/08_Scrolling.md "Elodex Scrolling"
[Elodex Highlighting]: https://github.com/Elodex/Documentation/blob/develop/07_Highlighting.md "Elodex Highlighting"
[Elodex Suggestions]: https://github.com/Elodex/Documentation/blob/develop/09_Suggestions.md "Elodex Suggestions"
[Elodex Search]: https://github.com/Elodex/Documentation/blob/develop/06_Search.md "Elodex Search"
