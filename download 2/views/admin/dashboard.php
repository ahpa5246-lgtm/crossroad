<!-- Stats -->
<div class="stats-grid">
<?php
$cards=[
  ['icon'=>'users','val'=>$stats['users'],       'lbl'=>'المستخدمون',    'cl'=>'navy'],
  ['icon'=>'clipboard','val'=>$stats['submissions'],  'lbl'=>'الطلبات الكلية','cl'=>'gold'],
  ['icon'=>'hourglass','val'=>$stats['pending'],      'lbl'=>'قيد المراجعة', 'cl'=>'red'],
  ['icon'=>'circle-check','val'=>$stats['accepted'],     'lbl'=>'المقبولون',     'cl'=>'green'],
  ['icon'=>'target','val'=>$stats['challenges'],   'lbl'=>'التحديات',      'cl'=>'teal'],
  ['icon'=>'award','val'=>$stats['completions'],  'lbl'=>'متممو التحديات','cl'=>'purple'],
  ['icon'=>'dot','val'=>$stats['open_forms'],   'lbl'=>'نماذج مفتوحة', 'cl'=>'navy'],
  ['icon'=>'trophy','val'=>$stats['certs'],        'lbl'=>'الشهادات',      'cl'=>'gold'],
  ['icon'=>'newspaper','val'=>$stats['news'],         'lbl'=>'الفعاليات',      'cl'=>'blue'],
];
foreach($cards as $c): ?>
<div class="stat-card stat-card--<?= $c['cl'] ?>">
  <div class="stat-icon"><?= icon($c['icon'], 22) ?></div>
  <div><div class="stat-val"><?= number_format($c['val']) ?></div><div class="stat-lbl"><?= $c['lbl'] ?></div></div>
</div>
<?php endforeach; ?>
</div>

<!-- Pending Certificates Alert -->
<?php if (!empty($pendingCerts)): ?>
<div class="acard" style="margin-bottom:24px;border:2px solid var(--gold);background:var(--gold-faint)">
  <div class="acard-header" style="background:var(--gold-faint);border-color:var(--gold-pale)">
    <h3 style="color:var(--gold)"><?= icon('award') ?> شهادات في الانتظار — <?= count($pendingCerts) ?> مستخدم</h3>
    <a href="<?= BASE_URL ?>/admin/certificates/issue" class="btn btn--gold btn--sm">إصدار شهادة ←</a>
  </div>
  <div class="table-wrap">
    <table class="atable">
      <thead><tr><th>المستخدم</th><th>التيليغرام</th><th>التحدي</th><th>النتيجة</th><th>تاريخ الإتمام</th><th>إجراء</th></tr></thead>
      <tbody>
      <?php foreach ($pendingCerts as $pc): ?>
      <tr>
        <td>
          <strong><?= e($pc['display_name']) ?></strong><br>
          <span style="font-size:11px;color:var(--tm)">@<?= e($pc['username']) ?></span>
        </td>
        <td style="color:var(--sage-dark);font-weight:600"><?= $pc['telegram_username'] ? e($pc['telegram_username']) : '<span style="color:var(--tm)">—</span>' ?></td>
        <td><?= e($pc['challenge_title']) ?></td>
        <td><strong style="color:var(--ok-fg)"><?= round($pc['percentage'], 1) ?>%</strong></td>
        <td style="font-size:12px"><?= date('d/m/Y H:i', strtotime($pc['completed_at'])) ?></td>
        <td>
          <a href="<?= BASE_URL ?>/admin/certificates/issue?user_id=<?= $pc['user_id'] ?>&challenge_id=<?= $pc['challenge_id'] ?>"
             class="btn btn--xs btn--gold"><?= icon('award') ?> أصدر</a>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Main grid -->
<div class="two-col">
  <div class="acard">
    <div class="acard-header"><h3>إجراءات سريعة</h3></div>
    <div class="quick-grid">
      <a href="<?= BASE_URL ?>/admin/forms/new"          class="quick-item"><span><?= icon('plus') ?></span>نموذج جديد</a>
      <a href="<?= BASE_URL ?>/admin/news/new"           class="quick-item"><span><?= icon('file-text') ?></span>فعالية جديدة</a>
      <a href="<?= BASE_URL ?>/admin/challenges/new"     class="quick-item"><span><?= icon('target') ?></span>تحدي جديد</a>
      <a href="<?= BASE_URL ?>/admin/submissions"        class="quick-item"><span><?= icon('clipboard') ?></span>مراجعة الطلبات</a>
      <a href="<?= BASE_URL ?>/admin/certificates/issue" class="quick-item"><span><?= icon('award') ?></span>إصدار شهادة</a>
      <a href="<?= BASE_URL ?>/admin/content"            class="quick-item"><span><?= icon('pencil') ?></span>تعديل المحتوى</a>
    </div>
  </div>
  <div class="acard">
    <div class="acard-header"><h3>آخر نشاطات النظام</h3></div>
    <div class="table-wrap">
      <table class="atable">
        <thead><tr><th>المستوى</th><th>الإجراء</th><th>الرسالة</th><th>الوقت</th></tr></thead>
        <tbody>
        <?php foreach ($logs as $log): ?>
        <tr>
          <td><span class="badge log-badge--<?= $log['level'] ?>"><?= e($log['level']) ?></span></td>
          <td><code style="font-size:11px;background:var(--bg-2);padding:2px 6px;border-radius:4px"><?= e($log['action']) ?></code></td>
          <td style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px"><?= e($log['message']) ?></td>
          <td style="font-size:11px;white-space:nowrap"><?= date('H:i:s', strtotime($log['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($logs)): ?><tr><td colspan="4" class="empty-row">لا توجد سجلات</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
