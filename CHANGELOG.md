# Release Notes

## [Unreleased](https://github.com/benargo/saloon-graphql/compare/v0.1.0...1.x)

### Added

- `GraphQLRequest`: an abstract Saloon request that sends `{query, variables}` as JSON.
- `GraphQLException`: a Saloon `RequestException` with `getErrors()`, `getFirstError()` and `hasErrorMatching()`.
- `HandlesGraphQLErrors`: a connector or request trait that throws `GraphQLException` for GraphQL `errors` responses.


## [v0.1.0](https://github.com/benargo/saloon-graphql/compare/...v0.1.0) - 202x-xx-xx

Initial pre-release.
