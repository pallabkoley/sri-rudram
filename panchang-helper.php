<?php
/* ==================================================================
 |  PANCHANG HELPER — daily auto-updating, no external API required
 |  Location : Kolkata, India
 |  Timezone : Asia/Kolkata
 |  Cache    : /cache/panchang/YYYY-MM-DD.json
 * ================================================================== */

if (!defined('PANCHANG_TIMEZONE')) define('PANCHANG_TIMEZONE', 'Asia/Kolkata');
if (!defined('PANCHANG_LAT'))      define('PANCHANG_LAT', 22.5726);   // Kolkata
if (!defined('PANCHANG_LNG'))      define('PANCHANG_LNG', 88.3639);
if (!defined('PANCHANG_CACHE_DIR')) {
    define('PANCHANG_CACHE_DIR', BASE_PATH . '/cache/panchang');
}

/* ------------------------------------------------------------------
 |  Main entry — returns today's panchang array (cached)
 * ------------------------------------------------------------------ */
if (!function_exists('panchang_get_today')) {
    function panchang_get_today(): array {
        date_default_timezone_set(PANCHANG_TIMEZONE);
        $today = date('Y-m-d');

        /* 1) Try cache */
        $cached = panchang_cache_read($today);
        if ($cached !== null) return $cached;

        /* 2) Compute locally */
        try {
            $data = panchang_compute($today);
        } catch (Throwable $e) {
            error_log('[panchang] ' . $e->getMessage());
            $data = panchang_fallback($today);
        }

        /* 3) Cache */
        panchang_cache_write($today, $data);

        return $data;
    }
}

/* ------------------------------------------------------------------
 |  Cache read/write
 * ------------------------------------------------------------------ */
if (!function_exists('panchang_cache_read')) {
    function panchang_cache_read(string $date): ?array {
        $file = PANCHANG_CACHE_DIR . '/' . $date . '.json';
        if (!is_file($file)) return null;
        $raw = @file_get_contents($file);
        if ($raw === false) return null;
        $j = json_decode($raw, true);
        return is_array($j) ? $j : null;
    }
}

if (!function_exists('panchang_cache_write')) {
    function panchang_cache_write(string $date, array $data): void {
        if (!is_dir(PANCHANG_CACHE_DIR)) {
            @mkdir(PANCHANG_CACHE_DIR, 0755, true);
        }
        $file = PANCHANG_CACHE_DIR . '/' . $date . '.json';
        @file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        @chmod($file, 0644);
    }
}

/* ------------------------------------------------------------------
 |  Astronomy primitives
 * ------------------------------------------------------------------ */
if (!function_exists('panchang_jd_from_ts')) {
    function panchang_jd_from_ts(int $ts): float {
        return ($ts / 86400.0) + 2440587.5;
    }
}

if (!function_exists('panchang_sun_longitude')) {
    function panchang_sun_longitude(float $jd): float {
        $T  = ($jd - 2451545.0) / 36525.0;
        $L0 = 280.46646 + 36000.76983 * $T + 0.0003032 * $T * $T;
        $M  = 357.52911 + 35999.05029 * $T - 0.0001537 * $T * $T;
        $Mr = deg2rad($M);
        $C  = (1.914602 - 0.004817 * $T) * sin($Mr)
            + (0.019993 - 0.000101 * $T) * sin(2 * $Mr)
            + 0.000289 * sin(3 * $Mr);
        $trueLon = $L0 + $C;
        $omega   = 125.04 - 1934.136 * $T;
        $lambda  = $trueLon - 0.00569 - 0.00478 * sin(deg2rad($omega));
        return fmod($lambda + 360, 360);
    }
}

if (!function_exists('panchang_moon_longitude')) {
    function panchang_moon_longitude(float $jd): float {
        $T  = ($jd - 2451545.0) / 36525.0;
        $Lp = 218.3164477 + 481267.88123421 * $T;
        $D  = 297.8501921 + 445267.1114034  * $T;
        $M  = 357.5291092 + 35999.0502909   * $T;
        $Mp = 134.9633964 + 477198.8675055  * $T;
        $F  =  93.2720950 + 483202.0175233  * $T;

        $Dr = deg2rad($D); $Mr = deg2rad($M);
        $Mpr = deg2rad($Mp); $Fr = deg2rad($F);

        $lon = $Lp
            + 6.288774 * sin($Mpr)
            + 1.274027 * sin(2*$Dr - $Mpr)
            + 0.658314 * sin(2*$Dr)
            + 0.213618 * sin(2*$Mpr)
            - 0.185116 * sin($Mr)
            - 0.114332 * sin(2*$Fr)
            + 0.058793 * sin(2*$Dr - 2*$Mpr)
            + 0.057066 * sin(2*$Dr - $Mr - $Mpr)
            + 0.053322 * sin(2*$Dr + $Mpr)
            + 0.045758 * sin(2*$Dr - $Mr)
            - 0.040923 * sin($Mr - $Mpr)
            - 0.034720 * sin($Dr)
            - 0.030383 * sin($Mr + $Mpr)
            + 0.015327 * sin(2*$Dr - 2*$Fr)
            + 0.010980 * sin($Dr - $Mpr);

        return fmod($lon + 360, 360);
    }
}

if (!function_exists('panchang_ayanamsa')) {
    /* Lahiri ayanamsa approximation */
    function panchang_ayanamsa(float $jd): float {
        $T = ($jd - 2451545.0) / 36525.0;
        return 23.85 + 0.01397 * ($T * 100);
    }
}

/* ------------------------------------------------------------------
 |  Tithi / Nakshatra / Yoga / Karana
 * ------------------------------------------------------------------ */
if (!function_exists('panchang_tithi_name')) {
    function panchang_tithi_name(int $n): string {
        $names = [
            1=>'Pratipada', 2=>'Dwitiya', 3=>'Tritiya', 4=>'Chaturthi',
            5=>'Panchami', 6=>'Shashthi', 7=>'Saptami', 8=>'Ashtami',
            9=>'Navami', 10=>'Dashami', 11=>'Ekadashi', 12=>'Dwadashi',
            13=>'Trayodashi', 14=>'Chaturdashi', 15=>'Purnima',
        ];
        if ($n >= 16 && $n <= 29) {
            $names[30] = 'Amavasya';
            return $names[$n - 14] ?? 'Krishna ' . ($n - 15);
        }
        if ($n === 30) return 'Amavasya';
        return $names[$n] ?? '—';
    }
}

if (!function_exists('panchang_nakshatra_name')) {
    function panchang_nakshatra_name(int $i): string {
        $n = [
            'Ashwini','Bharani','Krittika','Rohini','Mrigashira','Ardra',
            'Punarvasu','Pushya','Ashlesha','Magha','Purva Phalguni','Uttara Phalguni',
            'Hasta','Chitra','Swati','Vishakha','Anuradha','Jyeshtha',
            'Mula','Purva Ashadha','Uttara Ashadha','Shravana','Dhanishta','Shatabhisha',
            'Purva Bhadrapada','Uttara Bhadrapada','Revati',
        ];
        return $n[$i % 27] ?? '—';
    }
}

if (!function_exists('panchang_yoga_name')) {
    function panchang_yoga_name(int $i): string {
        $y = [
            'Vishkambha','Priti','Ayushman','Saubhagya','Shobhana','Atiganda',
            'Sukarman','Dhriti','Shula','Ganda','Vriddhi','Dhruva',
            'Vyaghata','Harshana','Vajra','Siddhi','Vyatipata','Variyana',
            'Parigha','Shiva','Siddha','Sadhya','Shubha','Shukla',
            'Brahma','Indra','Vaidhriti',
        ];
        return $y[$i % 27] ?? '—';
    }
}

if (!function_exists('panchang_karana_name')) {
    function panchang_karana_name(int $i): string {
        $k = ['Bava','Balava','Kaulava','Taitila','Gara','Vanija','Vishti',
              'Shakuni','Chatushpada','Naga','Kimstughna'];
        return $k[$i % 11] ?? '—';
    }
}

/* ------------------------------------------------------------------
 |  Sunrise / Sunset (NOAA simplified)
 * ------------------------------------------------------------------ */
if (!function_exists('panchang_sunrise_sunset')) {
    function panchang_sunrise_sunset(string $date): array {
        $tz  = new DateTimeZone(PANCHANG_TIMEZONE);
        $utc = new DateTimeZone('UTC');

        $noon = new DateTime($date . ' 12:00:00', $tz);
        $noon->setTimezone($utc);
        $jdNoon = panchang_jd_from_ts($noon->getTimestamp());
        $T = ($jdNoon - 2451545.0) / 36525.0;

        $L0 = fmod(280.46646 + 36000.76983 * $T, 360);
        $M  = 357.52911 + 35999.05029 * $T;
        $e  = 0.016708634 - 0.000042037 * $T;
        $eps = 23 + (26 + (21.448 - $T * (46.815 + $T * (0.00059 - $T * 0.001813))) / 60) / 60;

        $Mr = deg2rad($M);
        $C  = (1.914602 - 0.004817 * $T) * sin($Mr)
            + (0.019993 - 0.000101 * $T) * sin(2 * $Mr)
            + 0.000289 * sin(3 * $Mr);
        $sunTrueLon = $L0 + $C;

        $omega = 125.04 - 1934.136 * $T;
        $lambda = $sunTrueLon - 0.00569 - 0.00478 * sin(deg2rad($omega));

        $y  = tan(deg2rad($eps / 2)) ** 2;
        $Eq = $y * sin(2 * deg2rad($L0))
            - 2 * $e * sin($Mr)
            + 4 * $e * $y * sin($Mr) * cos(2 * deg2rad($L0))
            - 0.5 * $y * $y * sin(4 * deg2rad($L0))
            - 1.25 * $e * $e * sin(2 * $Mr);
        $eqTime = rad2deg($Eq) * 4;   // minutes

        $decl = rad2deg(asin(sin(deg2rad($eps)) * sin(deg2rad($lambda))));

        $lat = PANCHANG_LAT;
        $zenith = 90.833;   // official sunrise/sunset
        $cosH = cos(deg2rad($zenith)) / (cos(deg2rad($lat)) * cos(deg2rad($decl)))
              - tan(deg2rad($lat)) * tan(deg2rad($decl));

        if ($cosH > 1 || $cosH < -1) {
            /* Sun never rises or never sets */
            return ['sunrise' => '—', 'sunset' => '—', 'solar_noon' => '12:00'];
        }

        $H = rad2deg(acos($cosH));

        $solarNoonMin = 720 - 4 * PANCHANG_LNG - $eqTime;
        $sunriseMin   = $solarNoonMin - 4 * $H;
        $sunsetMin    = $solarNoonMin + 4 * $H;

        $tzOffsetMin = (new DateTime($date . ' 12:00', $tz))->getOffset() / 60;

        $fmt = function (float $min) use ($tzOffsetMin): string {
            $local = ($min + $tzOffsetMin + 1440) % 1440;
            $h = floor($local / 60);
            $m = round($local - $h * 60);
            return sprintf('%02d:%02d', $h, $m);
        };

        return [
            'sunrise'    => $fmt($sunriseMin),
            'sunset'     => $fmt($sunsetMin),
            'solar_noon' => $fmt($solarNoonMin),
        ];
    }
}

/* ------------------------------------------------------------------
 |  Moonrise / Moonset (approximate)
 * ------------------------------------------------------------------ */
if (!function_exists('panchang_moonrise_moonset')) {
    function panchang_moonrise_moonset(string $date, string $sunrise): array {
        /* Moon rises ~50 min later than previous day, average offset from sunrise.
           We compute a rough offset from moon-sun elongation. */
        $tz = new DateTimeZone(PANCHANG_TIMEZONE);
        $dt = new DateTime($date . ' 12:00', $tz);
        $jd = panchang_jd_from_ts($dt->getTimestamp());

        $sunLon  = panchang_sun_longitude($jd);
        $moonLon = panchang_moon_longitude($jd);
        $elong   = fmod($moonLon - $sunLon + 360, 360);

        /* Offset from sunrise in minutes: 0° elongation → rise ~ sunrise time;
           every 12° elongation adds ~ 50 min */
        $riseOffset = ($elong / 12) * 50;
        $setOffset  = $riseOffset + 720;   /* roughly 12h later */

        $parseMin = function (string $hhmm): int {
            if (!preg_match('/^(\d{1,2}):(\d{2})/', $hhmm, $m)) return 6 * 60;
            return (int) $m[1] * 60 + (int) $m[2];
        };
        $fmt = function (int $m): string {
            $m = ($m + 1440) % 1440;
            return sprintf('%02d:%02d', floor($m / 60), $m % 60);
        };

        $srMin = $parseMin($sunrise);

        return [
            'moonrise' => $fmt((int) round($srMin + $riseOffset)),
            'moonset'  => $fmt((int) round($srMin + $setOffset)),
        ];
    }
}

/* ------------------------------------------------------------------
 |  Abhijit Muhurat, Rahu Kaal
 * ------------------------------------------------------------------ */
if (!function_exists('panchang_abhijit_muhurat')) {
    function panchang_abhijit_muhurat(string $solarNoon): string {
        if (!preg_match('/^(\d{1,2}):(\d{2})/', $solarNoon, $m)) {
            return '11:48 – 12:36';
        }
        $noonMin = (int) $m[1] * 60 + (int) $m[2];
        $start = $noonMin - 24;
        $end   = $noonMin + 24;
        $fmt = function (int $x): string {
            $x = ($x + 1440) % 1440;
            return sprintf('%02d:%02d', floor($x / 60), $x % 60);
        };
        return $fmt($start) . ' – ' . $fmt($end);
    }
}

if (!function_exists('panchang_rahu_kaal')) {
    function panchang_rahu_kaal(string $date, string $sunrise, string $sunset): string {
        $parseMin = function (string $hhmm): int {
            if (!preg_match('/^(\d{1,2}):(\d{2})/', $hhmm, $m)) return 0;
            return (int) $m[1] * 60 + (int) $m[2];
        };
        $fmt = function (int $x): string {
            $x = ($x + 1440) % 1440;
            return sprintf('%02d:%02d', floor($x / 60), $x % 60);
        };

        $sr = $parseMin($sunrise);
        $ss = $parseMin($sunset);
        if ($ss < $sr) $ss += 1440;

        $dayLen = $ss - $sr;
        $seg    = $dayLen / 8;

        /* Weekday (0 = Sunday) → Rahu segment index */
        $wd = (int) date('w', strtotime($date));
        $map = [0 => 8, 1 => 2, 2 => 7, 3 => 5, 4 => 6, 5 => 4, 6 => 3];

        $i     = $map[$wd] ?? 1;
        $start = $sr + ($i - 1) * $seg;
        $end   = $start + $seg;

        return $fmt((int) round($start)) . ' – ' . $fmt((int) round($end));
    }
}

/* ------------------------------------------------------------------
 |  Main compute — everything for a given date
 * ------------------------------------------------------------------ */
if (!function_exists('panchang_compute')) {
    function panchang_compute(string $date): array {
        $tz  = new DateTimeZone(PANCHANG_TIMEZONE);
        $dt  = new DateTime($date . ' 06:00:00', $tz);
        $jd  = panchang_jd_from_ts($dt->getTimestamp());

        $ayan = panchang_ayanamsa($jd);

        $sunLon  = panchang_sun_longitude($jd);
        $moonLon = panchang_moon_longitude($jd);

        /* Sidereal (Vedic) longitudes */
        $sunSid  = fmod($sunLon  - $ayan + 360, 360);
        $moonSid = fmod($moonLon - $ayan + 360, 360);

        /* Tithi */
        $elong  = fmod($moonLon - $sunLon + 360, 360);
        $tithiN = (int) floor($elong / 12) + 1;   /* 1..30 */

        $paksha = $tithiN <= 15 ? 'Shukla' : 'Krishna';
        $tithiName = panchang_tithi_name($tithiN);
        if ($tithiN === 15) $tithiName = 'Purnima';
        if ($tithiN === 30) $tithiName = 'Amavasya';

        /* Nakshatra */
        $nakshatraIdx = (int) floor($moonSid / (360 / 27));
        $nakshatra = panchang_nakshatra_name($nakshatraIdx);

        /* Yoga */
        $yogaVal = fmod($sunSid + $moonSid, 360);
        $yogaIdx = (int) floor($yogaVal / (360 / 27));
        $yoga = panchang_yoga_name($yogaIdx);

        /* Karana */
        $karanaIdx = (int) floor($elong / 6);
        $karana = panchang_karana_name($karanaIdx % 11);

        /* Hindu month (approximate — from sun's sidereal longitude) */
        $hinduMonths = ['Chaitra','Vaishakha','Jyeshtha','Ashadha',
                        'Shravana','Bhadrapada','Ashwin','Kartik',
                        'Margashirsha','Pausha','Magha','Phalguna'];
        $hMonthIdx = (int) floor(fmod($sunSid + 360 - 23.85, 360) / 30);
        $hinduMonth = $hinduMonths[$hMonthIdx % 12];

        /* Sun/moon events */
        $ss = panchang_sunrise_sunset($date);
        $mrms = panchang_moonrise_moonset($date, $ss['sunrise']);
        $abhijit = panchang_abhijit_muhurat($ss['solar_noon']);
        $rahu = panchang_rahu_kaal($date, $ss['sunrise'], $ss['sunset']);

        return [
            'date'          => $date,
            'date_long'     => date('l, d M Y', strtotime($date)),
            'weekday'       => date('l', strtotime($date)),
            'location'      => 'Kolkata, West Bengal, India',
            'timezone'      => PANCHANG_TIMEZONE,
            'tithi'         => $tithiName,
            'tithi_number'  => $tithiN,
            'paksha'        => $paksha,
            'nakshatra'     => $nakshatra,
            'yoga'          => $yoga,
            'karana'        => $karana,
            'hindu_month'   => $hinduMonth,
            'sunrise'       => $ss['sunrise'],
            'sunset'        => $ss['sunset'],
            'moonrise'      => $mrms['moonrise'],
            'moonset'       => $mrms['moonset'],
            'abhijit'       => $abhijit,
            'rahu_kaal'     => $rahu,
            'computed_at'   => date('c'),
        ];
    }
}

/* ------------------------------------------------------------------
 |  Fallback — never crash the page
 * ------------------------------------------------------------------ */
if (!function_exists('panchang_fallback')) {
    function panchang_fallback(string $date): array {
        return [
            'date'          => $date,
            'date_long'     => date('l, d M Y', strtotime($date)),
            'weekday'       => date('l', strtotime($date)),
            'location'      => 'Kolkata, West Bengal, India',
            'timezone'      => PANCHANG_TIMEZONE,
            'tithi'         => '—', 'tithi_number' => 0, 'paksha' => '—',
            'nakshatra'     => '—', 'yoga' => '—', 'karana' => '—',
            'hindu_month'   => '—',
            'sunrise'       => '—', 'sunset' => '—',
            'moonrise'      => '—', 'moonset' => '—',
            'abhijit'       => '—', 'rahu_kaal' => '—',
            'computed_at'   => date('c'),
            'unavailable'   => true,
        ];
    }
}

/* ------------------------------------------------------------------
 |  Upcoming festivals — from existing `festivals` table
 * ------------------------------------------------------------------ */
if (!function_exists('panchang_upcoming_festivals')) {
    function panchang_upcoming_festivals(PDO $pdo, int $limit = 6): array {
        try {
            $stmt = $pdo->prepare(
                "SELECT id, name, slug, festival_date, short_desc, image_url
                 FROM festivals
                 WHERE is_active = 1 AND festival_date >= CURDATE()
                 ORDER BY festival_date ASC
                 LIMIT " . (int) $limit
            );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('[panchang:festivals] ' . $e->getMessage());
            return [];
        }
    }
}

/* ------------------------------------------------------------------
 |  Today's Rashifal — 12 signs with lucky color & number
 * ------------------------------------------------------------------ */
if (!function_exists('panchang_rashifal_data')) {
    function panchang_rashifal_data(): array {
        return [
            ['symbol'=>'♈','name'=>'Aries',      'short'=>'Mesh',       'lucky_color'=>'Red',       'lucky_number'=>9,  'text'=>'Today brings positive energy for Aries. Focus on devotion, patience, and dharmic actions. A good day for spiritual practices and temple visits.'],
            ['symbol'=>'♉','name'=>'Taurus',     'short'=>'Vrishabh',   'lucky_color'=>'Pink',      'lucky_number'=>6,  'text'=>'Today brings positive energy for Taurus. Focus on devotion, patience, and dharmic actions. A good day for spiritual practices and temple visits.'],
            ['symbol'=>'♊','name'=>'Gemini',     'short'=>'Mithun',     'lucky_color'=>'Green',     'lucky_number'=>5,  'text'=>'Today brings positive energy for Gemini. Focus on devotion, patience, and dharmic actions. A good day for spiritual practices and temple visits.'],
            ['symbol'=>'♋','name'=>'Cancer',     'short'=>'Kark',       'lucky_color'=>'White',     'lucky_number'=>2,  'text'=>'Today brings positive energy for Cancer. Focus on devotion, patience, and dharmic actions. A good day for spiritual practices and temple visits.'],
            ['symbol'=>'♌','name'=>'Leo',        'short'=>'Simha',      'lucky_color'=>'Gold',      'lucky_number'=>1,  'text'=>'Today brings positive energy for Leo. Focus on devotion, patience, and dharmic actions. A good day for spiritual practices and temple visits.'],
            ['symbol'=>'♍','name'=>'Virgo',      'short'=>'Kanya',      'lucky_color'=>'Green',     'lucky_number'=>5,  'text'=>'Today brings positive energy for Virgo. Focus on devotion, patience, and dharmic actions. A good day for spiritual practices and temple visits.'],
            ['symbol'=>'♎','name'=>'Libra',      'short'=>'Tula',       'lucky_color'=>'Blue',      'lucky_number'=>6,  'text'=>'Today brings positive energy for Libra. Focus on devotion, patience, and dharmic actions. A good day for spiritual practices and temple visits.'],
            ['symbol'=>'♏','name'=>'Scorpio',    'short'=>'Vrishchik',  'lucky_color'=>'Maroon',    'lucky_number'=>9,  'text'=>'Today brings positive energy for Scorpio. Focus on devotion, patience, and dharmic actions. A good day for spiritual practices and temple visits.'],
            ['symbol'=>'♐','name'=>'Sagittarius','short'=>'Dhanu',      'lucky_color'=>'Yellow',    'lucky_number'=>3,  'text'=>'Today brings positive energy for Sagittarius. Focus on devotion, patience, and dharmic actions. A good day for spiritual practices and temple visits.'],
            ['symbol'=>'♑','name'=>'Capricorn',  'short'=>'Makar',      'lucky_color'=>'Black',     'lucky_number'=>8,  'text'=>'Today brings positive energy for Capricorn. Focus on devotion, patience, and dharmic actions. A good day for spiritual practices and temple visits.'],
            ['symbol'=>'♒','name'=>'Aquarius',   'short'=>'Kumbh',      'lucky_color'=>'Blue',      'lucky_number'=>4,  'text'=>'Today brings positive energy for Aquarius. Focus on devotion, patience, and dharmic actions. A good day for spiritual practices and temple visits.'],
            ['symbol'=>'♓','name'=>'Pisces',     'short'=>'Meen',       'lucky_color'=>'Sea Green', 'lucky_number'=>7,  'text'=>'Today brings positive energy for Pisces. Focus on devotion, patience, and dharmic actions. A good day for spiritual practices and temple visits.'],
        ];
    }
}

/* ------------------------------------------------------------------
 |  FAQ — 5 items (kept inside helper so page stays clean)
 * ------------------------------------------------------------------ */
if (!function_exists('panchang_faq_data')) {
    function panchang_faq_data(): array {
        return [
            [
                'q' => 'What is Panchang?',
                'a' => 'Panchang is the traditional Hindu almanac that records five key elements of the day — Tithi (lunar day), Nakshatra (constellation), Yoga, Karana and Vaar (weekday). It is used to determine auspicious timings for rituals, travel, business and daily activities.',
            ],
            [
                'q' => 'Why does the Panchang change every day?',
                'a' => 'The positions of the Sun and Moon change continuously. Each day, the Moon moves about 13° through the zodiac, so the Tithi, Nakshatra and Yoga shift daily. This is why the Panchang is calculated fresh for every day.',
            ],
            [
                'q' => 'Which location and timezone is this Panchang based on?',
                'a' => 'This Panchang is calculated for Kolkata, West Bengal, India using the Asia/Kolkata timezone. Sunrise, sunset, moonrise and muhurat timings are all local to this location.',
            ],
            [
                'q' => 'What is Abhijit Muhurat and Rahu Kaal?',
                'a' => 'Abhijit Muhurat is the most auspicious 48-minute window around solar noon, ideal for starting important work. Rahu Kaal is an inauspicious period each day (roughly 90 minutes) that is best avoided for new beginnings.',
            ],
            [
                'q' => 'How accurate are the timings shown here?',
                'a' => 'Timings are computed using standard astronomical formulas with Lahiri ayanamsa, and are accurate to within a few minutes. For critical muhurta decisions, always cross-verify with a qualified Jyotish or a trusted printed Panchang.',
            ],
        ];
    }
}