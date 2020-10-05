# WHMCS "ISPAPI" Dashboard Widget "Monitoring" #

[![semantic-release](https://img.shields.io/badge/%20%20%F0%9F%93%A6%F0%9F%9A%80-semantic--release-e10079.svg)](https://github.com/semantic-release/semantic-release)
[![Build Status](https://travis-ci.com/hexonet/whmcs-ispapi-widget-monitoring.svg?branch=master)](https://travis-ci.com/hexonet/whmcs-ispapi-widget-monitoring)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![PRs welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/blob/master/CONTRIBUTING.md)

This Repository covers the WHMCS "ISPAPI" Dashboard Widget "Monitoring". It provides the following features to WHMCS:

## Supported Features ##

Monitoring of Domain Data Issues like

* WHOIS Privacy Service
* Transfer Lock

... and providing buttons to get the cases fixed.

If there's something missing, let us know and we will extend.

## Why this Widget ##

This widget was planned as nice to have for different purposes to show data differences between the HEXONET API and WHMCS. Don't worry, we do not have such issues in general. Whenever changes happen on domain level outside of WHMCS you might run into trouble as booked additional domain services might not correctly get invoiced to your customers (worst case!). This might happen when using another Frontend to activate or deactivate Domain Add-Ons / Services. Some Registries (e.g. DK Hostmaster) even allow Registrants to maintain domains over a registry frontend and to perform different actions directly there. This is where WHMCS modules in general might run into corner cases - Imagine that 3 systems are there then involved: Registry, HEXONET, WHMCS. Whenever underlying processes are quite special, that's where it is getting hard with WHMCS at the end and this is where we are thinking that this Widget helps!

## Resources ##

* [Release Notes](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/releases)

**If you have any issue related to this module, feel free to open an github issue or to contact our support team.**

## Usage Guide ##

### Installation / Upgrade ###

Download the ZIP archive including the latest release version [here](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/raw/master/whmcs-ispapi-widget-monitoring-latest.zip).

Extract the `ispapi_monitoring.php` into folder `/modules/widgets` of your WHMCS instance.

### Using this Widget ###

This is how it looks like in case everything is fine:
![allfine](https://user-images.githubusercontent.com/229425/94283804-c45bb600-ff51-11ea-9097-89e2067cd147.png)

Otherwise cases are listed accordingly and providing a wizard to get them fixed:
![inactivetransferlock](https://user-images.githubusercontent.com/229425/94922353-5a479180-04ba-11eb-9813-434374318552.png)
![wpissues](https://user-images.githubusercontent.com/229425/94419760-7d550700-0183-11eb-88d6-a8eab5e38f94.png)

Click on `Details!` to get more information:
![wizard1](https://user-images.githubusercontent.com/229425/94922460-8c58f380-04ba-11eb-9cf8-399d401bb971.png)

By clicking on `Fix this!` the widget takes care of processing the items as described:
![wizard2](https://user-images.githubusercontent.com/229425/94922508-a72b6800-04ba-11eb-9e5a-08ca63a29988.png)
![wizard3](https://user-images.githubusercontent.com/229425/94922549-b7434780-04ba-11eb-9d31-3f36f50ac167.png)

Furthermore you can download the affected items as CSV list for your reference before processing (without results) and after processing (including results).
NOTE: columns are separated by tab delimiter.

**Note:** This can of course take a while in case a lot of domains have to be processed on API side.

## Minimum Requirements ##

Having Javascript activated in Browser.
For the latest WHMCS minimum system requirements, please refer to
[https://docs.whmcs.com/System_Requirements](https://docs.whmcs.com/System_Requirements)

This Dashboard Widget is only compatible with the [ISPAPI registrar module](https://github.com/hexonet/whmcs-ispapi-registrar). Please install that provider module in version >= v4.4.5 first.

## Contributing ##

Please read [our contributing guide](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/blob/master/CONTRIBUTING.md) for details on our code of conduct, and the process for submitting pull requests to us.

## Authors ##

* **Kai Schwarz** - *development* - [PapaKai](https://github.com/papakai)

See also the list of [contributors](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/graphs/contributors) who participated in this project.

## License ##

This project is licensed under the MIT License - see the [LICENSE](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/blob/master/LICENSE) file for details.

[HEXONET GmbH](https://hexonet.net)
