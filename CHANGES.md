# Changelog

## 0.5.0
- work with clean URLs (update `fkooman/rest`)
- support multiple entries of the same provider when parsing `rel="me"` links

## 0.4.0
- implement initial WebId support
- lots of cleanups all around

## 0.3.0
- **SECURITY**: really stupid mistake where storing the access token
  from the identity providers seemed like a good idea, so removed all
  token storage and simplified a lot of code
- move CSS to a separate file to also work with CSP

## 0.2.0
- **BREAKING**: new database schema, no migration script available
- implement Twitter backend support
- make multi authentication backend support work
- use `Session` instead of database to store temporary state and tokens for 
  interacting with the authentication backend

## 0.1.0
- initial release
