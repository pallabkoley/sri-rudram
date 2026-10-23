<?php
require_once __DIR__ . '/config/config.php';

date_default_timezone_set('Asia/Kolkata');

/* ==================================================================
 |  INLINE PANCHANG HELPERS
 * ================================================================== */
if (!defined('PANCHANG_CACHE_DIR')) {
    $__primary = BASE_PATH . '/cache/panchang';
    if (!is_dir($__primary)) @mkdir($__primary, 0755, true);
    if (is_dir($__primary) && is_writable($__primary)) {
        define('PANCHANG_CACHE_DIR', $__primary);
    } else {
        $__fb = sys_get_temp_dir() . '/divya_panchang';
        if (!is_dir($__fb)) @mkdir($__fb, 0755, true);
        define('PANCHANG_CACHE_DIR', $__fb);
    }
}

if (!function_exists('pn_jd')) { function pn_jd(int $ts): float { return $ts/86400.0 + 2440587.5; } }
if (!function_exists('pn_sun_lon')) {
    function pn_sun_lon(float $jd): float {
        $T=($jd-2451545.0)/36525.0;
        $L0=280.46646+36000.76983*$T+0.0003032*$T*$T;
        $M=357.52911+35999.05029*$T-0.0001537*$T*$T; $Mr=deg2rad($M);
        $C=(1.914602-0.004817*$T)*sin($Mr)+(0.019993-0.000101*$T)*sin(2*$Mr)+0.000289*sin(3*$Mr);
        $trueLon=$L0+$C; $omega=125.04-1934.136*$T;
        $lambda=$trueLon-0.00569-0.00478*sin(deg2rad($omega));
        return fmod($lambda+360,360);
    }
}
if (!function_exists('pn_moon_lon')) {
    function pn_moon_lon(float $jd): float {
        $T=($jd-2451545.0)/36525.0;
        $Lp=218.3164477+481267.88123421*$T; $D=297.8501921+445267.1114034*$T;
        $M=357.5291092+35999.0502909*$T; $Mp=134.9633964+477198.8675055*$T;
        $F=93.2720950+483202.0175233*$T;
        $Dr=deg2rad($D);$Mr=deg2rad($M);$Mpr=deg2rad($Mp);$Fr=deg2rad($F);
        $lon=$Lp+6.288774*sin($Mpr)+1.274027*sin(2*$Dr-$Mpr)+0.658314*sin(2*$Dr)
            +0.213618*sin(2*$Mpr)-0.185116*sin($Mr)-0.114332*sin(2*$Fr)
            +0.058793*sin(2*$Dr-2*$Mpr)+0.057066*sin(2*$Dr-$Mr-$Mpr)
            +0.053322*sin(2*$Dr+$Mpr)+0.045758*sin(2*$Dr-$Mr)
            -0.040923*sin($Mr-$Mpr)-0.034720*sin($Dr)-0.030383*sin($Mr+$Mpr)
            +0.015327*sin(2*$Dr-2*$Fr)+0.010980*sin($Dr-$Mpr);
        return fmod($lon+360,360);
    }
}
if (!function_exists('pn_ayanamsa')) { function pn_ayanamsa(float $jd): float { $T=($jd-2451545.0)/36525.0; return 23.85+0.01397*($T*100); } }

if (!function_exists('pn_tithi_name')) {
    function pn_tithi_name(int $n): string {
        $names=[1=>'Pratipada',2=>'Dwitiya',3=>'Tritiya',4=>'Chaturthi',5=>'Panchami',6=>'Shashthi',
            7=>'Saptami',8=>'Ashtami',9=>'Navami',10=>'Dashami',11=>'Ekadashi',12=>'Dwadashi',
            13=>'Trayodashi',14=>'Chaturdashi',15=>'Purnima',16=>'Pratipada',17=>'Dwitiya',18=>'Tritiya',
            19=>'Chaturthi',20=>'Panchami',21=>'Shashthi',22=>'Saptami',23=>'Ashtami',24=>'Navami',
            25=>'Dashami',26=>'Ekadashi',27=>'Dwadashi',28=>'Trayodashi',29=>'Chaturdashi',30=>'Amavasya'];
        return $names[$n] ?? '—';
    }
}
if (!function_exists('pn_nakshatra')) {
    function pn_nakshatra(int $i): string {
        $n=['Ashwini','Bharani','Krittika','Rohini','Mrigashira','Ardra','Punarvasu','Pushya','Ashlesha',
            'Magha','Purva Phalguni','Uttara Phalguni','Hasta','Chitra','Swati','Vishakha','Anuradha',
            'Jyeshtha','Mula','Purva Ashadha','Uttara Ashadha','Shravana','Dhanishta','Shatabhisha',
            'Purva Bhadrapada','Uttara Bhadrapada','Revati'];
        return $n[$i%27] ?? '—';
    }
}
if (!function_exists('pn_yoga')) {
    function pn_yoga(int $i): string {
        $y=['Vishkambha','Priti','Ayushman','Saubhagya','Shobhana','Atiganda','Sukarman','Dhriti','Shula',
            'Ganda','Vriddhi','Dhruva','Vyaghata','Harshana','Vajra','Siddhi','Vyatipata','Variyana',
            'Parigha','Shiva','Siddha','Sadhya','Shubha','Shukla','Brahma','Indra','Vaidhriti'];
        return $y[$i%27] ?? '—';
    }
}
if (!function_exists('pn_karana')) {
    function pn_karana(int $i): string {
        $k=['Bava','Balava','Kaulava','Taitila','Gara','Vanija','Vishti','Shakuni','Chatushpada','Naga','Kimstughna'];
        return $k[$i%11] ?? '—';
    }
}
if (!function_exists('pn_sun_events')) {
    function pn_sun_events(string $date): array {
        $lat=22.5726; $lng=88.3639;
        $tz=new DateTimeZone('Asia/Kolkata'); $utc=new DateTimeZone('UTC');
        $noon=new DateTime($date.' 12:00:00',$tz); $noon->setTimezone($utc);
        $jd=pn_jd($noon->getTimestamp()); $T=($jd-2451545.0)/36525.0;
        $L0=fmod(280.46646+36000.76983*$T,360); $M=357.52911+35999.05029*$T;
        $e=0.016708634-0.000042037*$T;
        $eps=23+(26+(21.448-$T*(46.815+$T*(0.00059-$T*0.001813)))/60)/60;
        $Mr=deg2rad($M);
        $C=(1.914602-0.004817*$T)*sin($Mr)+(0.019993-0.000101*$T)*sin(2*$Mr)+0.000289*sin(3*$Mr);
        $sunTrueLon=$L0+$C; $omega=125.04-1934.136*$T;
        $lambda=$sunTrueLon-0.00569-0.00478*sin(deg2rad($omega));
        $y=pow(tan(deg2rad($eps/2)),2);
        $Eq=$y*sin(2*deg2rad($L0))-2*$e*sin($Mr)+4*$e*$y*sin($Mr)*cos(2*deg2rad($L0))
            -0.5*$y*$y*sin(4*deg2rad($L0))-1.25*$e*$e*sin(2*$Mr);
        $eqTime=rad2deg($Eq)*4;
        $decl=rad2deg(asin(sin(deg2rad($eps))*sin(deg2rad($lambda))));
        $cosH=cos(deg2rad(90.833))/(cos(deg2rad($lat))*cos(deg2rad($decl)))
            -tan(deg2rad($lat))*tan(deg2rad($decl));
        if ($cosH>1||$cosH<-1) return ['sunrise'=>'06:00','sunset'=>'18:00','solar_noon'=>'12:00'];
        $H=rad2deg(acos($cosH));
        $solarNoonMin=720-4*$lng-$eqTime;
        $sunriseMin=$solarNoonMin-4*$H; $sunsetMin=$solarNoonMin+4*$H;
        $tzOffsetMin=(new DateTime($date.' 12:00',$tz))->getOffset()/60;
        $fmt=function(float $min) use ($tzOffsetMin): string {
            $local=($min+$tzOffsetMin+1440)%1440;
            $h=floor($local/60); $m=round($local-$h*60);
            return sprintf('%02d:%02d',$h,$m);
        };
        return ['sunrise'=>$fmt($sunriseMin),'sunset'=>$fmt($sunsetMin),'solar_noon'=>$fmt($solarNoonMin)];
    }
}
if (!function_exists('pn_moon_events')) {
    function pn_moon_events(string $date, string $sunrise): array {
        $tz=new DateTimeZone('Asia/Kolkata');
        $dt=new DateTime($date.' 12:00',$tz); $jd=pn_jd($dt->getTimestamp());
        $elong=fmod(pn_moon_lon($jd)-pn_sun_lon($jd)+360,360);
        $riseOffset=($elong/12)*50; $setOffset=$riseOffset+720;
        $parseMin=function(string $hhmm): int {
            if (!preg_match('/^(\d{1,2}):(\d{2})/',$hhmm,$m)) return 360;
            return (int)$m[1]*60+(int)$m[2];
        };
        $fmt=function(int $m): string { $m=($m+1440)%1440; return sprintf('%02d:%02d',floor($m/60),$m%60); };
        $srMin=$parseMin($sunrise);
        return ['moonrise'=>$fmt((int)round($srMin+$riseOffset)),'moonset'=>$fmt((int)round($srMin+$setOffset))];
    }
}
if (!function_exists('pn_abhijit')) {
    function pn_abhijit(string $solarNoon): string {
        if (!preg_match('/^(\d{1,2}):(\d{2})/',$solarNoon,$m)) return '11:48 – 12:36';
        $noonMin=(int)$m[1]*60+(int)$m[2];
        $fmt=function(int $x): string { $x=($x+1440)%1440; return sprintf('%02d:%02d',floor($x/60),$x%60); };
        return $fmt($noonMin-24).' – '.$fmt($noonMin+24);
    }
}
if (!function_exists('pn_rahu')) {
    function pn_rahu(string $date, string $sunrise, string $sunset): string {
        $parseMin=function(string $hhmm): int {
            if (!preg_match('/^(\d{1,2}):(\d{2})/',$hhmm,$m)) return 0;
            return (int)$m[1]*60+(int)$m[2];
        };
        $fmt=function(int $x): string { $x=($x+1440)%1440; return sprintf('%02d:%02d',floor($x/60),$x%60); };
        $sr=$parseMin($sunrise); $ss=$parseMin($sunset); if ($ss<$sr) $ss+=1440;
        $seg=($ss-$sr)/8;
        $wd=(int)date('w',strtotime($date));
        $map=[0=>8,1=>2,2=>7,3=>5,4=>6,5=>4,6=>3];
        $i=$map[$wd]??1; $start=$sr+($i-1)*$seg;
        return $fmt((int)round($start)).' – '.$fmt((int)round($start+$seg));
    }
}
if (!function_exists('pn_compute')) {
    function pn_compute(string $date): array {
        $tz=new DateTimeZone('Asia/Kolkata');
        $dt=new DateTime($date.' 06:00:00',$tz); $jd=pn_jd($dt->getTimestamp());
        $ayan=pn_ayanamsa($jd);
        $sunLon=pn_sun_lon($jd); $moonLon=pn_moon_lon($jd);
        $sunSid=fmod($sunLon-$ayan+360,360); $moonSid=fmod($moonLon-$ayan+360,360);
        $elong=fmod($moonLon-$sunLon+360,360);
        $tithiN=(int)floor($elong/12)+1;
        $paksha=$tithiN<=15?'Shukla':'Krishna';
        $nakshatraIdx=(int)floor($moonSid/(360/27));
        $yogaIdx=(int)floor(fmod($sunSid+$moonSid,360)/(360/27));
        $karanaIdx=(int)floor($elong/6);
        $hinduMonths=['Chaitra','Vaishakha','Jyeshtha','Ashadha','Shravana','Bhadrapada',
            'Ashwin','Kartik','Margashirsha','Pausha','Magha','Phalguna'];
        $hMonthIdx=(int)floor(fmod($sunSid+360-23.85,360)/30);
        $ss=pn_sun_events($date); $mrms=pn_moon_events($date,$ss['sunrise']);
        return [
            'date'=>$date,'date_long'=>date('l, d M Y',strtotime($date)),
            'weekday'=>date('l',strtotime($date)),
            'location'=>'Kolkata, West Bengal, India','timezone'=>'Asia/Kolkata',
            'tithi'=>pn_tithi_name($tithiN),'tithi_number'=>$tithiN,'paksha'=>$paksha,
            'nakshatra'=>pn_nakshatra($nakshatraIdx),'yoga'=>pn_yoga($yogaIdx),
            'karana'=>pn_karana($karanaIdx%11),'hindu_month'=>$hinduMonths[$hMonthIdx%12],
            'sunrise'=>$ss['sunrise'],'sunset'=>$ss['sunset'],
            'moonrise'=>$mrms['moonrise'],'moonset'=>$mrms['moonset'],
            'abhijit'=>pn_abhijit($ss['solar_noon']),
            'rahu_kaal'=>pn_rahu($date,$ss['sunrise'],$ss['sunset']),
            'computed_at'=>date('c'),
        ];
    }
}
if (!function_exists('pn_fallback')) {
    function pn_fallback(string $date): array {
        return ['date'=>$date,'date_long'=>date('l, d M Y',strtotime($date)),
            'weekday'=>date('l',strtotime($date)),'location'=>'Kolkata, West Bengal, India',
            'timezone'=>'Asia/Kolkata','tithi'=>'—','tithi_number'=>0,'paksha'=>'—',
            'nakshatra'=>'—','yoga'=>'—','karana'=>'—','hindu_month'=>'—',
            'sunrise'=>'—','sunset'=>'—','moonrise'=>'—','moonset'=>'—',
            'abhijit'=>'—','rahu_kaal'=>'—','computed_at'=>date('c'),'unavailable'=>true];
    }
}
if (!function_exists('pn_get_today')) {
    function pn_get_today(): array {
        $today=date('Y-m-d');
        $cacheFile=PANCHANG_CACHE_DIR.'/'.$today.'.json';
        if (is_file($cacheFile)) {
            $raw=@file_get_contents($cacheFile);
            if ($raw!==false) { $j=json_decode($raw,true); if (is_array($j)&&!empty($j['date'])) return $j; }
        }
        try { $data=pn_compute($today); }
        catch (Throwable $e) { error_log('[panchang] '.$e->getMessage()); $data=pn_fallback($today); }
        @file_put_contents($cacheFile, json_encode($data, JSON_UNESCAPED_UNICODE));
        @chmod($cacheFile, 0644);
        return $data;
    }
}
if (!function_exists('pn_festivals')) {
    function pn_festivals(PDO $pdo, int $limit = 6): array {
        try {
            $stmt=$pdo->prepare(
                "SELECT id, name, slug, festival_date, short_desc, image_url
                 FROM festivals
                 WHERE is_active = 1 AND festival_date >= CURDATE()
                 ORDER BY festival_date ASC LIMIT ".(int)$limit
            );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) { error_log('[panchang:festivals] '.$e->getMessage()); return []; }
    }
}

/* ==================================================================
 |  REMOTE FESTIVAL IMAGE URLs — direct, no local storage
 |  Sources: Unsplash CDN + Wikimedia Commons (both allow hotlinking)
 * ================================================================== */
if (!function_exists('pn_festival_image_url')) {
    function pn_festival_image_url(string $name, string $slug = ''): string {
        /* Primary URLs — Unsplash CDN (stable, hotlink-friendly) */
        static $primary = [
            'maha shivratri'      => 'https://images.unsplash.com/photo-1604608672516-f1b9b1d1c0b9?auto=format&fit=crop&w=900&q=80',
            'maha-shivratri'      => 'https://images.unsplash.com/photo-1604608672516-f1b9b1d1c0b9?auto=format&fit=crop&w=900&q=80',
            'shivratri'           => 'https://images.unsplash.com/photo-1604608672516-f1b9b1d1c0b9?auto=format&fit=crop&w=900&q=80',
            'raksha bandhan'      => 'https://images.unsplash.com/photo-1609766857041-ed402ea8069a?auto=format&fit=crop&w=900&q=80',
            'raksha-bandhan'      => 'https://images.unsplash.com/photo-1609766857041-ed402ea8069a?auto=format&fit=crop&w=900&q=80',
            'rakhi'               => 'https://images.unsplash.com/photo-1609766857041-ed402ea8069a?auto=format&fit=crop&w=900&q=80',
            'janmashtami'         => 'https://images.unsplash.com/photo-1610701596007-11502861dcfa?auto=format&fit=crop&w=900&q=80',
            'krishna janmashtami' => 'https://images.unsplash.com/photo-1610701596007-11502861dcfa?auto=format&fit=crop&w=900&q=80',
            'navratri'            => 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=900&q=80',
            'navaratri'           => 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=900&q=80',
            'durga puja'          => 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=900&q=80',
            'dussehra'            => 'https://images.unsplash.com/photo-1595246140625-573b715d11dc?auto=format&fit=crop&w=900&q=80',
            'dasara'              => 'https://images.unsplash.com/photo-1595246140625-573b715d11dc?auto=format&fit=crop&w=900&q=80',
            'vijayadashami'       => 'https://images.unsplash.com/photo-1595246140625-573b715d11dc?auto=format&fit=crop&w=900&q=80',
            'diwali'              => 'https://images.unsplash.com/photo-1604608672516-f1b9b1d1c0b9?auto=format&fit=crop&w=900&q=80',
            'deepavali'           => 'https://images.unsplash.com/photo-1604608672516-f1b9b1d1c0b9?auto=format&fit=crop&w=900&q=80',
            'holi'                => 'https://images.unsplash.com/photo-1610701596007-11502861dcfa?auto=format&fit=crop&w=900&q=80',
            'ganesh chaturthi'    => 'https://images.unsplash.com/photo-1567591370788-90e0e6c6c2ba?auto=format&fit=crop&w=900&q=80',
            'ganesh-chaturthi'    => 'https://images.unsplash.com/photo-1567591370788-90e0e6c6c2ba?auto=format&fit=crop&w=900&q=80',
        ];

        $keys = [];
        if ($slug !== '') $keys[] = strtolower(trim($slug));
        $keys[] = strtolower(trim($name));

        foreach ($keys as $key) {
            if (isset($primary[$key])) return $primary[$key];
        }
        return '';
    }
}

if (!function_exists('pn_festival_fallback_url')) {
    function pn_festival_fallback_url(string $name, string $slug = ''): string {
        /* Fallback URLs — Wikimedia Commons (permanent, hotlink-safe) */
        static $fallback = [
            'diwali'         => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f2/Diya_lamp.jpg/800px-Diya_lamp.jpg',
            'deepavali'      => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f2/Diya_lamp.jpg/800px-Diya_lamp.jpg',
            'navratri'       => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/Durga-Puja.jpg/800px-Durga-Puja.jpg',
            'navaratri'      => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/Durga-Puja.jpg/800px-Durga-Puja.jpg',
            'dussehra'       => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2b/Dussehra_at_Mysore.jpg/800px-Dussehra_at_Mysore.jpg',
            'maha shivratri' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0e/Shiv_Lingam.jpg/800px-Shiv_Lingam.jpg',
            'janmashtami'    => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/6d/Krishna_statue.jpg/800px-Krishna_statue.jpg',
        ];

        $keys = [];
        if ($slug !== '') $keys[] = strtolower(trim($slug));
        $keys[] = strtolower(trim($name));

        foreach ($keys as $key) {
            if (isset($fallback[$key])) return $fallback[$key];
        }
        return '';
    }
}

/* Gradient + emoji fallback (last resort) */
if (!function_exists('pn_festival_theme')) {
    function pn_festival_theme(string $slug, string $name = ''): array {
        $key = strtolower($slug ?: $name);
        $map = [
            'maha-shivratri'=>['emoji'=>'🕉️','theme'=>'shiva'],
            'shivratri'     =>['emoji'=>'🕉️','theme'=>'shiva'],
            'raksha-bandhan'=>['emoji'=>'🪢','theme'=>'rakhi'],
            'rakhi'         =>['emoji'=>'🪢','theme'=>'rakhi'],
            'janmashtami'   =>['emoji'=>'🦚','theme'=>'krishna'],
            'krishna'       =>['emoji'=>'🦚','theme'=>'krishna'],
            'navratri'      =>['emoji'=>'🌺','theme'=>'durga'],
            'navaratri'     =>['emoji'=>'🌺','theme'=>'durga'],
            'durga'         =>['emoji'=>'🌺','theme'=>'durga'],
            'dussehra'      =>['emoji'=>'🏹','theme'=>'ram'],
            'dasara'        =>['emoji'=>'🏹','theme'=>'ram'],
            'diwali'        =>['emoji'=>'🪔','theme'=>'diwali'],
            'deepavali'     =>['emoji'=>'🪔','theme'=>'diwali'],
            'holi'          =>['emoji'=>'🎨','theme'=>'holi'],
            'ganesh'        =>['emoji'=>'🐘','theme'=>'ganesh'],
            'ram-navami'    =>['emoji'=>'🏹','theme'=>'ram'],
        ];
        foreach ($map as $k => $v) {
            if (strpos($key, $k) !== false) return $v;
        }
        return ['emoji'=>'🎉','theme'=>'default'];
    }
}

/* Rashifal + FAQ unchanged */
if (!function_exists('pn_rashifal')) {
    function pn_rashifal(): array {
        return [
            ['symbol'=>'♈','name'=>'Aries','short'=>'Mesh','lucky_color'=>'Red','lucky_number'=>9,'text'=>'Today brings positive energy for Aries. Focus on devotion, patience, and dharmic actions. A good day for spiritual practices and temple visits.'],
            ['symbol'=>'♉','name'=>'Taurus','short'=>'Vrishabh','lucky_color'=>'Pink','lucky_number'=>6,'text'=>'Today brings positive energy for Taurus. Focus on devotion, patience, and dharmic actions. A good day for spiritual practices and temple visits.'],
            ['symbol'=>'♊','name'=>'Gemini','short'=>'Mithun','lucky_color'=>'Green','lucky_number'=>5,'text'=>'Today brings positive energy for Gemini. Focus on devotion, patience, and dharmic actions. A good day for spiritual practices and temple visits.'],
            ['symbol'=>'♋','name'=>'Cancer','short'=>'Kark','lucky_color'=>'White','lucky_number'=>2,'text'=>'Today brings positive energy for Cancer. Focus on devotion, patience, and dharmic actions. A good day for spiritual practices and temple visits.'],
            ['symbol'=>'♌','name'=>'Leo','short'=>'Simha','lucky_color'=>'Gold','lucky_number'=>1,'text'=>'Today brings positive energy for Leo. Focus on devotion, patience, and dharmic actions. A good day for spiritual practices and temple visits.'],
            ['symbol'=>'♍','name'=>'Virgo','short'=>'Kanya','lucky_color'=>'Green','lucky_number'=>5,'text'=>'Today brings positive energy for Virgo. Focus on devotion, patience, and dharmic actions. A good day for spiritual practices and temple visits.'],
            ['symbol'=>'♎','name'=>'Libra','short'=>'Tula','lucky_color'=>'Blue','lucky_number'=>6,'text'=>'Today brings positive energy for Libra. Focus on devotion, patience, and dharmic actions. A good day for spiritual practices and temple visits.'],
            ['symbol'=>'♏','name'=>'Scorpio','short'=>'Vrishchik','lucky_color'=>'Maroon','lucky_number'=>9,'text'=>'Today brings positive energy for Scorpio. Focus on devotion, patience, and dharmic actions. A good day for spiritual practices and temple visits.'],
            ['symbol'=>'♐','name'=>'Sagittarius','short'=>'Dhanu','lucky_color'=>'Yellow','lucky_number'=>3,'text'=>'Today brings positive energy for Sagittarius. Focus on devotion, patience, and dharmic actions. A good day for spiritual practices and temple visits.'],
            ['symbol'=>'♑','name'=>'Capricorn','short'=>'Makar','lucky_color'=>'Black','lucky_number'=>8,'text'=>'Today brings positive energy for Capricorn. Focus on devotion, patience, and dharmic actions. A good day for spiritual practices and temple visits.'],
            ['symbol'=>'♒','name'=>'Aquarius','short'=>'Kumbh','lucky_color'=>'Blue','lucky_number'=>4,'text'=>'Today brings positive energy for Aquarius. Focus on devotion, patience, and dharmic actions. A good day for spiritual practices and temple visits.'],
            ['symbol'=>'♓','name'=>'Pisces','short'=>'Meen','lucky_color'=>'Sea Green','lucky_number'=>7,'text'=>'Today brings positive energy for Pisces. Focus on devotion, patience, and dharmic actions. A good day for spiritual practices and temple visits.'],
        ];
    }
}
if (!function_exists('pn_faqs')) {
    function pn_faqs(): array {
        return [
            ['q'=>'What is Panchang?','a'=>'Panchang is the traditional Hindu almanac that records five key elements of the day — Tithi (lunar day), Nakshatra (constellation), Yoga, Karana and Vaar (weekday). It is used to determine auspicious timings for rituals, travel, business and daily activities.'],
            ['q'=>'Why does the Panchang change every day?','a'=>'The positions of the Sun and Moon change continuously. Each day, the Moon moves about 13° through the zodiac, so the Tithi, Nakshatra and Yoga shift daily. This is why the Panchang is calculated fresh for every day.'],
            ['q'=>'Which location and timezone is this Panchang based on?','a'=>'This Panchang is calculated for Kolkata, West Bengal, India using the Asia/Kolkata timezone. Sunrise, sunset, moonrise and muhurat timings are all local to this location.'],
            ['q'=>'What is Abhijit Muhurat and Rahu Kaal?','a'=>'Abhijit Muhurat is the most auspicious 48-minute window around solar noon, ideal for starting important work. Rahu Kaal is an inauspicious period each day (roughly 90 minutes) that is best avoided for new beginnings.'],
            ['q'=>'How accurate are the timings shown here?','a'=>'Timings are computed using standard astronomical formulas with Lahiri ayanamsa, and are accurate to within a few minutes. For critical muhurta decisions, always cross-verify with a qualified Jyotish or a trusted printed Panchang.'],
        ];
    }
}

/* ==================================================================
 |  Fetch data
 * ================================================================== */
$panchang  = pn_get_today();
$festivals = pn_festivals($pdo, 6);
$rashifal  = pn_rashifal();
$faqs      = pn_faqs();

$pageTitle = 'Panchang — Today\'s Hindu Calendar';
require BASE_PATH . '/includes/header.php';
?>

<!-- ═══════════════ HERO (UNCHANGED) ═══════════════ -->
<section class="page-hero">
  <div class="page-hero-mandala"></div>
  <div class="container">
    <nav class="breadcrumb">
      <a href="<?= url('index.php') ?>">Home</a>
      <span class="sep">›</span>
      <span class="current">Panchang</span>
    </nav>
    <span class="eyebrow">Daily Hindu Calendar</span>
    <h1>Today's Panchang</h1>
    <p>Stay connected with cosmic energies and plan your day better.</p>
  </div>
</section>

<style>
/* [Same CSS as before — I'm keeping it unchanged] */
.pn-section{padding:56px 0 32px;background:#FAF6EF}
.pn-section--alt{padding:32px 0 48px;background:#FAF6EF}
.pn-container{max-width:1200px;margin:0 auto;padding:0 22px}
.pn-head{text-align:center;max-width:760px;margin:0 auto 36px;padding:0 22px}
.pn-head__eyebrow{display:inline-block;font-size:11px;font-weight:700;letter-spacing:2px;
  text-transform:uppercase;color:#B76146;margin-bottom:12px}
.pn-head h2{font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:32px;line-height:1.2;color:#351B2F;margin:0 0 12px;font-weight:600}
.pn-head__date{display:inline-block;margin-top:4px;font-size:13.5px;color:#6B7280;font-weight:600}
.pn-head__line{width:70px;height:2px;background:linear-gradient(90deg,transparent,#D4AF37,transparent);margin:16px auto 0}

.pn-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:32px}
@media(max-width:900px){.pn-grid{grid-template-columns:repeat(2,1fr);gap:16px}}
@media(max-width:560px){.pn-grid{grid-template-columns:1fr;gap:12px}}
.pn-card{background:#fff;border:1px solid #EFE6D9;border-radius:14px;padding:20px 22px;
  box-shadow:0 1px 3px rgba(53,27,47,.05);display:flex;flex-direction:column;gap:8px;
  transition:transform .2s,box-shadow .2s,border-color .2s}
.pn-card:hover{transform:translateY(-3px);box-shadow:0 10px 24px rgba(53,27,47,.09);border-color:rgba(183,97,70,.28)}
.pn-card__label{display:flex;align-items:center;gap:8px;font-size:11px;font-weight:700;
  letter-spacing:1.4px;text-transform:uppercase;color:#B76146}
.pn-card__label i{font-size:14px}
.pn-card__value{font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:22px;line-height:1.25;color:#351B2F;font-weight:600}
.pn-card__sub{font-size:12.5px;color:#6B7280;line-height:1.5;margin-top:2px}
.pn-unavailable{max-width:680px;margin:0 auto 32px;padding:18px 22px;background:#FFF7E0;
  border:1px solid #F5D76E;border-radius:12px;color:#92700C;font-size:14px;line-height:1.6;text-align:center}

/* ══ FESTIVALS — SAME CARD DESIGN, REMOTE IMAGES ══ */
.fest-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}
@media(max-width:900px){.fest-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:560px){.fest-grid{grid-template-columns:1fr}}
.fest-card{background:#fff;border:1px solid #EFE6D9;border-radius:16px;overflow:hidden;
  box-shadow:0 1px 3px rgba(53,27,47,.05);display:flex;flex-direction:column;
  transition:transform .22s,box-shadow .22s,border-color .22s}
.fest-card:hover{transform:translateY(-4px);box-shadow:0 12px 28px rgba(53,27,47,.10);border-color:rgba(183,97,70,.28)}

.fest-card__media{position:relative;height:180px;overflow:hidden;background:#F3F4F6}
@media(max-width:560px){.fest-card__media{height:160px}}

/* Gradient + emoji fallback layer (bottom) */
.fest-card__bg{position:absolute;inset:0;display:grid;place-items:center;z-index:1}
.fest-card__emoji{font-size:68px;line-height:1;filter:drop-shadow(0 6px 16px rgba(0,0,0,.35));opacity:.95}
.fest-card__bg--shiva{background:linear-gradient(135deg,#475569 0%,#1F2937 100%)}
.fest-card__bg--rakhi{background:linear-gradient(135deg,#F59E0B 0%,#B76146 100%)}
.fest-card__bg--krishna{background:linear-gradient(135deg,#1E40AF 0%,#1E3A8A 100%)}
.fest-card__bg--durga{background:linear-gradient(135deg,#DC2626 0%,#7C2D12 100%)}
.fest-card__bg--ram{background:linear-gradient(135deg,#7C2D12 0%,#351B2F 100%)}
.fest-card__bg--diwali{background:linear-gradient(135deg,#D4AF37 0%,#B76146 100%)}
.fest-card__bg--holi{background:linear-gradient(135deg,#EC4899 0%,#8B5CF6 100%)}
.fest-card__bg--ganesh{background:linear-gradient(135deg,#F59E0B 0%,#DC2626 100%)}
.fest-card__bg--default{background:linear-gradient(135deg,#B76146 0%,#351B2F 100%)}

/* Remote image layer (covers gradient when loaded) */
.fest-card__img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;
  object-position:center;display:block;z-index:2;transition:transform .35s ease}
.fest-card:hover .fest-card__img{transform:scale(1.05)}

/* Subtle dark gradient for date badge readability */
.fest-card__media::after{content:'';position:absolute;inset:0;z-index:3;pointer-events:none;
  background:linear-gradient(180deg,rgba(53,27,47,.40) 0%,transparent 35%,transparent 65%,rgba(53,27,47,.55) 100%)}

/* Date badge */
.fest-card__date{position:absolute;top:12px;left:12px;z-index:4;padding:6px 14px;border-radius:99px;
  background:rgba(53,27,47,.92);color:#F7EBDD;font-size:11px;font-weight:700;
  letter-spacing:.4px;backdrop-filter:blur(6px);box-shadow:0 2px 8px rgba(0,0,0,.25)}

.fest-card__body{padding:18px 20px 20px;display:flex;flex-direction:column;gap:8px;flex:1;background:#fff}
.fest-card__name{font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:18px;line-height:1.3;color:#351B2F;margin:0;font-weight:600}
.fest-card__desc{color:#6B7280;font-size:13px;line-height:1.6;margin:0;flex:1;
  display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
.fest-card__cta{font-size:12.5px;font-weight:700;color:#B76146;text-decoration:none;
  letter-spacing:.3px;margin-top:4px;align-self:flex-start}
.fest-card__cta:hover{color:#9B4C34}

.rash-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
@media(max-width:900px){.rash-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:560px){.rash-grid{grid-template-columns:1fr}}
.rash-card{background:#fff;border:1px solid #EFE6D9;border-radius:14px;padding:20px 20px 18px;
  box-shadow:0 1px 3px rgba(53,27,47,.05);display:flex;flex-direction:column;gap:10px;
  transition:transform .22s,box-shadow .22s,border-color .22s}
.rash-card:hover{transform:translateY(-3px);box-shadow:0 10px 24px rgba(53,27,47,.09);border-color:rgba(183,97,70,.28)}
.rash-card__head{display:flex;align-items:center;gap:12px;padding-bottom:12px;border-bottom:1px solid #F3EEE6}
.rash-card__symbol{width:44px;height:44px;border-radius:11px;display:grid;place-items:center;
  font-size:22px;line-height:1;background:linear-gradient(135deg,#F7EBDD,#F4EAF0);color:#B76146;flex-shrink:0}
.rash-card__name{font-family:var(--font-serif,'Playfair Display',Georgia,serif);font-size:15px;
  font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:#351B2F;margin:0 0 2px;line-height:1.2}
.rash-card__sub{font-size:10.5px;letter-spacing:1.3px;text-transform:uppercase;color:#B76146;font-weight:600}
.rash-card__text{color:#4B5563;font-size:13px;line-height:1.6;margin:0;flex:1}
.rash-card__foot{display:grid;grid-template-columns:1fr 1fr;gap:12px;padding-top:12px;border-top:1px solid #F3EEE6}
.rash-card__flabel{font-size:10px;letter-spacing:1.2px;text-transform:uppercase;color:#9CA3AF;font-weight:700;margin-bottom:3px}
.rash-card__fval{font-size:14px;font-weight:700;color:#351B2F}
.rash-card__fval--num{color:#B76146;font-family:var(--font-serif,Georgia,serif);font-size:17px}

.pn-faq{padding:56px 0 80px;background:#fff}
.pn-faq__list{max-width:820px;margin:0 auto;padding:0 22px;display:flex;flex-direction:column;gap:12px}
.pn-faq__item{background:#fff;border:1px solid #EFE6D9;border-radius:14px;overflow:hidden;
  transition:border-color .2s,box-shadow .2s}
.pn-faq__item.is-open{border-color:rgba(183,97,70,.35);box-shadow:0 4px 18px rgba(53,27,47,.06)}
.pn-faq__q{display:flex;align-items:center;justify-content:space-between;gap:16px;width:100%;
  padding:18px 22px;background:transparent;border:0;cursor:pointer;text-align:left;
  font-family:inherit;font-size:15px;font-weight:600;color:#351B2F;line-height:1.4}
.pn-faq__q:hover{color:#B76146}
.pn-faq__chev{flex-shrink:0;width:20px;height:20px;display:grid;place-items:center;color:#B76146;
  transition:transform .28s}
.pn-faq__item.is-open .pn-faq__chev{transform:rotate(180deg)}
.pn-faq__chev svg{width:14px;height:14px;stroke-width:2.6}
.pn-faq__a{max-height:0;overflow:hidden;transition:max-height .32s}
.pn-faq__a-inner{padding:0 22px 20px;color:#4B5563;font-size:14px;line-height:1.75}
.pn-faq__a-inner p{margin:0}

@media(max-width:640px){
  .pn-section{padding:36px 0 24px}
  .pn-section--alt{padding:24px 0 32px}
  .pn-head h2{font-size:24px}
  .pn-head{margin-bottom:24px}
  .pn-card__value{font-size:18px}
  .pn-faq{padding:40px 0 56px}
  .pn-faq__q{padding:16px 18px;font-size:14px}
  .pn-faq__a-inner{padding:0 18px 18px;font-size:13.5px}
}
</style>

<!-- ── TODAY'S PANCHANG (unchanged) ─────────────── -->
<section class="pn-section">
  <div class="pn-container">
    <div class="pn-head">
      <span class="pn-head__eyebrow">Daily Hindu Calendar</span>
      <h2>Today's Panchang</h2>
      <span class="pn-head__date"><?= e($panchang['date_long']) ?> · <?= e($panchang['location']) ?></span>
      <div class="pn-head__line"></div>
    </div>

    <?php if (!empty($panchang['unavailable'])): ?>
      <div class="pn-unavailable">
        <b>Today's Panchang information is temporarily unavailable.</b><br>
        Please try again shortly.
      </div>
    <?php endif; ?>

    <div class="pn-grid">
      <div class="pn-card"><div class="pn-card__label"><i class="bi bi-moon-stars"></i> Tithi</div>
        <div class="pn-card__value"><?= e($panchang['paksha']) ?> <?= e($panchang['tithi']) ?></div>
        <div class="pn-card__sub">Paksha: <?= e($panchang['paksha']) ?> · Tithi No. <?= (int)$panchang['tithi_number'] ?></div></div>
      <div class="pn-card"><div class="pn-card__label"><i class="bi bi-stars"></i> Nakshatra</div>
        <div class="pn-card__value"><?= e($panchang['nakshatra']) ?></div>
        <div class="pn-card__sub">Lunar constellation of the day</div></div>
      <div class="pn-card"><div class="pn-card__label"><i class="bi bi-calendar-event"></i> Hindu Month</div>
        <div class="pn-card__value"><?= e($panchang['hindu_month']) ?></div>
        <div class="pn-card__sub">Vedic lunar month</div></div>
      <div class="pn-card"><div class="pn-card__label"><i class="bi bi-activity"></i> Yoga</div>
        <div class="pn-card__value"><?= e($panchang['yoga']) ?></div>
        <div class="pn-card__sub">Nitya yoga of the day</div></div>
      <div class="pn-card"><div class="pn-card__label"><i class="bi bi-hourglass-split"></i> Karana</div>
        <div class="pn-card__value"><?= e($panchang['karana']) ?></div>
        <div class="pn-card__sub">Half-tithi segment</div></div>
      <div class="pn-card"><div class="pn-card__label"><i class="bi bi-sun"></i> Sunrise</div>
        <div class="pn-card__value"><?= e($panchang['sunrise']) ?></div>
        <div class="pn-card__sub">Sunset: <?= e($panchang['sunset']) ?></div></div>
      <div class="pn-card"><div class="pn-card__label"><i class="bi bi-moon"></i> Moonrise</div>
        <div class="pn-card__value"><?= e($panchang['moonrise']) ?></div>
        <div class="pn-card__sub">Moonset: <?= e($panchang['moonset']) ?></div></div>
      <div class="pn-card"><div class="pn-card__label"><i class="bi bi-brightness-high"></i> Abhijit Muhurat</div>
        <div class="pn-card__value"><?= e($panchang['abhijit']) ?></div>
        <div class="pn-card__sub">Most auspicious window</div></div>
      <div class="pn-card"><div class="pn-card__label"><i class="bi bi-shield-exclamation"></i> Rahu Kaal</div>
        <div class="pn-card__value"><?= e($panchang['rahu_kaal']) ?></div>
        <div class="pn-card__sub">Avoid new beginnings</div></div>
    </div>
  </div>
</section>

<!-- ── UPCOMING FESTIVALS (remote images) ──────── -->
<?php if (!empty($festivals)): ?>
<section class="pn-section--alt">
  <div class="pn-container">
    <div class="pn-head">
      <span class="pn-head__eyebrow">Tithi &amp; Muhurat</span>
      <h2>Upcoming Festivals</h2>
      <div class="pn-head__line"></div>
    </div>

    <div class="fest-grid">
      <?php foreach ($festivals as $f): ?>
        <?php
        /* --- Primary remote URL (Unsplash) --- */
        $primaryUrl  = pn_festival_image_url((string)$f['name'], (string)$f['slug']);

        /* --- Fallback remote URL (Wikimedia) --- */
        $fallbackUrl = pn_festival_fallback_url((string)$f['name'], (string)$f['slug']);

        /* --- If both empty, try DB image_url --- */
        if ($primaryUrl === '' && $fallbackUrl === '') {
            $dbImg = trim((string)($f['image_url'] ?? ''));
            if ($dbImg !== '' && preg_match('#^https?://#i', $dbImg)) {
                $primaryUrl = $dbImg;
            }
        }

        /* --- Theme + emoji (final visual fallback) --- */
        $theme = pn_festival_theme((string)$f['slug'], (string)$f['name']);
        $fDate = strtotime($f['festival_date']);
        ?>
        <article class="fest-card">

          <div class="fest-card__media">

            <!-- Gradient + emoji (always present, sits behind image) -->
            <div class="fest-card__bg fest-card__bg--<?= e($theme['theme']) ?>" aria-hidden="true">
              <span class="fest-card__emoji"><?= $theme['emoji'] ?></span>
            </div>

            <!-- Remote image (covers gradient when loaded) -->
            <?php if ($primaryUrl !== ''): ?>
              <img class="fest-card__img"
                   src="<?= e($primaryUrl) ?>"
                   alt="<?= e($f['name']) ?>"
                   loading="lazy"
                   referrerpolicy="no-referrer"
                   onerror="if(this.dataset.fb){this.style.display='none';}else{this.dataset.fb=1;this.src='<?= e($fallbackUrl) ?>';}">
            <?php endif; ?>

            <span class="fest-card__date">📅 <?= date('d M Y', $fDate) ?></span>
          </div>

          <div class="fest-card__body">
            <h3 class="fest-card__name"><?= e($f['name']) ?></h3>
            <p class="fest-card__desc"><?= e($f['short_desc']) ?></p>
            <a href="<?= url('festival.php?slug=' . urlencode($f['slug'])) ?>" class="fest-card__cta">
              Explore →
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ── TODAY'S RASHIFAL (unchanged) ─────────────── -->
<section class="pn-section--alt">
  <div class="pn-container">
    <div class="pn-head">
      <span class="pn-head__eyebrow">Vedic Astrology</span>
      <h2>Today's Rashifal</h2>
      <span class="pn-head__date"><?= e($panchang['date_long']) ?></span>
      <div class="pn-head__line"></div>
    </div>
    <div class="rash-grid">
      <?php foreach ($rashifal as $r): ?>
        <article class="rash-card">
          <div class="rash-card__head">
            <div class="rash-card__symbol"><?= e($r['symbol']) ?></div>
            <div>
              <h3 class="rash-card__name"><?= e($r['name']) ?></h3>
              <div class="rash-card__sub"><?= e($r['short']) ?></div>
            </div>
          </div>
          <p class="rash-card__text"><?= e($r['text']) ?></p>
          <div class="rash-card__foot">
            <div><div class="rash-card__flabel">Lucky Color</div>
              <div class="rash-card__fval"><?= e($r['lucky_color']) ?></div></div>
            <div><div class="rash-card__flabel">Lucky Number</div>
              <div class="rash-card__fval rash-card__fval--num"><?= (int)$r['lucky_number'] ?></div></div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── FAQ (unchanged) ─────────────────────────── -->
<section class="pn-faq">
  <div class="pn-head">
    <span class="pn-head__eyebrow">Need Help?</span>
    <h2>Frequently Asked Questions</h2>
    <div class="pn-head__line"></div>
  </div>
  <div class="pn-faq__list">
    <?php foreach ($faqs as $f): ?>
      <div class="pn-faq__item">
        <button class="pn-faq__q" type="button" aria-expanded="false">
          <span><?= e($f['q']) ?></span>
          <span class="pn-faq__chev" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-linecap="round" stroke-linejoin="round">
              <path d="m6 9 6 6 6-6"/>
            </svg>
          </span>
        </button>
        <div class="pn-faq__a">
          <div class="pn-faq__a-inner"><p><?= e($f['a']) ?></p></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<script>
(function () {
  'use strict';
  var items = document.querySelectorAll('.pn-faq__item');
  if (!items.length) return;
  items.forEach(function (item) {
    var btn = item.querySelector('.pn-faq__q');
    var body = item.querySelector('.pn-faq__a');
    if (!btn || !body) return;
    btn.addEventListener('click', function () {
      var isOpen = item.classList.contains('is-open');
      items.forEach(function (other) {
        other.classList.remove('is-open');
        var ob = other.querySelector('.pn-faq__a');
        var obtn = other.querySelector('.pn-faq__q');
        if (ob) ob.style.maxHeight = null;
        if (obtn) obtn.setAttribute('aria-expanded', 'false');
      });
      if (!isOpen) {
        item.classList.add('is-open');
        body.style.maxHeight = body.scrollHeight + 'px';
        btn.setAttribute('aria-expanded', 'true');
      }
    });
  });
  window.addEventListener('resize', function () {
    items.forEach(function (item) {
      if (item.classList.contains('is-open')) {
        var body = item.querySelector('.pn-faq__a');
        if (body) body.style.maxHeight = body.scrollHeight + 'px';
      }
    });
  });
})();
</script>

<?php require BASE_PATH . '/includes/footer.php'; ?>