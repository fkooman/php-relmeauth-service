# Introduction
These are all the files to get a Docker instance running with 
`php-relmeauth-service`.

To build the Docker image:

    docker build --rm -t fkooman/php-relmeauth-service .

To run the container:

    docker run -d -p 443:443 fkooman/php-relmeauth-service

That should be all. You can replace `fkooman` with your own name of course.

Do not forget to first go to [https://localhost](https://localhost) with your
browser to accept the self signed certificate.
