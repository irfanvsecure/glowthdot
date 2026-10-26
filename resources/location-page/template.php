<?php
/**
 * Location page body, rendered by scripts/build-location-pages.php from storage/app/location-content/{slug}.json.
 * Fields named title, intro, body, text, source, note, lead and a hold trusted HTML (links, highlights); the rest is plain text.
 *
 * @var array $data
 */

$e = fn (?string $s) => htmlspecialchars((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
// Link label whose trailing arrow must not wrap onto a line of its own
$linkLabel = fn (string $s) => str_replace(' →', '&nbsp;→', $e($s));
$audit = $data['hero']['ctas'][0];
$whatsapp = $data['final']['ctas'][1] ?? null;
$finalAudit = $data['final']['ctas'][0];

$icon = [
    'arrow' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>',
    'check' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>',
    'cross' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 7l10 10M17 7L7 17"/></svg>',
    'shield' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l7 3v5.5c0 4.4-3 8.2-7 9.5-4-1.3-7-5.1-7-9.5V6z"/><path d="M8.8 12.2l2.2 2.2 4.2-4.4"/></svg>',
    'pin' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>',
    'whatsapp' => '<svg viewBox="0 0 24 24" aria-hidden="true" class="lp-wa"><path d="M12 3a9 9 0 0 0-7.8 13.5L3 21l4.6-1.2A9 9 0 1 0 12 3zm0 16.4a7.4 7.4 0 0 1-3.8-1l-.3-.2-2.7.7.7-2.6-.2-.3A7.4 7.4 0 1 1 12 19.4zm4-5.5c-.2-.1-1.3-.7-1.5-.7-.2-.1-.4-.1-.5.1l-.7.9c-.1.2-.3.2-.5.1a6 6 0 0 1-3-2.6c-.2-.4.2-.4.6-1.2.1-.1 0-.3 0-.4l-.7-1.6c-.2-.4-.4-.4-.5-.4h-.4a.8.8 0 0 0-.6.3 2.5 2.5 0 0 0-.8 1.9 4.4 4.4 0 0 0 .9 2.3 10 10 0 0 0 3.8 3.4c1.4.6 2 .6 2.7.5.4-.1 1.3-.5 1.5-1.1.2-.5.2-1 .1-1.1z"/></svg>',
];

$cityPages = ['/dubai/', '/abu-dhabi/', '/sharjah/'];

// Icon for a related-page link, picked from words in its address
$topicIcon = function (string $href) {
    $paths = [
        'russian|arabic|korean' => '<path d="M21 12a8 8 0 0 1-8 8H4l2-3a8 8 0 1 1 15-5z"/><path d="M9 11h6M9 14h4"/>',
        'laser' => '<path d="M13 3L5 14h6l-1 7 8-11h-6z"/>',
        'clinic|derma|medical|surgery' => '<rect x="4" y="4" width="16" height="16" rx="4"/><path d="M12 8v8M8 12h8"/>',
        'spa|bath|wellness|massage|hammam' => '<path d="M12 21c-4.5-2-7-5.5-7-10 3 0 5.5 1.5 7 4 1.5-2.5 4-4 7-4 0 4.5-2.5 8-7 10z"/><path d="M12 15V9"/>',
        'nail|hair|salon|barber|lash|brow' => '<circle cx="6" cy="7" r="3"/><circle cx="6" cy="17" r="3"/><path d="M8.5 8.5L20 19M8.5 15.5L20 5"/>',
    ];
    foreach ($paths as $words => $path) {
        if (preg_match('#'.$words.'#', $href)) {
            return '<svg viewBox="0 0 24 24" aria-hidden="true">'.$path.'</svg>';
        }
    }

    return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l1.8 4.6L18 9l-4.2 1.4L12 15l-1.8-4.6L6 9l4.2-1.4z"/><path d="M18 15l.9 2.1L21 18l-2.1.9L18 21l-.9-2.1L15 18l2.1-.9z"/></svg>';
};

$nav = [
    'services' => 'Services',
    'market' => 'Market',
    'districts' => 'Areas',
    'rules' => 'Regulations',
    'compare' => 'Why Glowth',
    'faq' => 'FAQs',
];

$audit_button = function (array $cta, string $class = 'lp-btn lp-btn--grad') use ($e, $icon) {
    return '<a href="'.$e($cta['href']).'" class="'.$class.'">'.$e($cta['label']).$icon['arrow'].'</a>';
};
$whatsapp_button = function (string $class = 'lp-btn lp-btn--line') use ($e, $icon, $whatsapp) {
    return $whatsapp ? '<a href="'.$e($whatsapp['href']).'" class="'.$class.'" target="_blank" rel="noopener">'.$icon['whatsapp'].$e($whatsapp['label']).'</a>' : '';
};
$trust = function () use ($e, $icon, $data) {
    $out = '<ul class="lp-trust">';
    foreach ($data['final']['trust'] as $item) {
        $out .= '<li>'.$icon['check'].$e($item).'</li>';
    }

    return $out.'</ul>';
};
$heading = function (array $block, string $tag = 'h2', bool $center = false) use ($e) {
    $out = '<div class="lp-head'.($center ? ' lp-head--center' : '').'">';
    if ($block['kicker'] !== '') {
        $out .= '<span class="lp-kicker">'.$e($block['kicker']).'</span>';
    }
    $out .= '<'.$tag.' class="lp-title">'.$block['title'].'</'.$tag.'>';
    if ($block['intro'] !== '') {
        $out .= '<p class="lp-intro">'.$block['intro'].'</p>';
    }

    return $out.'</div>';
};
?>
<main class="lp" id="lp-<?= $e($data['slug']) ?>">

  <header class="lp-hero">
    <div class="lp-wrap lp-hero__grid">
      <div class="lp-hero__copy">
        <span class="lp-pill"><i></i><?= $e($data['hero']['pill']) ?></span>
        <h1><?= $data['hero']['title'] ?></h1>
        <p class="lp-hero__intro"><?= $data['hero']['intro'] ?></p>
        <div class="lp-actions">
          <?= $audit_button($audit) ?>
          <?php if (isset($data['hero']['ctas'][1])): ?>
          <a href="<?= $e($data['hero']['ctas'][1]['href']) ?>" class="lp-btn lp-btn--ghost"><?= $e($data['hero']['ctas'][1]['label']) ?></a>
          <?php endif ?>
        </div>
        <?= $trust() ?>
      </div>

      <aside class="lp-audit" aria-label="<?= $e($data['final']['kicker']) ?>">
        <span class="lp-audit__badge"><?= $icon['shield'] ?><?= $e($data['final']['kicker']) ?></span>
        <p class="lp-audit__text"><?= $data['final']['intro'] ?></p>
        <?= $audit_button($finalAudit, 'lp-btn lp-btn--grad lp-btn--block') ?>
        <?= $whatsapp_button('lp-btn lp-btn--wa lp-btn--block') ?>
      </aside>
    </div>
  </header>

  <section class="lp-stats" aria-label="<?= $e($data['city']) ?> in numbers">
    <div class="lp-wrap lp-stats__grid">
      <?php foreach ($data['hero']['stats'] as $stat): ?>
      <div class="lp-stat">
        <b><?= $e($stat['value']) ?></b>
        <span><?= $e($stat['label']) ?></span>
        <?php if ($stat['source'] !== ''): ?><small><?= $stat['source'] ?></small><?php endif ?>
      </div>
      <?php endforeach ?>
    </div>
  </section>

  <nav class="lp-nav" aria-label="On this page">
    <div class="lp-wrap lp-nav__in">
      <ul>
        <?php foreach ($nav as $id => $label): ?>
        <li><a href="#<?= $id ?>"><?= $e($label) ?></a></li>
        <?php endforeach ?>
      </ul>
      <a href="<?= $e($audit['href']) ?>" class="lp-nav__cta"><?= $e($audit['label']) ?></a>
    </div>
  </nav>

  <section class="lp-section" id="services">
    <div class="lp-wrap">
      <?= $heading($data['services'], 'h2', true) ?>
      <div class="lp-services">
        <?php foreach ($data['services']['cards'] as $card): ?>
        <article class="lp-service">
          <span class="lp-service__icon"><?= $card['icon'] ?></span>
          <h3><a href="<?= $e($card['href']) ?>"><?= $card['title'] ?></a></h3>
          <p><?= $card['text'] ?></p>
          <?php if ($card['read']): ?>
          <a class="lp-service__read" href="<?= $e($card['read']['href']) ?>"><?= $linkLabel($card['read']['label']) ?></a>
          <?php endif ?>
        </article>
        <?php endforeach ?>
      </div>
    </div>
  </section>

  <section class="lp-band" aria-label="<?= $e($data['final']['kicker']) ?>">
    <div class="lp-wrap lp-band__in">
      <div>
        <p class="lp-band__title"><?= $e($data['final']['kicker']) ?></p>
        <?= $trust() ?>
      </div>
      <div class="lp-actions">
        <?= $audit_button($finalAudit, 'lp-btn lp-btn--lime') ?>
        <?= $whatsapp_button('lp-btn lp-btn--line') ?>
      </div>
    </div>
  </section>

  <section class="lp-section" id="market">
    <div class="lp-wrap">
      <?= $heading($data['market']) ?>
      <div class="lp-market">
        <div class="lp-prose"><?= $data['market']['body'] ?></div>
        <aside class="lp-glance">
          <h3><?= $e($data['market']['glance']['title']) ?></h3>
          <ul>
            <?php foreach ($data['market']['glance']['items'] as $item): ?>
            <li><b><?= $e($item['value']) ?></b><span><?= $item['text'] ?></span></li>
            <?php endforeach ?>
          </ul>
          <small><?= $data['market']['glance']['source'] ?></small>
        </aside>
      </div>
    </div>
  </section>

  <section class="lp-section lp-section--dark" id="districts">
    <div class="lp-wrap">
      <?= $heading($data['areas'], 'h2', true) ?>
      <div class="lp-areas" data-lp-tabs>
        <div class="lp-areas__tabs" role="tablist" aria-label="<?= $e($data['city']) ?> areas">
          <?php foreach ($data['areas']['items'] as $i => $area): ?>
          <button type="button" role="tab" id="lp-tab-<?= $e($area['id']) ?>" aria-controls="lp-area-<?= $e($area['id']) ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>" tabindex="<?= $i === 0 ? '0' : '-1' ?>"><?= $icon['pin'] ?><?= $e($area['tab']) ?></button>
          <?php endforeach ?>
        </div>
        <?php foreach ($data['areas']['items'] as $i => $area): ?>
        <div class="lp-area" role="tabpanel" id="lp-area-<?= $e($area['id']) ?>" aria-labelledby="lp-tab-<?= $e($area['id']) ?>"<?= $i === 0 ? '' : ' hidden' ?>>
          <div class="lp-area__copy">
            <h3><?= $area['title'] ?></h3>
            <p><?= $area['text'] ?></p>
            <ul class="lp-tags">
              <?php foreach ($area['tags'] as $tag): ?><li><?= $e($tag) ?></li><?php endforeach ?>
            </ul>
          </div>
          <dl class="lp-area__facts">
            <?php foreach ($area['facts'] as $fact): ?>
            <div><dt><?= $e($fact['label']) ?></dt><dd><?= $e($fact['value']) ?></dd></div>
            <?php endforeach ?>
          </dl>
        </div>
        <?php endforeach ?>
      </div>
      <p class="lp-note lp-note--dark"><?= $data['areas']['note'] ?></p>
      <div class="lp-center">
        <?= $audit_button($audit, 'lp-btn lp-btn--grad') ?>
      </div>
    </div>
  </section>

  <section class="lp-section lp-section--soft" id="rules">
    <div class="lp-wrap">
      <?= $heading($data['rules'], 'h2', true) ?>
      <div class="lp-rules lp-rules--<?= count($data['rules']['cards']) ?>">
        <?php foreach ($data['rules']['cards'] as $card): ?>
        <article class="lp-rule">
          <span class="lp-rule__tag"><?= $icon['shield'] ?><?= $e($card['tag']) ?></span>
          <h3><?= $card['title'] ?></h3>
          <div class="lp-rule__body"><?= $card['body'] ?></div>
        </article>
        <?php endforeach ?>
      </div>
      <?php if ($data['rules']['note'] !== ''): ?><p class="lp-note"><?= $data['rules']['note'] ?></p><?php endif ?>
    </div>
  </section>

  <section class="lp-section">
    <div class="lp-wrap lp-maps">
      <?= $heading($data['maps']) ?>
      <figure class="lp-map">
        <figcaption class="lp-map__head">
          <span><?= $e($data['maps']['search']) ?></span>
          <?php if ($data['maps']['badge'] !== ''): ?><em><?= $e($data['maps']['badge']) ?></em><?php endif ?>
        </figcaption>
        <ol class="lp-map__list">
          <?php foreach ($data['maps']['rows'] as $row): ?>
          <li class="<?= $row['you'] ? 'is-you' : '' ?>">
            <span class="lp-map__pin"><?= $e($row['pin']) ?></span>
            <span class="lp-map__name"><b><?= $e($row['name']) ?></b><small><?= $e($row['detail']) ?></small></span>
            <?php if ($row['tag'] !== ''): ?><span class="lp-map__tag"><?= $e($row['tag']) ?></span><?php endif ?>
          </li>
          <?php endforeach ?>
        </ol>
        <?php if ($data['maps']['note'] !== ''): ?><p class="lp-map__note"><?= $data['maps']['note'] ?></p><?php endif ?>
      </figure>
    </div>
  </section>

  <section class="lp-section lp-section--soft" id="compare">
    <div class="lp-wrap">
      <?= $heading($data['compare'], 'h2', true) ?>
      <div class="lp-compare" role="table" aria-label="<?= $e(strip_tags($data['compare']['title'])) ?>">
        <div class="lp-compare__row lp-compare__row--head" role="row">
          <?php foreach ($data['compare']['columns'] as $i => $col): ?>
          <span role="columnheader"<?= $i === 1 ? ' class="is-us"' : '' ?>><?= $e($col) ?></span>
          <?php endforeach ?>
        </div>
        <?php foreach ($data['compare']['rows'] as $row): ?>
        <div class="lp-compare__row" role="row">
          <span role="rowheader"><?= $e($row[0]) ?></span>
          <span role="cell" class="is-us" data-col="<?= $e($data['compare']['columns'][1]) ?>"><?= $icon['check'] ?><?= $e($row[1]) ?></span>
          <span role="cell" class="is-them" data-col="<?= $e($data['compare']['columns'][2]) ?>"><?= $icon['cross'] ?><?= $e($row[2]) ?></span>
        </div>
        <?php endforeach ?>
      </div>
    </div>
  </section>

  <section class="lp-section" id="faq">
    <div class="lp-wrap lp-faq">
      <div class="lp-faq__side">
        <?= $heading($data['faq']) ?>
        <div class="lp-ask">
          <?= $whatsapp_button('lp-btn lp-btn--wa') ?>
          <?= $audit_button($audit, 'lp-btn lp-btn--grad') ?>
        </div>
      </div>
      <div class="lp-faq__list">
        <?php foreach ($data['faq']['items'] as $i => $item): ?>
        <details<?= $i === 0 ? ' open' : '' ?>>
          <summary><?= $e($item['q']) ?></summary>
          <p><?= $item['a'] ?></p>
        </details>
        <?php endforeach ?>
      </div>
    </div>
  </section>

  <section class="lp-links">
    <div class="lp-wrap lp-links__grid">
      <?php foreach ($data['links'] as $group):
          $isCities = ! array_diff(array_column($group['links'], 'href'), $cityPages); ?>
      <div class="lp-links__group<?= $isCities ? ' lp-links__group--cities' : '' ?>">
        <h2 class="lp-kicker<?= $isCities ? ' lp-kicker--lime' : '' ?>"><?= $e($group['label']) ?></h2>
        <?php if ($group['lead'] !== ''): ?><p><?= $group['lead'] ?></p><?php endif ?>
        <ul>
          <?php foreach ($group['links'] as $link): ?>
          <li>
            <a href="<?= $e($link['href']) ?>">
              <span class="lp-links__icon"><?= $isCities ? $icon['pin'] : $topicIcon($link['href']) ?></span>
              <span class="lp-links__label"><?= $e(rtrim($link['label'], ' →')) ?></span>
              <span class="lp-links__go"><?= $icon['arrow'] ?></span>
            </a>
          </li>
          <?php endforeach ?>
        </ul>
      </div>
      <?php endforeach ?>
    </div>
  </section>

  <section class="lp-final" id="audit" data-lp-final>
    <div class="lp-wrap">
      <div class="lp-final__card">
        <span class="lp-kicker lp-kicker--lime"><?= $e($data['final']['kicker']) ?></span>
        <h2 class="lp-title"><?= $data['final']['title'] ?></h2>
        <p class="lp-intro"><?= $data['final']['intro'] ?></p>
        <div class="lp-actions lp-actions--center">
          <?= $audit_button($finalAudit) ?>
          <?= $whatsapp_button() ?>
        </div>
        <?= $trust() ?>
      </div>
    </div>
  </section>

  <div class="lp-sticky" aria-hidden="true">
    <a href="<?= $e($audit['href']) ?>" class="lp-sticky__audit" tabindex="-1"><?= $e($audit['label']) ?></a>
    <?php if ($whatsapp): ?>
    <a href="<?= $e($whatsapp['href']) ?>" class="lp-sticky__wa" target="_blank" rel="noopener" tabindex="-1" aria-label="<?= $e($whatsapp['label']) ?>"><?= $icon['whatsapp'] ?></a>
    <?php endif ?>
  </div>
</main>
