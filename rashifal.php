<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Rashifal — Daily Horoscope';
require BASE_PATH . '/includes/header.php';
?>

<!-- ═══════════════ HERO (UNCHANGED) ═══════════════ -->
<section class="page-hero">
  <div class="page-hero-mandala"></div>
  <div class="container">
    <nav class="breadcrumb">
      <a href="<?= url('index.php') ?>">Home</a>
      <span class="sep">›</span>
      <span class="current">Rashifal</span>
    </nav>
    <span class="eyebrow">Vedic Astrology</span>
    <h1>Today's Rashifal</h1>
    <p>Daily horoscope for all 12 zodiac signs.</p>
  </div>
</section>

<!-- ═══════════════ RASHIFAL CONTENT (NEW) ═══════════════ -->

<style>
/* ============================================================
   RASHIFAL PAGE — scoped styles (rash-* prefix, no conflicts)
   ============================================================ */

/* ── Daily Horoscope intro ─────────────────────────── */
.rash-intro{
  padding:56px 0 40px;
  background:#FAF6EF;
  text-align:center;
}
.rash-intro__inner{
  max-width:780px;
  margin:0 auto;
  padding:0 22px;
}
.rash-intro__eyebrow{
  display:inline-block;
  font-size:11px;
  font-weight:700;
  letter-spacing:2px;
  text-transform:uppercase;
  color:#B76146;
  margin-bottom:12px;
}
.rash-intro h2{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:34px;
  line-height:1.2;
  color:#351B2F;
  margin:0 0 14px;
  font-weight:600;
}
.rash-intro__gold-line{
  width:70px;
  height:2px;
  background:linear-gradient(90deg,transparent,#D4AF37,transparent);
  margin:0 auto 22px;
}
.rash-intro p{
  color:#4B5563;
  font-size:15px;
  line-height:1.75;
  margin:0;
}
@media(max-width:768px){
  .rash-intro{padding:40px 0 28px}
  .rash-intro h2{font-size:26px}
  .rash-intro p{font-size:14px}
}

/* ── Cards grid section ─────────────────────────────── */
.rash-grid-section{
  padding:40px 0 72px;
  background:#FAF6EF;
}
.rash-grid-container{
  max-width:1200px;
  margin:0 auto;
  padding:0 22px;
}
.rash-grid{
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:22px;
}
@media(max-width:1024px){
  .rash-grid{grid-template-columns:repeat(2,1fr);gap:20px}
}
@media(max-width:640px){
  .rash-grid{grid-template-columns:1fr;gap:16px}
  .rash-grid-section{padding:28px 0 48px}
}

/* ── Individual Rashi card ─────────────────────────── */
.rash-card{
  background:#fff;
  border:1px solid #EFE6D9;
  border-radius:16px;
  padding:22px 22px 20px;
  box-shadow:0 1px 3px rgba(53,27,47,.05);
  display:flex;
  flex-direction:column;
  gap:12px;
  transition:transform .22s ease,box-shadow .22s ease,border-color .22s ease;
}
.rash-card:hover{
  transform:translateY(-3px);
  box-shadow:0 10px 26px rgba(53,27,47,.10);
  border-color:rgba(183,97,70,.28);
}

/* Head: symbol + name */
.rash-card__head{
  display:flex;
  align-items:center;
  gap:14px;
  padding-bottom:14px;
  border-bottom:1px solid #F3EEE6;
}
.rash-card__symbol{
  width:46px;
  height:46px;
  border-radius:12px;
  display:grid;
  place-items:center;
  font-size:24px;
  line-height:1;
  background:linear-gradient(135deg,#F7EBDD,#F4EAF0);
  color:#B76146;
  flex-shrink:0;
}
.rash-card__name-block{min-width:0}
.rash-card__name{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:16px;
  font-weight:700;
  letter-spacing:1.2px;
  text-transform:uppercase;
  color:#351B2F;
  margin:0 0 3px;
  line-height:1.2;
}
.rash-card__sub{
  font-size:10.5px;
  letter-spacing:1.4px;
  text-transform:uppercase;
  color:#B76146;
  font-weight:600;
}

/* Body: label + paragraph */
.rash-card__label{
  font-size:10.5px;
  letter-spacing:1.3px;
  text-transform:uppercase;
  color:#9CA3AF;
  font-weight:700;
  margin:0 0 6px;
}
.rash-card__text{
  color:#4B5563;
  font-size:13.5px;
  line-height:1.65;
  margin:0;
  flex:1;
}

/* Foot: lucky color + number */
.rash-card__foot{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:14px;
  padding-top:14px;
  margin-top:4px;
  border-top:1px solid #F3EEE6;
}
.rash-card__foot-col{min-width:0}
.rash-card__foot-label{
  font-size:10px;
  letter-spacing:1.2px;
  text-transform:uppercase;
  color:#9CA3AF;
  font-weight:700;
  margin:0 0 4px;
}
.rash-card__foot-value{
  font-size:14.5px;
  font-weight:700;
  color:#351B2F;
  line-height:1.25;
}
.rash-card__foot-value--num{
  color:#B76146;
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:18px;
}

/* ── FAQ section ────────────────────────────────────── */
.rash-faq{
  padding:72px 0 80px;
  background:#fff;
}
.rash-faq__head{
  text-align:center;
  max-width:640px;
  margin:0 auto 40px;
  padding:0 22px;
}
.rash-faq__eyebrow{
  display:inline-block;
  font-size:11px;
  font-weight:700;
  letter-spacing:2px;
  text-transform:uppercase;
  color:#B76146;
  margin-bottom:12px;
}
.rash-faq h2{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:32px;
  line-height:1.2;
  color:#351B2F;
  margin:0 0 14px;
  font-weight:600;
}
.rash-faq__gold-line{
  width:70px;
  height:2px;
  background:linear-gradient(90deg,transparent,#D4AF37,transparent);
  margin:0 auto;
}
.rash-faq__list{
  max-width:820px;
  margin:0 auto;
  padding:0 22px;
  display:flex;
  flex-direction:column;
  gap:12px;
}
.rash-faq__item{
  background:#fff;
  border:1px solid #EFE6D9;
  border-radius:14px;
  overflow:hidden;
  transition:border-color .2s ease,box-shadow .2s ease;
}
.rash-faq__item.is-open{
  border-color:rgba(183,97,70,.35);
  box-shadow:0 4px 18px rgba(53,27,47,.06);
}
.rash-faq__q{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:16px;
  width:100%;
  padding:18px 22px;
  background:transparent;
  border:0;
  cursor:pointer;
  text-align:left;
  font-family:inherit;
  font-size:15px;
  font-weight:600;
  color:#351B2F;
  line-height:1.4;
  transition:color .18s ease;
}
.rash-faq__q:hover{color:#B76146}
.rash-faq__chev{
  flex-shrink:0;
  width:20px;
  height:20px;
  display:grid;
  place-items:center;
  color:#B76146;
  transition:transform .28s ease;
}
.rash-faq__item.is-open .rash-faq__chev{
  transform:rotate(180deg);
}
.rash-faq__chev svg{
  width:14px;
  height:14px;
  stroke-width:2.6;
}
.rash-faq__a{
  max-height:0;
  overflow:hidden;
  transition:max-height .32s ease;
}
.rash-faq__a-inner{
  padding:0 22px 22px;
  color:#4B5563;
  font-size:14px;
  line-height:1.75;
}
.rash-faq__a-inner p{margin:0}

@media(max-width:640px){
  .rash-faq{padding:48px 0 56px}
  .rash-faq h2{font-size:24px}
  .rash-faq__q{padding:16px 18px;font-size:14px}
  .rash-faq__a-inner{padding:0 18px 18px;font-size:13.5px}
}
</style>

<!-- ── DAILY HOROSCOPE INTRO ─────────────────────────── -->
<section class="rash-intro">
  <div class="rash-intro__inner">
    <span class="rash-intro__eyebrow">Sacred Predictions</span>
    <h2>Daily Horoscope</h2>
    <div class="rash-intro__gold-line"></div>
    <p>
      Srirudram Rashifal is your daily Vedic horoscope guide offering insights
      on love, career, health and finances. Based on Moon sign (Rashi), it
      provides lucky color, lucky number and personalized predictions to help
      you plan your day with cosmic awareness.
    </p>
  </div>
</section>

<!-- ── 12 RASHI CARDS ────────────────────────────────── -->
<section class="rash-grid-section">
  <div class="rash-grid-container">
    <div class="rash-grid">

      <!-- ♈ ARIES -->
      <article class="rash-card">
        <div class="rash-card__head">
          <div class="rash-card__symbol">♈</div>
          <div class="rash-card__name-block">
            <h3 class="rash-card__name">Aries</h3>
            <div class="rash-card__sub">Mesh</div>
          </div>
        </div>
        <div>
          <div class="rash-card__label">Daily Horoscope</div>
          <p class="rash-card__text">Today brings positive energy for Aries. Focus on devotion, patience, and dharmic actions. Lucky color: Red. A good day for spiritual practices and temple visits.</p>
        </div>
        <div class="rash-card__foot">
          <div class="rash-card__foot-col">
            <div class="rash-card__foot-label">Lucky Color</div>
            <div class="rash-card__foot-value">Red</div>
          </div>
          <div class="rash-card__foot-col">
            <div class="rash-card__foot-label">Lucky Number</div>
            <div class="rash-card__foot-value rash-card__foot-value--num">9</div>
          </div>
        </div>
      </article>

      <!-- ♉ TAURUS -->
      <article class="rash-card">
        <div class="rash-card__head">
          <div class="rash-card__symbol">♉</div>
          <div class="rash-card__name-block">
            <h3 class="rash-card__name">Taurus</h3>
            <div class="rash-card__sub">Vrishabh</div>
          </div>
        </div>
        <div>
          <div class="rash-card__label">Daily Horoscope</div>
          <p class="rash-card__text">Today brings positive energy for Taurus. Focus on devotion, patience, and dharmic actions. Lucky color: Pink. A good day for spiritual practices and temple visits.</p>
        </div>
        <div class="rash-card__foot">
          <div class="rash-card__foot-col">
            <div class="rash-card__foot-label">Lucky Color</div>
            <div class="rash-card__foot-value">Pink</div>
          </div>
          <div class="rash-card__foot-col">
            <div class="rash-card__foot-label">Lucky Number</div>
            <div class="rash-card__foot-value rash-card__foot-value--num">6</div>
          </div>
        </div>
      </article>

      <!-- ♊ GEMINI -->
      <article class="rash-card">
        <div class="rash-card__head">
          <div class="rash-card__symbol">♊</div>
          <div class="rash-card__name-block">
            <h3 class="rash-card__name">Gemini</h3>
            <div class="rash-card__sub">Mithun</div>
          </div>
        </div>
        <div>
          <div class="rash-card__label">Daily Horoscope</div>
          <p class="rash-card__text">Today brings positive energy for Gemini. Focus on devotion, patience, and dharmic actions. Lucky color: Green. A good day for spiritual practices and temple visits.</p>
        </div>
        <div class="rash-card__foot">
          <div class="rash-card__foot-col">
            <div class="rash-card__foot-label">Lucky Color</div>
            <div class="rash-card__foot-value">Green</div>
          </div>
          <div class="rash-card__foot-col">
            <div class="rash-card__foot-label">Lucky Number</div>
            <div class="rash-card__foot-value rash-card__foot-value--num">5</div>
          </div>
        </div>
      </article>

      <!-- ♋ CANCER -->
      <article class="rash-card">
        <div class="rash-card__head">
          <div class="rash-card__symbol">♋</div>
          <div class="rash-card__name-block">
            <h3 class="rash-card__name">Cancer</h3>
            <div class="rash-card__sub">Kark</div>
          </div>
        </div>
        <div>
          <div class="rash-card__label">Daily Horoscope</div>
          <p class="rash-card__text">Today brings positive energy for Cancer. Focus on devotion, patience, and dharmic actions. Lucky color: White. A good day for spiritual practices and temple visits.</p>
        </div>
        <div class="rash-card__foot">
          <div class="rash-card__foot-col">
            <div class="rash-card__foot-label">Lucky Color</div>
            <div class="rash-card__foot-value">White</div>
          </div>
          <div class="rash-card__foot-col">
            <div class="rash-card__foot-label">Lucky Number</div>
            <div class="rash-card__foot-value rash-card__foot-value--num">2</div>
          </div>
        </div>
      </article>

      <!-- ♌ LEO -->
      <article class="rash-card">
        <div class="rash-card__head">
          <div class="rash-card__symbol">♌</div>
          <div class="rash-card__name-block">
            <h3 class="rash-card__name">Leo</h3>
            <div class="rash-card__sub">Simha</div>
          </div>
        </div>
        <div>
          <div class="rash-card__label">Daily Horoscope</div>
          <p class="rash-card__text">Today brings positive energy for Leo. Focus on devotion, patience, and dharmic actions. Lucky color: Gold. A good day for spiritual practices and temple visits.</p>
        </div>
        <div class="rash-card__foot">
          <div class="rash-card__foot-col">
            <div class="rash-card__foot-label">Lucky Color</div>
            <div class="rash-card__foot-value">Gold</div>
          </div>
          <div class="rash-card__foot-col">
            <div class="rash-card__foot-label">Lucky Number</div>
            <div class="rash-card__foot-value rash-card__foot-value--num">1</div>
          </div>
        </div>
      </article>

      <!-- ♍ VIRGO -->
      <article class="rash-card">
        <div class="rash-card__head">
          <div class="rash-card__symbol">♍</div>
          <div class="rash-card__name-block">
            <h3 class="rash-card__name">Virgo</h3>
            <div class="rash-card__sub">Kanya</div>
          </div>
        </div>
        <div>
          <div class="rash-card__label">Daily Horoscope</div>
          <p class="rash-card__text">Today brings positive energy for Virgo. Focus on devotion, patience, and dharmic actions. Lucky color: Green. A good day for spiritual practices and temple visits.</p>
        </div>
        <div class="rash-card__foot">
          <div class="rash-card__foot-col">
            <div class="rash-card__foot-label">Lucky Color</div>
            <div class="rash-card__foot-value">Green</div>
          </div>
          <div class="rash-card__foot-col">
            <div class="rash-card__foot-label">Lucky Number</div>
            <div class="rash-card__foot-value rash-card__foot-value--num">5</div>
          </div>
        </div>
      </article>

      <!-- ♎ LIBRA -->
      <article class="rash-card">
        <div class="rash-card__head">
          <div class="rash-card__symbol">♎</div>
          <div class="rash-card__name-block">
            <h3 class="rash-card__name">Libra</h3>
            <div class="rash-card__sub">Tula</div>
          </div>
        </div>
        <div>
          <div class="rash-card__label">Daily Horoscope</div>
          <p class="rash-card__text">Today brings positive energy for Libra. Focus on devotion, patience, and dharmic actions. Lucky color: Blue. A good day for spiritual practices and temple visits.</p>
        </div>
        <div class="rash-card__foot">
          <div class="rash-card__foot-col">
            <div class="rash-card__foot-label">Lucky Color</div>
            <div class="rash-card__foot-value">Blue</div>
          </div>
          <div class="rash-card__foot-col">
            <div class="rash-card__foot-label">Lucky Number</div>
            <div class="rash-card__foot-value rash-card__foot-value--num">6</div>
          </div>
        </div>
      </article>

      <!-- ♏ SCORPIO -->
      <article class="rash-card">
        <div class="rash-card__head">
          <div class="rash-card__symbol">♏</div>
          <div class="rash-card__name-block">
            <h3 class="rash-card__name">Scorpio</h3>
            <div class="rash-card__sub">Vrishchik</div>
          </div>
        </div>
        <div>
          <div class="rash-card__label">Daily Horoscope</div>
          <p class="rash-card__text">Today brings positive energy for Scorpio. Focus on devotion, patience, and dharmic actions. Lucky color: Maroon. A good day for spiritual practices and temple visits.</p>
        </div>
        <div class="rash-card__foot">
          <div class="rash-card__foot-col">
            <div class="rash-card__foot-label">Lucky Color</div>
            <div class="rash-card__foot-value">Maroon</div>
          </div>
          <div class="rash-card__foot-col">
            <div class="rash-card__foot-label">Lucky Number</div>
            <div class="rash-card__foot-value rash-card__foot-value--num">9</div>
          </div>
        </div>
      </article>

      <!-- ♐ SAGITTARIUS -->
      <article class="rash-card">
        <div class="rash-card__head">
          <div class="rash-card__symbol">♐</div>
          <div class="rash-card__name-block">
            <h3 class="rash-card__name">Sagittarius</h3>
            <div class="rash-card__sub">Dhanu</div>
          </div>
        </div>
        <div>
          <div class="rash-card__label">Daily Horoscope</div>
          <p class="rash-card__text">Today brings positive energy for Sagittarius. Focus on devotion, patience, and dharmic actions. Lucky color: Yellow. A good day for spiritual practices and temple visits.</p>
        </div>
        <div class="rash-card__foot">
          <div class="rash-card__foot-col">
            <div class="rash-card__foot-label">Lucky Color</div>
            <div class="rash-card__foot-value">Yellow</div>
          </div>
          <div class="rash-card__foot-col">
            <div class="rash-card__foot-label">Lucky Number</div>
            <div class="rash-card__foot-value rash-card__foot-value--num">3</div>
          </div>
        </div>
      </article>

      <!-- ♑ CAPRICORN -->
      <article class="rash-card">
        <div class="rash-card__head">
          <div class="rash-card__symbol">♑</div>
          <div class="rash-card__name-block">
            <h3 class="rash-card__name">Capricorn</h3>
            <div class="rash-card__sub">Makar</div>
          </div>
        </div>
        <div>
          <div class="rash-card__label">Daily Horoscope</div>
          <p class="rash-card__text">Today brings positive energy for Capricorn. Focus on devotion, patience, and dharmic actions. Lucky color: Black. A good day for spiritual practices and temple visits.</p>
        </div>
        <div class="rash-card__foot">
          <div class="rash-card__foot-col">
            <div class="rash-card__foot-label">Lucky Color</div>
            <div class="rash-card__foot-value">Black</div>
          </div>
          <div class="rash-card__foot-col">
            <div class="rash-card__foot-label">Lucky Number</div>
            <div class="rash-card__foot-value rash-card__foot-value--num">8</div>
          </div>
        </div>
      </article>

      <!-- ♒ AQUARIUS -->
      <article class="rash-card">
        <div class="rash-card__head">
          <div class="rash-card__symbol">♒</div>
          <div class="rash-card__name-block">
            <h3 class="rash-card__name">Aquarius</h3>
            <div class="rash-card__sub">Kumbh</div>
          </div>
        </div>
        <div>
          <div class="rash-card__label">Daily Horoscope</div>
          <p class="rash-card__text">Today brings positive energy for Aquarius. Focus on devotion, patience, and dharmic actions. Lucky color: Blue. A good day for spiritual practices and temple visits.</p>
        </div>
        <div class="rash-card__foot">
          <div class="rash-card__foot-col">
            <div class="rash-card__foot-label">Lucky Color</div>
            <div class="rash-card__foot-value">Blue</div>
          </div>
          <div class="rash-card__foot-col">
            <div class="rash-card__foot-label">Lucky Number</div>
            <div class="rash-card__foot-value rash-card__foot-value--num">4</div>
          </div>
        </div>
      </article>

      <!-- ♓ PISCES -->
      <article class="rash-card">
        <div class="rash-card__head">
          <div class="rash-card__symbol">♓</div>
          <div class="rash-card__name-block">
            <h3 class="rash-card__name">Pisces</h3>
            <div class="rash-card__sub">Meen</div>
          </div>
        </div>
        <div>
          <div class="rash-card__label">Daily Horoscope</div>
          <p class="rash-card__text">Today brings positive energy for Pisces. Focus on devotion, patience, and dharmic actions. Lucky color: Sea Green. A good day for spiritual practices and temple visits.</p>
        </div>
        <div class="rash-card__foot">
          <div class="rash-card__foot-col">
            <div class="rash-card__foot-label">Lucky Color</div>
            <div class="rash-card__foot-value">Sea Green</div>
          </div>
          <div class="rash-card__foot-col">
            <div class="rash-card__foot-label">Lucky Number</div>
            <div class="rash-card__foot-value rash-card__foot-value--num">7</div>
          </div>
        </div>
      </article>

    </div>
  </div>
</section>

<!-- ── FAQ ────────────────────────────────────────────── -->
<section class="rash-faq">
  <div class="rash-faq__head">
    <span class="rash-faq__eyebrow">Need Help?</span>
    <h2>Frequently Asked Questions</h2>
    <div class="rash-faq__gold-line"></div>
  </div>

  <div class="rash-faq__list">

    <div class="rash-faq__item">
      <button class="rash-faq__q" type="button" aria-expanded="false">
        <span>What is Rashifal (Horoscope)?</span>
        <span class="rash-faq__chev" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-linecap="round" stroke-linejoin="round">
            <path d="m6 9 6 6 6-6"/>
          </svg>
        </span>
      </button>
      <div class="rash-faq__a">
        <div class="rash-faq__a-inner">
          <p>Rashifal is the Vedic system of daily guidance based on the position of the Moon at the time of your birth. It offers insights into the day's energy across love, career, health and finances, along with supportive elements like lucky color and lucky number.</p>
        </div>
      </div>
    </div>

    <div class="rash-faq__item">
      <button class="rash-faq__q" type="button" aria-expanded="false">
        <span>Is the prediction based on the Sun Sign or Moon Sign?</span>
        <span class="rash-faq__chev" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-linecap="round" stroke-linejoin="round">
            <path d="m6 9 6 6 6-6"/>
          </svg>
        </span>
      </button>
      <div class="rash-faq__a">
        <div class="rash-faq__a-inner">
          <p>This Rashifal is based on the Moon Sign (Rashi), as prescribed in traditional Vedic astrology. The Moon governs the mind and daily emotional rhythm, so Moon-sign guidance is considered more relevant for day-to-day predictions than Western Sun-sign horoscopes.</p>
        </div>
      </div>
    </div>

    <div class="rash-faq__item">
      <button class="rash-faq__q" type="button" aria-expanded="false">
        <span>How is the daily horoscope calculated?</span>
        <span class="rash-faq__chev" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-linecap="round" stroke-linejoin="round">
            <path d="m6 9 6 6 6-6"/>
          </svg>
        </span>
      </button>
      <div class="rash-faq__a">
        <div class="rash-faq__a-inner">
          <p>Each day's horoscope is prepared by studying the transit of the Moon and other planets relative to each Rashi, along with the Vedic panchang (tithi, vaar, nakshatra and yoga). Insights for the day are drawn from these planetary movements.</p>
        </div>
      </div>
    </div>

    <div class="rash-faq__item">
      <button class="rash-faq__q" type="button" aria-expanded="false">
        <span>How do I find my correct Zodiac Sign (Rashi)?</span>
        <span class="rash-faq__chev" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-linecap="round" stroke-linejoin="round">
            <path d="m6 9 6 6 6-6"/>
          </svg>
        </span>
      </button>
      <div class="rash-faq__a">
        <div class="rash-faq__a-inner">
          <p>Your Rashi is determined by the position of the Moon in the zodiac at the exact time and place of your birth. If you don't already know it, a Vedic birth chart (Janma Kundali) prepared from your date, time and place of birth will reveal your correct Moon sign.</p>
        </div>
      </div>
    </div>

    <div class="rash-faq__item">
      <button class="rash-faq__q" type="button" aria-expanded="false">
        <span>How reliable are these predictions?</span>
        <span class="rash-faq__chev" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-linecap="round" stroke-linejoin="round">
            <path d="m6 9 6 6 6-6"/>
          </svg>
        </span>
      </button>
      <div class="rash-faq__a">
        <div class="rash-faq__a-inner">
          <p>Rashifal is intended as spiritual guidance, not as a substitute for your own judgement or professional advice. It reflects the general cosmic tendencies of the day for each Rashi and is best used as supportive insight for reflection and mindful planning.</p>
        </div>
      </div>
    </div>

  </div>
</section>

<script>
(function () {
  'use strict';

  var items = document.querySelectorAll('.rash-faq__item');
  if (!items.length) return;

  items.forEach(function (item) {
    var btn  = item.querySelector('.rash-faq__q');
    var body = item.querySelector('.rash-faq__a');
    if (!btn || !body) return;

    btn.addEventListener('click', function () {
      var isOpen = item.classList.contains('is-open');

      /* Close all */
      items.forEach(function (other) {
        other.classList.remove('is-open');
        var ob = other.querySelector('.rash-faq__a');
        var obtn = other.querySelector('.rash-faq__q');
        if (ob)  ob.style.maxHeight = null;
        if (obtn) obtn.setAttribute('aria-expanded', 'false');
      });

      /* Open the clicked one (if it was closed) */
      if (!isOpen) {
        item.classList.add('is-open');
        body.style.maxHeight = body.scrollHeight + 'px';
        btn.setAttribute('aria-expanded', 'true');
      }
    });
  });

  /* Keep open item sized correctly on resize */
  window.addEventListener('resize', function () {
    items.forEach(function (item) {
      if (item.classList.contains('is-open')) {
        var body = item.querySelector('.rash-faq__a');
        if (body) body.style.maxHeight = body.scrollHeight + 'px';
      }
    });
  });
})();
</script>

<?php require BASE_PATH . '/includes/footer.php'; ?>