# [1.8.0](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/compare/v1.7.1...v1.8.0) (2021-09-22)


### Features

* **toggle on/off:** reviewed from scratch, follows our account widget ([1fe39e0](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/commit/1fe39e0653bf498bb0cd11a78c145f62db339106))

## [1.7.1](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/compare/v1.7.0...v1.7.1) (2021-07-08)


### Bug Fixes

* **semantic-release:** version update mechanism ([633f561](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/commit/633f56153ee8680bcec6d313ff5814473faf47e0))

# [1.7.0](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/compare/v1.6.7...v1.7.0) (2021-07-01)


### Features

* **zipfile:** added subdirs to the zipfile ([47e9a35](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/commit/47e9a3544f6c2100aeb77f6163e96f1812c83b94))

## [1.6.7](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/compare/v1.6.6...v1.6.7) (2021-03-30)


### Bug Fixes

* **getactivedomainswhmcs:** fixed issue with foreach args ([ccc2031](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/commit/ccc2031f6ec3bea4589a72bb7e5e34927c9a9635))

## [1.6.6](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/compare/v1.6.5...v1.6.6) (2021-03-09)


### Performance Improvements

* **getactivedomainswhmcs:** to use toJson approach for array vs. object problem ([00c032d](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/commit/00c032d31d393c918e69bb4104ad115cd5ec1b69))

## [1.6.5](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/compare/v1.6.4...v1.6.5) (2021-01-20)


### Bug Fixes

* **ci:** migration from Travis CI to github actions ([c131dc7](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/commit/c131dc7a85a97b8c24723907a0bc37d94cfe603c))

## [1.6.4](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/compare/v1.6.3...v1.6.4) (2021-01-07)


### Bug Fixes

* **premium domains:** fixing process has to consider NULL value for is_premium ([658588d](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/commit/658588d28a15664bc8e410cfbb52dfc442c5ea72))

## [1.6.3](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/compare/v1.6.2...v1.6.3) (2021-01-07)


### Bug Fixes

* **premium domains:** is_premium is by default null, prev update missing ([fec5c9d](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/commit/fec5c9d640a7792b4fa4d9a96c9f611eb7c33191))

## [1.6.2](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/compare/v1.6.1...v1.6.2) (2021-01-07)


### Bug Fixes

* **texts:** fixes text output related to singular/plural correctness ([1ca9778](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/commit/1ca9778f955d29d086ce0476c6a99824063f9f33))

## [1.6.1](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/compare/v1.6.0...v1.6.1) (2021-01-07)


### Bug Fixes

* **premium domains:** fix case detection (is_premium is NULL by default) ([f78c9fc](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/commit/f78c9fcd9dd7ee636a4a445b5080c9363c76cfa2))

# [1.6.0](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/compare/v1.5.1...v1.6.0) (2021-01-06)


### Features

* **premium domains:** added possibility to fix standard domains in whmcs that are premium domains ([b758695](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/commit/b75869562849fad6cfdc899f0ac06d9e770dd445))

## [1.5.1](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/compare/v1.5.0...v1.5.1) (2020-12-02)


### Bug Fixes

* **getactivedomainswhmcs:** fix data usage in loop ([885f18e](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/commit/885f18e631d18271718b087653d5d9bc15f1c406))

# [1.5.0](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/compare/v1.4.1...v1.5.0) (2020-11-24)


### Features

* **premium domains:** fixable registrarRenewalCostPrice data in tbldomains_extra ([2bddbcf](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/commit/2bddbcff04f6f9e3dbf780f499ae18f4aec14058))

## [1.4.1](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/compare/v1.4.0...v1.4.1) (2020-10-07)


### Bug Fixes

* **logo:** use right one ([ecf8cbe](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/commit/ecf8cbef7bfccc6af0d74e9d6a9e8c552e05f1ea))

# [1.4.0](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/compare/v1.3.2...v1.4.0) (2020-10-07)


### Features

* **new case:** added to cleanup additional notes related to our migration tool ([9f41945](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/commit/9f41945a828cc8d61f7ddcb31fdfdf0c284de02f))

## [1.3.2](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/compare/v1.3.1...v1.3.2) (2020-10-07)


### Bug Fixes

* **getactivedomainswhmcs:** switch back to registrar ispapi ([ebadbab](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/commit/ebadbab93af213b3e1c9e10eb723a53909fd0382))

## [1.3.1](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/compare/v1.3.0...v1.3.1) (2020-10-06)


### Bug Fixes

* **getdata:** fixed corner cases around data lookup ([483489a](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/commit/483489a3d747cc2a35724600c07461c1bb23e75e))

# [1.3.0](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/compare/v1.2.0...v1.3.0) (2020-10-02)


### Bug Fixes

* **widget:** fixed plural output in modal description ([a78cb4b](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/commit/a78cb4bc7eac6ac9633882bbd82684b26f044c47))


### Features

* **transferlock:** added check for inactive transfer lock ([cd0f3b1](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/commit/cd0f3b16a65c6907466869d00c395f0f74e85700))

# [1.2.0](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/compare/v1.1.1...v1.2.0) (2020-10-02)


### Features

* **processing:** covered over javascript ([1eb34ca](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/commit/1eb34ca4832f53d0609be238990f855301b385b8))

## [1.1.1](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/compare/v1.1.0...v1.1.1) (2020-09-25)


### Bug Fixes

* **widget:** to consider ispapi registrar / removed test cases ([29c27ad](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/commit/29c27ad7d6f772b493fbea049829847458c5c155))

# [1.1.0](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/compare/v1.0.0...v1.1.0) (2020-09-25)


### Features

* **visual output:** reviewed for case "all fine" ([62e3660](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/commit/62e366085b0469e78f21c895c78f295a17f1dcf5))

# 1.0.0 (2020-09-25)


### Features

* **release:** initial release ([3bad096](https://github.com/hexonet/whmcs-ispapi-widget-monitoring/commit/3bad0967ee13a851d5c9ec533fb8adfb26e19419))
