# Introduction
This document describes the design of using RelMeAuth/IndieAuth together with
WebID+TLS.

The idea is generate a self signed client certificate, i.e. not issued by a CA
and store its fingerprint on your personal website together with your other
`rel="me"` links.

# Setup
The user would just add an additional entry to the list of authentication 
sources, e.g.:

    <link rel="me" href="di:sha-256;2gANyGXtjBIV4LGfUEk_gEDGkUvlgjxfaWayIhyEShc?ct=application/x-x509-user-cert">
    <link rel="me" href="https://twitter.com/fkooman">

# Server
The RelMeAuth/IndieAuth service accepts (optional) client certificate 
authentication without validating the CA and calculates the fingerprint of the
certificate provided to the web server. This fingerprint is then matched with
the fingerprint on the user's profile.

# Provisioning
To make it easy for users to generate their own client certificate it makes 
sense the RelMeAuth/IndieAuth service provides the means to create and 
install such a certificate using for instance the HTML5 `<keygen>` tag.

# Server Configuration
For Apache (2.4) the following configuration directives are relevant:

	SSLVerifyClient optional_no_ca
	SSLVerifyDepth 0 
	SSLOptions +ExportCertData

# References
* [The di (DIGEST) URI Scheme](https://tools.ietf.org/html/draft-hallambaker-digesturi-02)
