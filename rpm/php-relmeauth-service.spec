%global github_owner     fkooman
%global github_name      php-relmeauth-service

Name:       php-relmeauth-service
Version:    0.4.0
Release:    1%{?dist}
Summary:    RelMeAuth service written in PHP

Group:      Applications/Internet
License:    AGPLv3+
URL:        https://github.com/%{github_owner}/%{github_name}
Source0:    https://github.com/%{github_owner}/%{github_name}/archive/%{version}.tar.gz
Source1:    php-relmeauth-service-httpd-conf
Source2:    php-relmeauth-service-autoload.php

BuildArch:  noarch

Requires:   php >= 5.3.3
Requires:   php-openssl
Requires:   php-pdo
Requires:   httpd
Requires:   php-pecl-oauth >= 1.2.3

Requires:   php-composer(guzzle/guzzle) >= 3.9
Requires:   php-composer(guzzle/guzzle) < 4.0
Requires:   php-composer(fkooman/json) >= 0.6.0
Requires:   php-composer(fkooman/json) < 0.7.0
Requires:   php-composer(fkooman/ini) >= 0.2.0
Requires:   php-composer(fkooman/ini) < 0.3.0
Requires:   php-composer(fkooman/rest) >= 0.6.4
Requires:   php-composer(fkooman/rest) < 0.7.0
Requires:   php-composer(fkooman/cert-parser) >= 0.1.8
Requires:   php-composer(fkooman/cert-parser) < 0.2.0

Requires:   php-pear(pear.twig-project.org/Twig) >= 1.15
Requires:   php-pear(pear.twig-project.org/Twig) < 2.0

#Starting F21 we can use the composer dependency for Symfony
#Requires:   php-composer(symfony/classloader) >= 2.3.9
#Requires:   php-composer(symfony/classloader) < 3.0
Requires:   php-pear(pear.symfony.com/ClassLoader) >= 2.3.9
Requires:   php-pear(pear.symfony.com/ClassLoader) < 3.0

Requires(post): policycoreutils-python
Requires(postun): policycoreutils-python

%description
RelMeAuth service to authenticate users using their profile URL and existing 
(social) network logins.

%prep
%setup -qn %{github_name}-%{version}

sed -i "s|dirname(__DIR__)|'%{_datadir}/php-relmeauth-service'|" bin/php-relmeauth-service-init

%build

%install
# Apache configuration
install -m 0644 -D -p %{SOURCE1} ${RPM_BUILD_ROOT}%{_sysconfdir}/httpd/conf.d/php-relmeauth-service.conf

# Application
mkdir -p ${RPM_BUILD_ROOT}%{_datadir}/php-relmeauth-service
cp -pr web views src ${RPM_BUILD_ROOT}%{_datadir}/php-relmeauth-service

# use our own class loader
mkdir -p ${RPM_BUILD_ROOT}%{_datadir}/php-relmeauth-service/vendor
cp -pr %{SOURCE2} ${RPM_BUILD_ROOT}%{_datadir}/php-relmeauth-service/vendor/autoload.php

mkdir -p ${RPM_BUILD_ROOT}%{_bindir}
cp -pr bin/* ${RPM_BUILD_ROOT}%{_bindir}

# Config
mkdir -p ${RPM_BUILD_ROOT}%{_sysconfdir}/php-relmeauth-service
cp -p config/config.ini.defaults ${RPM_BUILD_ROOT}%{_sysconfdir}/php-relmeauth-service/config.ini
ln -s ../../../etc/php-relmeauth-service ${RPM_BUILD_ROOT}%{_datadir}/php-relmeauth-service/config

# Data
mkdir -p ${RPM_BUILD_ROOT}%{_localstatedir}/lib/php-relmeauth-service

%post
semanage fcontext -a -t httpd_sys_rw_content_t '%{_localstatedir}/lib/php-relmeauth-service(/.*)?' 2>/dev/null || :
restorecon -R %{_localstatedir}/lib/php-relmeauth-service || :

%postun
if [ $1 -eq 0 ] ; then  # final removal
semanage fcontext -d -t httpd_sys_rw_content_t '%{_localstatedir}/lib/php-relmeauth-service(/.*)?' 2>/dev/null || :
fi

%files
%defattr(-,root,root,-)
%config(noreplace) %{_sysconfdir}/httpd/conf.d/php-relmeauth-service.conf
%config(noreplace) %{_sysconfdir}/php-relmeauth-service
%{_bindir}/php-relmeauth-service-init
%dir %{_datadir}/php-relmeauth-service
%{_datadir}/php-relmeauth-service/src
%{_datadir}/php-relmeauth-service/vendor
%{_datadir}/php-relmeauth-service/web
%{_datadir}/php-relmeauth-service/views
%{_datadir}/php-relmeauth-service/config
%dir %attr(0700,apache,apache) %{_localstatedir}/lib/php-relmeauth-service
%doc README.md agpl-3.0.txt composer.json config/

%changelog
* Mon Jan 26 2015 François Kooman <fkooman@tuxed.net> - 0.4.0-1
- update to 0.4.0

* Mon Jan 26 2015 François Kooman <fkooman@tuxed.net> - 0.3.0-1
- update to 0.3.0

* Sun Jan 25 2015 François Kooman <fkooman@tuxed.net> - 0.2.0-2
- require Guzzle

* Sun Jan 25 2015 François Kooman <fkooman@tuxed.net> - 0.2.0-1
- update to 0.2.0

* Sat Jan 24 2015 François Kooman <fkooman@tuxed.net> - 0.1.0-1
- initial package
