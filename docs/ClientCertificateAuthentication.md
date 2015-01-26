# Introduction
This document describes the design of using RelMeAuth together with TLS client 
certificates.

The goal is to further decentralize the social authentication mechanisms 
currently implemented in e.g. IndieAuth.

# Configuration

## User
For the end user who wishes to use a TLS client certificate to authenticate to
services that are currently using RelMeAuth need to generate a self signed
TLS client certificate in the browser and import that in the browser. There are
various ways of doing this, e.g. by using the `openssl` command line tool or
using the HTML5 `<keygen>` tag together with a backend service that signs the
SPKAC sent by the browser.

The user who wants to use their certificate to authenticate to a RelMeAuth 
service just needs to put the certificate fingerprint on their profile page 
using the `rel="me"` link type, similar to e.g. Twitter and GitHub` links. For 
the client certificate the link relation looks like this:

    <link href="di:sha-256;eFOWtZEA76fMLgdiM5aJIfFbAR_sn7CwBgaygxzJmDw?ct=application/x-x509-user-cert" rel="me">

There can be multiple such link relations to support multiple client 
certificates, for instance on the user's different devices. The client 
certificate itself does not need to contain any details as the normal RelMeAuth
flow is used to determine the user's profile page.

## Server
The RelMeAuth service will need to support TLS client certificate 
authentication. For instance the following configuration snippet is for Apache 
2.4:

	SSLVerifyClient optional_no_ca
	SSLVerifyDepth 0 
	SSLOptions +ExportCertData

This will allow the user to use a TLS client certificate, but if non is 
available the normal flow without certificates is used.

# References

* [The di (DIGEST) URI Scheme](https://tools.ietf.org/html/draft-hallambaker-digesturi-02)

