CHANGELOG for Bolt Common
=========================

1.1.0
-----

Released 2017-08-10 ([commits since v1.0.0](https://github.com/bolt/common/compare/v1.0.0...v1.1.0))

 - PR [#17](https://github.com/bolt/common/pull/17)
   - Change: Allow call stack index to be passed into `Deprecated::method` subject parameter
   - Change: Magic call methods now suggest corresponding method on suggested class
   - Change: Removed functionality for magic properties (i.e. __get)
   - Change: Refactored stacktrace logic into separate method
 - PR [#16](https://github.com/bolt/common/pull/16)
   - Added: `Json` constant shortcut flags
   - Change: `Json` to escape line terminators
   - Change: `Json` doesn't pretty print by default
   - Fixed: `Json` will throw an exception for empty strings in PHP 5
 - PR [#15](https://github.com/bolt/common/pull/15)
   - Fixed: `Deprecated::method` use in constructors
 - PR [#14](https://github.com/bolt/common/pull/14)
   - Change: `Deprecated::method` now suggests the method of suggested class

1.0.0
-----

Released 2017-08-01 ([commits](https://github.com/bolt/common/compare/253f473f479d8aa149574fd1ab237da0e9c995c0...v1.0.0))

Change summary:

 - Added: `Assert` class
 - Added: `Json` class
 - Added: `String` class
 - Added: `Deprecated` class
 - Added: `Thrower` class
