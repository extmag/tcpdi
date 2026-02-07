# Changelog

## 2.0.0

### Breaking Changes

- Minimum PHP version raised from 5.6 to 7.4
- `tcpdi_parser::Error()` now throws `\RuntimeException` instead of calling `die()` — callers relying on process termination must catch the exception

### Bug Fixes

- **[CRITICAL] Fix annotations never imported** (Bug 1): `count($annots[1][1] > 1)` evaluated operator precedence incorrectly — the comparison was inside `count()`, making the condition always false on PHP 7.2+. Fixed to `count($annots[1][1]) > 0`.
- **[CRITICAL] Fix dead code in `decodeStream()`** (Bug 2): `/Length` (PDF_TYPE_NUMERIC) and `/Filter` (PDF_TYPE_ARRAY) checks were nested inside `if ($v[0] == PDF_TYPE_TOKEN)`, making them unreachable. Stream length truncation and array filter extraction now work correctly.
- **[CRITICAL] Replace `die()` with exception** (Bug 3): `tcpdi_parser::Error()` called `die()` with HTML, killing the entire process. Now throws `\RuntimeException` with a plain-text message.
- **[HIGH] Fix `strpos ==` comparison** (Bug 4): `strpos() == $startxref` in `getXrefData()` could misidentify xref stream as xref table when `$startxref` was `0`. Fixed to `===`.
- **[HIGH] Fix `strpos !=` comparison** (Bug 5): `strpos() != $offset` in `getIndirectObject()` could fail when offset was `0` and object was missing. Fixed to `!==`.
- **[HIGH] Fix `Error()` receiving array** (Bug 6): `getIndirectObject()` passed `$obj` (array from `explode()`) instead of `$obj_ref` (string) to `Error()`, causing "Array to string conversion". Fixed to `$obj_ref`.
- **[HIGH] Fix out-of-bounds in `_unescape()`** (Bug 7): Octal escape sequences at end of string caused undefined offset access on `$s[$count+1]`. Added bounds checking.
- **[MEDIUM] Remove `@` error suppression** (Bug 8): `getRawObject()` used `@$data[...]` to suppress undefined offset warnings. Replaced with explicit `strlen()` bounds check.
- **[MEDIUM] Fix relative `require_once` paths** (Bug 9): `require_once('fpdf_tpl.php')` and `require_once('tcpdi_parser.php')` relied on include_path. Fixed to use `__DIR__` for reliable resolution.

### Development

- Added PHPUnit 9.6 test suite with 30 tests covering all fixed bugs
- Added `phpunit.xml` configuration
- Added `autoload-dev` PSR-4 mapping for tests
