# TODO

## Automatic testing plan:

 * launch simulator
 * get access to the app by using inspector protocol for ios/android
 * set page via prefs
 * run script
 * get test result via ajax

http://stackoverflow.com/questions/18739352/automatic-testing-for-cordova-phonegap-webview-on-android
https://github.com/google/ios-webkit-debug-proxy/blob/master/examples/wdp_client.js

Even more — add android

 * https://docs.travis-ci.com/user/languages/android
 * https://github.com/journeyapps/android-sdk-installer

## Change plugin add test from travis cli

…to something like this: https://github.com/apache/cordova-android/blob/master/spec/e2e/plugin.spec.js

## Ask cordova team to expose config.xml contents



## Ask cordova team to update xcode package, for now only sync version works (can cause additional issues)

 * https://github.com/alunny/node-xcode/pull/66
 * https://issues.apache.org/jira/browse/CB-9297
 * https://github.com/apache/cordova-lib/pull/305
