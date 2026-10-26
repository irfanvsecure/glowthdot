<?php
/**
 * Service page body, rendered by scripts/build-service-pages.php from storage/app/service-content/{slug}.json.
 * Fields holding HTML: title, intro, text, body, stat, value, label, who, foot, footnote, a, html and every
 * "raw"-style field (visual, embed, form, extra), which is original Elementor markup kept as it was.
 *
 * @var array $data
 */

$e = fn (?string $s) => htmlspecialchars((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$whatsapp = ['href' => 'https://wa.me/971588979925', 'label' => 'WhatsApp us'];
$hero = $data['hero'];
$sections = $data['sections'];

$formIndex = null;
foreach ($sections as $i => $s) {
    if ($s['layout'] === 'form') {
        $formIndex = $i;
        break;
    }
}
$formSection = $formIndex !== null ? $sections[$formIndex] : null;
// On-page calls to action lead to the form; the hero keeps its own links
$auditLabel = $hero['ctas'][0]['label'] ?? 'Get my free audit';
$auditHref = $formSection ? '#lp-form' : ($hero['ctas'][0]['href'] ?? '/get-a-free-audit/');

$icon = [
    'arrow' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>',
    'check' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>',
    'star' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l2.7 5.6 6.1.8-4.5 4.2 1.1 6L12 16.8 6.6 19.6l1.1-6L3.2 9.4l6.1-.8z"/></svg>',
    'whatsapp' => '<svg viewBox="0 0 24 24" aria-hidden="true" class="lp-wa"><path d="M12 3a9 9 0 0 0-7.8 13.5L3 21l4.6-1.2A9 9 0 1 0 12 3zm0 16.4a7.4 7.4 0 0 1-3.8-1l-.3-.2-2.7.7.7-2.6-.2-.3A7.4 7.4 0 1 1 12 19.4zm4-5.5c-.2-.1-1.3-.7-1.5-.7-.2-.1-.4-.1-.5.1l-.7.9c-.1.2-.3.2-.5.1a6 6 0 0 1-3-2.6c-.2-.4.2-.4.6-1.2.1-.1 0-.3 0-.4l-.7-1.6c-.2-.4-.4-.4-.5-.4h-.4a.8.8 0 0 0-.6.3 2.5 2.5 0 0 0-.8 1.9 4.4 4.4 0 0 0 .9 2.3 10 10 0 0 0 3.8 3.4c1.4.6 2 .6 2.7.5.4-.1 1.3-.5 1.5-1.1.2-.5.2-1 .1-1.1z"/></svg>',
];

$button = function (string $href, string $label, string $class = 'lp-btn lp-btn--grad', bool $arrow = true) use ($e, $icon) {
    return '<a href="'.$e($href).'" class="'.$class.'">'.$e($label).($arrow ? $icon['arrow'] : '').'</a>';
};
$whatsappButton = function (string $class = 'lp-btn lp-btn--wa') use ($e, $icon, $whatsapp) {
    return '<a href="'.$e($whatsapp['href']).'" class="'.$class.'" target="_blank" rel="noopener">'.$icon['whatsapp'].$e($whatsapp['label']).'</a>';
};
$anchors = function (array $ids) use ($e) {
    return implode('', array_map(fn ($id) => '<span class="lp-anchor" id="'.$e($id).'"></span>', $ids));
};
$heading = function (array $s, bool $center = true) use ($e) {
    if ($s['kicker'] === '' && $s['title'] === '' && $s['intro'] === '') {
        return '';
    }
    $out = '<div class="lp-head'.($center ? ' lp-head--center' : '').'">';
    if ($s['kicker'] !== '') {
        $out .= '<span class="lp-kicker">'.$e($s['kicker']).'</span>';
    }
    if ($s['title'] !== '') {
        $out .= '<h2 class="lp-title">'.$s['title'].'</h2>';
    }
    if ($s['intro'] !== '') {
        $out .= '<div class="lp-intro">'.$s['intro'].'</div>';
    }

    return $out.'</div>';
};
$footnote = fn (array $s) => $s['footnote'] !== '' ? '<div class="lp-note">'.$s['footnote'].'</div>' : '';

// How many columns a card grid gets
$columns = function (array $items) {
    $n = count($items);
    $steps = $n > 1 && ! array_filter($items, fn ($c) => ($c['badge'] ?? '') === '');
    if ($steps) {
        return $n <= 5 ? $n : 4;
    }
    $long = array_sum(array_map(fn ($c) => strlen(strip_tags($c['text'] ?? '')) + 40 * count($c['list'] ?? []), $items)) / max(1, $n) > 230;

    return match (true) {
        $n <= 3 => $n,
        $n === 4 => $long ? 2 : 4,
        $n >= 7 => 4,
        default => 3,
    };
};

// Sticky nav: sections with a short label
$nav = [];
foreach ($sections as $i => $s) {
    if ($s['kicker'] !== '' && mb_strlen($s['kicker']) <= 26 && $s['layout'] !== 'form' && count($nav) < 6) {
        $nav['s'.$i] = $s['kicker'];
    }
}
?>
<main class="lp lp--service" id="lp-<?= $e($data['slug']) ?>">

  <header class="lp-hero">
    <?= $anchors($hero['anchors']) ?>
    <div class="lp-wrap lp-hero__grid">
      <div class="lp-hero__copy">
        <?php if ($hero['pill'] !== ''): ?><span class="lp-pill"><i></i><?= $e($hero['pill']) ?></span><?php endif ?>
        <h1><?= $hero['title'] ?></h1>
        <div class="lp-hero__intro"><?= $hero['intro'] ?></div>
        <div class="lp-actions">
          <?php foreach ($hero['ctas'] as $i => $cta): ?>
          <?= $button($cta['href'], $cta['label'], $i === 0 ? 'lp-btn lp-btn--grad' : 'lp-btn lp-btn--ghost', $i === 0) ?>
          <?php endforeach ?>
        </div>
        <?php if ($hero['chips']): ?>
        <ul class="lp-chips">
          <?php foreach ($hero['chips'] as $chip): ?>
          <li><b><?= $chip['title'] ?></b><?php if ($chip['text'] !== ''): ?><span><?= $chip['text'] ?></span><?php endif ?></li>
          <?php endforeach ?>
        </ul>
        <?php endif ?>
        <?= implode('', $hero['extra']) ?>
      </div>
      <?php if ($hero['visual'] !== ''): ?>
      <div class="lp-hero__visual"><?= $hero['visual'] ?></div>
      <?php endif ?>
    </div>
  </header>

  <?php if ($nav): ?>
  <nav class="lp-nav" aria-label="On this page">
    <div class="lp-wrap lp-nav__in">
      <ul>
        <?php foreach ($nav as $id => $navLabel): ?>
        <li><a href="#<?= $id ?>"><?= $e($navLabel) ?></a></li>
        <?php endforeach ?>
      </ul>
      <a href="<?= $e($auditHref) ?>" class="lp-nav__cta"><?= $e($auditLabel) ?></a>
    </div>
  </nav>
  <?php endif ?>

  <?php foreach ($sections as $i => $s):
      $tone = $i % 2 === 1 ? ' lp-section--soft' : '';
      $sid = 's'.$i; ?>

  <?php if ($s['layout'] === 'form'): ?>
  <section class="lp-formcta" id="lp-form" data-lp-final>
    <?= $anchors($s['anchors']) ?><span class="lp-anchor" id="<?= $sid ?>"></span>
    <div class="lp-wrap">
      <div class="lp-formcta__card">
        <div class="lp-formcta__copy">
          <?php if ($s['kicker'] !== ''): ?><span class="lp-kicker lp-kicker--lime"><?= $e($s['kicker']) ?></span><?php endif ?>
          <h2 class="lp-title"><?= $s['title'] ?></h2>
          <?php if ($s['intro'] !== ''): ?><div class="lp-intro"><?= $s['intro'] ?></div><?php endif ?>
          <?php if ($s['list']): ?>
          <ul class="lp-checks lp-checks--dark">
            <?php foreach ($s['list'] as $li): ?><li><?= $li['html'] ?></li><?php endforeach ?>
          </ul>
          <?php endif ?>
          <div class="lp-actions"><?= $whatsappButton('lp-btn lp-btn--line') ?></div>
        </div>
        <div class="lp-formcta__form"><?= $s['form'] ?></div>
      </div>
      <?= $footnote($s) ?>
    </div>
  </section>
  <?php continue; endif ?>

  <section class="lp-section<?= $s['layout'] === 'stats' ? ' lp-section--dark' : $tone ?> lp-section--<?= $s['layout'] ?>" id="<?= $sid ?>">
    <?= $anchors($s['anchors']) ?>
    <div class="lp-wrap">

      <?php if ($s['layout'] === 'faq'): ?>
      <div class="lp-faq">
        <div class="lp-faq__side">
          <?= $heading($s, false) ?>
          <div class="lp-ask">
            <?= $whatsappButton() ?>
            <?= $button($auditHref, $auditLabel) ?>
          </div>
        </div>
        <div class="lp-faq__list">
          <?php foreach ($s['items'] as $n => $item): ?>
          <details<?= $n === 0 ? ' open' : '' ?>>
            <summary><?= $e($item['q']) ?></summary>
            <div class="lp-faq__answer"><?= $item['a'] ?></div>
          </details>
          <?php endforeach ?>
        </div>
      </div>
      <?= $footnote($s) ?>

      <?php elseif ($s['layout'] === 'features'): ?>
      <?= $heading($s) ?>
      <div class="lp-features">
        <?php foreach ($s['items'] as $n => $f): ?>
        <div class="lp-feature<?= $n % 2 ? ' lp-feature--flip' : '' ?>">
          <div class="lp-feature__copy">
            <?php if ($f['kicker'] !== ''): ?><span class="lp-feature__num"><?= $e($f['kicker']) ?></span><?php endif ?>
            <h3><?= $f['title'] ?></h3>
            <div class="lp-feature__body"><?= $f['body'] ?></div>
            <?php if ($f['ctas']): ?>
            <div class="lp-actions">
              <?php foreach ($f['ctas'] as $cta): ?><?= $button($cta['href'], $cta['label'], 'lp-btn lp-btn--soft') ?><?php endforeach ?>
            </div>
            <?php endif ?>
          </div>
          <div class="lp-feature__visual"><?= $f['visual'] ?></div>
        </div>
        <?php endforeach ?>
      </div>
      <?= $footnote($s) ?>

      <?php elseif ($s['layout'] === 'stats'): ?>
      <?= $heading($s) ?>
      <div class="lp-bigstats lp-bigstats--<?= count($s['items']) ?>">
        <?php foreach ($s['items'] as $st): ?>
        <div class="lp-bigstat"><b><?= $st['value'] ?></b><span><?= $st['label'] ?></span></div>
        <?php endforeach ?>
      </div>
      <?= $footnote($s) ?>

      <?php elseif ($s['layout'] === 'reviews'): ?>
      <?= $heading($s) ?>
      <div class="lp-reviews">
        <?php foreach ($s['items'] as $r): ?>
        <figure class="lp-review">
          <div class="lp-review__stars" role="img" aria-label="Rated <?= (int) $r['stars'] ?> out of 5"><?= str_repeat($icon['star'], max(0, min(5, (int) $r['stars']))) ?></div>
          <blockquote><?= $r['text'] ?></blockquote>
          <figcaption><?= $r['who'] ?></figcaption>
        </figure>
        <?php endforeach ?>
      </div>
      <?= $footnote($s) ?>

      <?php elseif ($s['layout'] === 'cards'): $cols = $columns($s['items']); ?>
      <?= $heading($s) ?>
      <div class="lp-cards lp-cards--<?= $cols ?>">
        <?php foreach ($s['items'] as $c): ?>
        <?php if ($c['kind'] === 'stat'): ?>
        <div class="lp-card lp-card--stat"><b class="lp-card__stat"><?= $c['value'] ?></b><div class="lp-card__text"><?= $c['label'] ?></div></div>
        <?php elseif ($c['kind'] === 'review' || $c['kind'] === 'feature' || $c['kind'] === 'raw'): ?>
        <div class="lp-card lp-card--raw"><?= $c['html'] ?? '' ?></div>
        <?php else: ?>
        <article class="lp-card<?= $c['badge'] !== '' ? ' lp-card--step' : '' ?>">
          <?php if ($c['badge'] !== ''): ?><span class="lp-card__badge"><?= $e($c['badge']) ?></span><?php endif ?>
          <?php if ($c['stat'] !== ''): ?><b class="lp-card__stat"><?= $c['stat'] ?></b>
          <?php elseif ($c['icon'] !== ''): ?><span class="lp-card__icon"><?= $c['icon'] ?></span><?php endif ?>
          <?php if ($c['title'] !== ''): ?>
          <h3><?= $c['href'] !== '' && ! str_contains($c['title'], '<a ') ? '<a href="'.$e($c['href']).'">'.$c['title'].'</a>' : $c['title'] ?></h3>
          <?php endif ?>
          <?php if ($c['text'] !== ''): ?><div class="lp-card__text"><?= $c['text'] ?></div><?php endif ?>
          <?php if ($c['list']): ?>
          <ul class="lp-checks">
            <?php foreach ($c['list'] as $li): ?><li><?= $li['href'] !== '' ? '<a href="'.$e($li['href']).'">'.$li['html'].'</a>' : $li['html'] ?></li><?php endforeach ?>
          </ul>
          <?php endif ?>
          <?php if ($c['foot'] !== ''): ?><div class="lp-card__foot"><?= $c['foot'] ?></div><?php endif ?>
          <?php if ($c['extra'] !== ''): ?><div class="lp-card__extra"><?= $c['extra'] ?></div><?php endif ?>
        </article>
        <?php endif ?>
        <?php endforeach ?>
      </div>
      <?= $footnote($s) ?>

      <?php else: ?>
      <?= $heading($s) ?>
      <div class="lp-embed"><?= $s['embed'] ?></div>
      <?= $footnote($s) ?>
      <?php endif ?>

    </div>
  </section>

  <?php if ($i === 1 && $formSection): ?>
  <section class="lp-band" aria-label="<?= $e($formSection['kicker'] ?: strip_tags($formSection['title'])) ?>">
    <div class="lp-wrap lp-band__in">
      <div>
        <p class="lp-band__title"><?= $e($formSection['kicker'] ?: strip_tags($formSection['title'])) ?></p>
        <p class="lp-band__sub"><?= $formSection['title'] ?></p>
      </div>
      <div class="lp-actions">
        <?= $button($auditHref, $auditLabel, 'lp-btn lp-btn--lime') ?>
        <?= $whatsappButton('lp-btn lp-btn--line') ?>
      </div>
    </div>
  </section>
  <?php endif ?>

  <?php endforeach ?>

  <div class="lp-sticky" aria-hidden="true">
    <a href="<?= $e($auditHref) ?>" class="lp-sticky__audit" tabindex="-1"><?= $e($auditLabel) ?></a>
    <a href="<?= $e($whatsapp['href']) ?>" class="lp-sticky__wa" target="_blank" rel="noopener" tabindex="-1" aria-label="<?= $e($whatsapp['label']) ?>"><?= $icon['whatsapp'] ?></a>
  </div>
</main>
