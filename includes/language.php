<?php
/**
 * ============================================================
 *  Divya Aastha — Global Language System (en / bn / hi)
 * ============================================================
 *  Central i18n layer used by every page of the site.
 *
 *  Public helpers
 *  --------------
 *  __($key, ...$args)   translate a manual dictionary key
 *  __t($englishPhrase)  translate a raw English phrase at runtime
 *  current_lang()       active language code (en|bn|hi)
 *  lang_name($code)     display name of a language
 *  lang_url($code)      switcher URL that keeps the current query
 *  localize_digits($s)  ASCII digits  -> digits of active language
 *  localize_date($s)    English months/digits -> active language
 *
 *  Output buffer
 *  -------------
 *  Every rendered page is passed through __translate_html():
 *    - text nodes        -> phrase dictionary
 *    - safe attributes   -> phrase dictionary (alt/title/placeholder/aria-*)
 *    - <script> literals -> phrase dictionary (visible messages only)
 *    - prices / % / grouped numbers and dates -> locale digits
 *  <style>, <pre>, <code> and JSON responses are never touched.
 * ============================================================
 */

if (defined('DIVYA_I18N_LOADED')) {
    return;
}
define('DIVYA_I18N_LOADED', true);

/** Supported language codes */
const DIVYA_LANGS = ['en', 'bn', 'hi'];

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ══════════════════════════════════════════════════════════
 *  1) Language switch request (?lang=bn) — persists in session + cookie
 * ══════════════════════════════════════════════════════════ */
if (isset($_GET['lang']) && is_string($_GET['lang']) && in_array($_GET['lang'], DIVYA_LANGS, true)) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $lang;
    setcookie('lang', $lang, [
        'expires'  => time() + 60 * 60 * 24 * 365,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    // redirect back to the same page, keeping every other query param
    $url    = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    $params = $_GET;
    unset($params['lang']);
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    if (!headers_sent()) {
        header('Location: ' . $url);
    }
    exit;
}

/* ══════════════════════════════════════════════════════════
 *  2) Active language
 * ══════════════════════════════════════════════════════════ */
$current_lang = $_SESSION['lang'] ?? $_COOKIE['lang'] ?? 'en';
if (!is_string($current_lang) || !in_array($current_lang, DIVYA_LANGS, true)) {
    $current_lang = 'en';
}
$GLOBALS['__lang'] = $current_lang;

// Keep the session choice alive in a long-lived cookie so the language
// survives session expiry, new devices and browser restarts.
if (isset($_COOKIE['lang']) ? $_COOKIE['lang'] !== $current_lang : true) {
    setcookie('lang', $current_lang, [
        'expires'  => time() + 60 * 60 * 24 * 365,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/* ══════════════════════════════════════════════════════════
 *  3) Dictionaries  (lang/<code>.php may merge phrase packs)
 * ══════════════════════════════════════════════════════════ */
$GLOBALS['__translations'] = require __DIR__ . '/lang/' . $current_lang . '.php';
$GLOBALS['__fallback']     = $current_lang === 'en'
    ? $GLOBALS['__translations']
    : require __DIR__ . '/lang/en.php';

/* ══════════════════════════════════════════════════════════
 *  4) Manual dictionary helpers
 * ══════════════════════════════════════════════════════════ */
if (!function_exists('__')) {
    function __($key, ...$args) {
        $text = $GLOBALS['__translations'][$key] ?? $GLOBALS['__fallback'][$key] ?? null;
        if ($text === null) {
            // allow __('An English sentence.') to fall back to the phrase pack
            $text = __t((string) $key);
        }
        if (!is_string($text)) {
            $text = (string) $key;
        }
        if ($args) {
            $text = @vsprintf($text, $args);
        }
        return $text;
    }
}

if (!function_exists('__t')) {
    /** Translate a raw English phrase (used by handlers / flash messages). */
    function __t(string $phrase): string {
        if ($phrase === '' || $GLOBALS['__lang'] === 'en') {
            return $phrase;
        }
        $norm = __normalize_text($phrase);
        if ($norm === '') {
            return $phrase;
        }
        $map = __phrase_map();
        if (isset($map[$norm])) {
            return $map[$norm];
        }
        $out = __phrase_replace($norm);
        if ($out === $norm) {
            return $phrase;        // no entry -> keep original wording
        }
        return $out;
    }
}

if (!function_exists('current_lang')) {
    function current_lang(): string {
        return $GLOBALS['__lang'];
    }
}

if (!function_exists('lang_name')) {
    function lang_name(?string $code = null): string {
        $code = $code ?: current_lang();
        return $GLOBALS['__translations']['lang_' . $code] ?? $code;
    }
}

if (!function_exists('lang_url')) {
    /** Language switcher URL — keeps the current path + query string. */
    function lang_url(string $code): string {
        if (!in_array($code, DIVYA_LANGS, true)) {
            $code = 'en';
        }
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';
        $path   = strtok($uri, '?');
        $params = [];
        $qs     = parse_url($uri, PHP_URL_QUERY);
        if (is_string($qs) && $qs !== '') {
            parse_str($qs, $params);
        }
        $params['lang'] = $code;
        return $path . '?' . http_build_query($params);
    }
}

/* ══════════════════════════════════════════════════════════
 *  5) Phrase dictionary (English -> active language)
 * ══════════════════════════════════════════════════════════ */
if (!function_exists('__phrase_map')) {
    /**
     * Normalised phrase map:
     *   ['map' => [english => translation], 'max' => max word count, 'first' => first-word index].
     * Built once per request.
     */
    function __phrase_map(): array {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $map   = [];
        $first = [];
        $maxLen = 1;
        foreach ($GLOBALS['__translations']['__phrases'] ?? [] as $key => $value) {
            if (!is_string($key) || !is_string($value) || $value === '') {
                continue;
            }
            $key = preg_replace('/\s+/u', ' ', trim($key));
            if ($key === '') {
                continue;
            }
            $map[$key] = $value;
            $words = explode(' ', $key);
            $first[$words[0]] = true;                 // first-word index: O(1) skip
            if (preg_match('/^[^\p{L}\p{N}]+(.+)$/us', $words[0], $fm)) {
                $first[$fm[1]] = true;                // "`Continue Shopping!`" style keys
            }
            $count = count($words);
            if ($count > $maxLen) {
                $maxLen = $count;
            }
        }
        $cache = ['map' => $map, 'max' => min($maxLen, 200), 'first' => $first];
        return $cache;
    }
}

/* ══════════════════════════════════════════════════════════
 *  6) Text normalisation + phrase replacement
 * ══════════════════════════════════════════════════════════ */
if (!function_exists('__normalize_text')) {
    function __normalize_text(string $text): string {
        $text = str_replace(
            ['&nbsp;', '&amp;', '&#039;', '&#39;', '&quot;', '&#8217;', '&#8216;', '&rsquo;'],
            [' ',  '&',    "'",      "'",      '"',      "'",       "'",       "'"],
            $text
        );
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim((string) $text);
    }
}

if (!function_exists('__escape_amp')) {
    /** Re-escape bare "&" only when the source actually used "&amp;". */
    function __escape_amp(string $original, string $result): string {
        if (strpos($original, '&amp;') !== false) {
            $result = preg_replace('/&(?![a-zA-Z][a-zA-Z0-9]{1,10};|#\d{1,7};)/', '&amp;', $result);
        }
        return $result;
    }
}

if (!function_exists('__phrase_replace')) {
    /**
     * Word-boundary safe, longest-first phrase replacement.
     * Operates on whitespace normalised text (HTML collapses it anyway).
     */
    function __phrase_replace(string $text): string {
        $cfg = __phrase_map();
        $map = $cfg['map'];
        if ($map === [] || $text === '') {
            return $text;
        }
        $max   = $cfg['max'];
        $first = $cfg['first'];
        $words = explode(' ', $text);
        $count = count($words);
        $out   = [];
        $i     = 0;

        while ($i < $count) {
            $done = false;
            // Fast path: only attempt a window match when the current word can
            // actually start a known phrase (raw or punctuation-trimmed).
            $fw = $words[$i];
            $canStart = isset($first[$fw]);
            if (!$canStart && preg_match('/^(.+?)[^\p{L}\p{N}]+$/us', $fw, $fmTail)) {
                $canStart = isset($first[$fmTail[1]]);   // `Name:` style keys
            }
            if (!$canStart && !preg_match('/^[\p{L}\p{N}]/u', $fw)
                && preg_match('/[\p{L}\p{N}][\p{L}\p{N}].*$/us', $fw, $fm2)) {
                $canStart = isset($first[$fm2[0]]);
            }
            if ($canStart) {
                $limit = min($max, $count - $i);
                for ($len = $limit; $len >= 1; $len--) {
                    $cand = $len === 1 ? $words[$i] : implode(' ', array_slice($words, $i, $len));
                    if (isset($map[$cand])) {
                        $out[] = $map[$cand];
                        $i    += $len;
                        $done  = true;
                        break;
                    }
                    // tolerate punctuation glued to the phrase: `Continue Shopping!`
                    if (preg_match('/^([^\p{L}\p{N}]*)(.*?)([^\p{L}\p{N}]*)$/us', $cand, $pm)
                        && $pm[2] !== '' && $pm[2] !== $cand && isset($map[$pm[2]])) {
                        $out[] = $pm[1] . $map[$pm[2]] . $pm[3];
                        $i    += $len;
                        $done  = true;
                        break;
                    }
                }
            }
            if (!$done) {
                $out[] = $words[$i];
                $i++;
            }
        }
        return implode(' ', $out);
    }
}

/* ══════════════════════════════════════════════════════════
 *  7) Locale aware numbers / currency / dates
 * ══════════════════════════════════════════════════════════ */
if (!function_exists('__digit_map')) {
    function __digit_map(?string $lang = null): array {
        $lang = $lang ?: ($GLOBALS['__lang'] ?? 'en');
        if ($lang === 'bn') {
            return ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪',
                    '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯'];
        }
        if ($lang === 'hi') {
            return ['0' => '०', '1' => '१', '2' => '२', '3' => '३', '4' => '४',
                    '5' => '५', '6' => '६', '7' => '७', '8' => '८', '9' => '९'];
        }
        return [];
    }
}

if (!function_exists('localize_digits')) {
    /** Convert every ASCII digit in $s to the active language's digits. */
    function localize_digits(string $s, ?string $lang = null): string {
        $map = __digit_map($lang);
        return $map === [] ? $s : strtr($s, $map);
    }
}

if (!function_exists('__month_map')) {
    function __month_map(?string $lang = null): array {
        $lang = $lang ?: ($GLOBALS['__lang'] ?? 'en');
        if ($lang === 'bn') {
            return ['January' => 'জানুয়ারি', 'February' => 'ফেব্রুয়ারি', 'March' => 'মার্চ', 'April' => 'এপ্রিল',
                    'May' => 'মে', 'June' => 'জুন', 'July' => 'জুলাই', 'August' => 'আগস্ট',
                    'September' => 'সেপ্টেম্বর', 'October' => 'অক্টোবর', 'November' => 'নভেম্বর', 'December' => 'ডিসেম্বর',
                    'Jan' => 'জানু', 'Feb' => 'ফেব্রু', 'Mar' => 'মার্চ', 'Apr' => 'এপ্রিল', 'Jun' => 'জুন', 'Jul' => 'জুলাই',
                    'Aug' => 'আগ', 'Sep' => 'সেপ্ট', 'Oct' => 'অক্টো', 'Nov' => 'নভে', 'Dec' => 'ডিসে'];
        }
        if ($lang === 'hi') {
            return ['January' => 'जनवरी', 'February' => 'फ़रवरी', 'March' => 'मार्च', 'April' => 'अप्रैल',
                    'May' => 'मई', 'June' => 'जून', 'July' => 'जुलाई', 'August' => 'अगस्त',
                    'September' => 'सितंबर', 'October' => 'अक्टूबर', 'November' => 'नवंबर', 'December' => 'दिसंबर',
                    'Jan' => 'जन', 'Feb' => 'फ़र', 'Mar' => 'मार्च', 'Apr' => 'अप्रैल', 'Jun' => 'जून', 'Jul' => 'जुल',
                    'Aug' => 'अग', 'Sep' => 'सित', 'Oct' => 'अक्टू', 'Nov' => 'नव', 'Dec' => 'दिस'];
        }
        return [];
    }
}

if (!function_exists('localize_date')) {
    /**
     * "12 Oct 2026" -> "১২ অক্টো ২০২৬" (bn) / "१२ अक्टू २०२६" (hi)
     * Only touches recognised month names, so it is safe on any date string.
     */
    function localize_date(string $s, ?string $lang = null): string {
        $lang = $lang ?: ($GLOBALS['__lang'] ?? 'en');
        if ($lang === 'en' || $s === '') {
            return $s;
        }
        $months = __month_map($lang);
        if ($months !== []) {
            $names = array_map(function ($n) { return preg_quote($n, '/'); }, array_keys($months));
            // longest name first so "January" wins over "Jan"
            usort($names, function ($a, $b) { return strlen($b) - strlen($a); });
            $s = preg_replace_callback('/\b(' . implode('|', $names) . ')\b/u', function ($m) use ($months) {
                return $months[$m[1]] ?? $m[1];
            }, $s) ?? $s;
        }
        return localize_digits($s, $lang);
    }
}

if (!function_exists('__localize_output')) {
    /**
     * Locale formatting for rendered text:
     *   prices (₹1,500) · percentages (10%) · grouped numbers (1,500) · dates.
     * Plain integers (ids, quantities, counters) are deliberately left alone.
     */
    function __localize_output(string $text): string {
        $lang = $GLOBALS['__lang'] ?? 'en';
        if ($lang === 'en' || $text === '') {
            return $text;
        }
        $digits = __digit_map($lang);
        if ($digits === []) {
            return $text;
        }

        // 1) currency  ₹1,500 / ₹ 999.50
        $text = preg_replace_callback('/(₹\s?)([0-9][0-9,]*(?:\.[0-9]+)?)/u',
            function ($m) use ($digits) { return $m[1] . strtr($m[2], $digits); }, $text);

        // 2) percentages  10%
        $text = preg_replace_callback('/([0-9][0-9,]*(?:\.[0-9]+)?)\s?%/u',
            function ($m) use ($digits) { return strtr($m[1], $digits) . '%'; }, $text);

        // 3) grouped numbers  1,500 / 12,500.75  (never plain integers)
        $text = preg_replace_callback('/\b[0-9]{1,3}(?:,[0-9]{3})+(?:\.[0-9]+)?\b/',
            function ($m) use ($digits) { return strtr($m[0], $digits); }, $text);

        // 4) dates  12 Oct 2026 / 12 October 2026
        return localize_date($text, $lang);
    }
}

/* ══════════════════════════════════════════════════════════
 *  8) HTML / JS / attribute translation (output buffer core)
 * ══════════════════════════════════════════════════════════ */
if (!function_exists('__translate_fragment')) {
    /** Normalise -> phrase replace -> locale format, for one text run. */
    function __translate_fragment(string $run): string {
        $norm = __normalize_text($run);
        if ($norm === '') {
            return $run;
        }
        $out = __phrase_replace($norm);
        $out = __localize_output($out);
        if ($out === $norm) {
            return $run;            // nothing changed -> keep original bytes
        }
        return __escape_amp($run, $out);
    }
}

if (!function_exists('__translate_text')) {
    function __translate_text(string $text): string {
        if (trim($text) === '') {
            return $text;
        }
        return __translate_fragment($text);
    }
}

if (!function_exists('__translate_tag')) {
    /**
     * Translates only "human readable" attributes.
     * Structural attributes (href, name, value, id, class, data-*) are never touched,
     * so forms, actions and JavaScript behaviour stay exactly the same.
     */
    function __translate_tag(string $tag): string {
        if (strpos($tag, '=') === false) {
            return $tag;
        }
        return preg_replace_callback(
            '/\s(placeholder|title|alt|aria-label|aria-placeholder|aria-valuetext|aria-roledescription)\s*=\s*("([^"]*)"|\'([^\']*)\')/i',
            function ($m) {
                $value = ($m[3] ?? '') !== '' ? $m[3] : ($m[4] ?? '');
                if (trim($value) === '') {
                    return $m[0];
                }
                $new = __translate_fragment($value);
                if ($new === $value) {
                    return $m[0];
                }
                $eq   = strpos($m[0], '=');
                $head = rtrim(substr($m[0], 0, $eq + 1));
                $quote = ($m[3] ?? '') !== '' ? '"' : "'";
                return $head . $quote . $new . $quote;
            },
            $tag
        );
    }
}

if (!function_exists('__translate_js')) {
    /**
     * Translates English messages that live inside inline <script> blocks.
     * Only string literals are inspected — identifiers, keys and logic are untouched.
     */
    function __translate_js(string $code): string {
        if (trim($code) === '') {
            return $code;
        }
        return preg_replace_callback(
            '#\'((?:\\\\.|[^\'\\\\])*)\'|"((?:\\\\.|[^"\\\\])*)"|`((?:\\\\.|[^`\\\\])*)`#s',
            function ($m) {
                if (($m[1] ?? '') !== '') {
                    $inner = $m[1]; $quote = "'";
                } elseif (($m[2] ?? '') !== '') {
                    $inner = $m[2]; $quote = '"';
                } elseif (($m[3] ?? '') !== '') {
                    $inner = $m[3]; $quote = '`';
                } else {
                    return $m[0];
                }
                if (trim($inner) === '') {
                    return $m[0];
                }
                // HTML template literal -> run the normal HTML pipeline on it
                if (preg_match('/<[a-z][^>]*>/i', $inner)) {
                    $new = __translate_segments($inner);
                    return $new === $inner ? $m[0] : $quote . str_replace($quote, '\\' . $quote, $new) . $quote;
                }
                $norm = __normalize_text($inner);
                if ($norm === '') {
                    return $m[0];
                }
                $new = __phrase_replace($norm);
                if ($new === $norm) {
                    return $m[0];
                }
                return $quote . str_replace($quote, '\\' . $quote, $new) . $quote;
            },
            $code
        );
    }
}

if (!function_exists('__translate_segments')) {
    /**
     * Walks an HTML document and translates the visible parts.
     * Comments, <style>, <pre> and <code> are returned untouched.
     */
    function __translate_segments(string $html): string {
        static $pattern = null;
        if ($pattern === null) {
            $pattern = '#'
                . '(?P<comment><!--.*?(?:-->|\z))'
                . '|(?P<style><style\b[^>]*>.*?(?:</style>|\z))'
                . '|(?P<pre><(?:pre|code)\b[^>]*>.*?(?:</(?:pre|code)>|\z))'
                . '|(?P<script><script\b(?P<sattrs>[^>]*)>)(?P<sbody>.*?)(?P<sclose></script>|\z)'
                . '|(?P<tag><[^>]+>)'
                . '|(?P<text>[^<]+)'
                . '#is';
        }

        return preg_replace_callback($pattern, function ($m) {
            $first = $m[0][0] ?? '';
            if ($first === '<') {
                if (strncmp($m[0], '<!--', 4) === 0) return $m[0];
                if (stripos($m[0], '<style') === 0)  return $m[0];
                if (preg_match('#^<(pre|code)\b#i', $m[0])) return $m[0];
                if (stripos($m[0], '<script') === 0) {
                    $attrs = $m['sattrs'] ?? '';
                    // JSON / template data islands are never rewritten
                    if (preg_match('#type\s*=\s*["\'](?:application/(?:ld\+)?json|text/template|text/plain)["\']#i', $attrs)) {
                        return $m[0];
                    }
                    return $m['script'] . __translate_js($m['sbody'] ?? '') . ($m['sclose'] ?? '');
                }
                if (($m['tag'] ?? '') !== '') return __translate_tag($m['tag']);
                return $m[0];
            }
            if (($m['text'] ?? '') !== '') return __translate_text($m['text']);
            return $m[0];
        }, $html) ?? $html;
    }
}

if (!function_exists('__response_is_html')) {
    /** Only rewrite HTML responses — JSON/CSV/downloads stay byte-identical. */
    function __response_is_html(string $html): bool {
        foreach (headers_list() as $header) {
            if (stripos($header, 'Content-Type:') === 0) {
                return stripos($header, 'text/html') !== false;
            }
        }
        // no content-type yet: treat raw JSON payloads (AJAX handlers) as JSON
        $trim = ltrim($html);
        if ($trim !== '' && ($trim[0] === '{' || $trim[0] === '[')) {
            return json_decode($trim) === null;
        }
        return true;
    }
}

/* ══════════════════════════════════════════════════════════
 *  8b) AJAX / JSON payload translation (display strings only)
 * ══════════════════════════════════════════════════════════ */
if (!function_exists('__json_display_keys')) {
    /**
     * Only these object keys are human-readable labels/messages.
     * Structural keys (ok, url, slug, redirect, qty, count, state …) are
     * never touched, so every JavaScript consumer keeps working unchanged.
     */
    function __json_display_keys(): array {
        static $keys = null;
        if ($keys === null) {
            $keys = array_flip([
                'message', 'msg', 'error', 'errors', 'notice', 'warning', 'success',
                'info', 'title', 'sub', 'tag', 'label', 'hint', 'description', 'desc',
                'text', 'name', 'badge', 'summary', 'subtitle', 'placeholder', 'note',
                'empty_text', 'reason', 'detail', 'details', 'comment', 'heading',
                'action_text', 'button', 'status_text', 'subtotal', 'total', 'amount',
                'price', 'shipping', 'tax', 'discount', 'grand_total', 'mrp',
            ]);
        }
        return $keys;
    }
}

if (!function_exists('__translate_json_data')) {
    /**
     * Recursively translate display strings inside decoded JSON.
     * $force = true translates every nested string (used under whitelisted
     * keys such as `errors` => ['Name is required', …]).
     */
    function __translate_json_data($data, bool $force = false) {
        if (!is_array($data)) {
            return $data;
        }
        $isList = array_keys($data) === range(0, count($data) - 1);
        foreach ($data as $k => $v) {
            $hit = $force
                || (!$isList && is_string($k) && isset(__json_display_keys()[mb_strtolower($k)]));
            if (is_string($v)) {
                if ($hit) {
                    $data[$k] = __translate_fragment($v);
                }
            } elseif (is_array($v)) {
                $data[$k] = __translate_json_data($v, $hit);
            }
        }
        return $data;
    }
}

if (!function_exists('__maybe_translate_json')) {
    /** Translate an AJAX JSON body (no-op for anything that is not JSON). */
    function __maybe_translate_json(string $body): string {
        $trim = ltrim($body);
        if ($trim === '' || ($trim[0] !== '{' && $trim[0] !== '[')) {
            return $body;
        }
        $data = json_decode($trim, true);
        if (!is_array($data)) {
            return $body;
        }
        $data = __translate_json_data($data);
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

if (!function_exists('__translate_html')) {
    /** Output-buffer callback — entry point for every rendered page. */
    function __translate_html(string $html): string {
        if ($html === '' || ($GLOBALS['__lang'] ?? 'en') === 'en') {
            return $html;
        }
        if (!__response_is_html($html)) {
            return __maybe_translate_json($html);
        }
        return __translate_segments($html);
    }
}

/* ══════════════════════════════════════════════════════════
 *  9) System on
 * ══════════════════════════════════════════════════════════ */
if (($GLOBALS['__lang'] ?? 'en') !== 'en') {
    ob_start('__translate_html');
}
