<div class="acard" style="margin-bottom:20px">
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
    <h2 style="margin:0;font-family:var(--font-display)"><?= icon('database') ?> SQL Query</h2>
    <span class="badge badge--red" style="font-size:11px">للمشرف الأعلى فقط</span>
  </div>
  <form method="POST" action="<?= BASE_URL ?>/admin/sql/execute">
    <?= csrf_field() ?>
    <div class="form-group">
      <label class="form-label">SQL Statement</label>
      <textarea name="sql" class="form-input" rows="7" style="font-family:monospace;font-size:13px;direction:ltr;text-align:left" placeholder="SELECT * FROM users LIMIT 10;"><?= e($query??'') ?></textarea>
      <div style="font-size:12px;color:var(--text-muted);margin-top:4px"><?= icon('alert') ?> كل استعلام يسجل تلقائيا في السجلات.</div>
    </div>
    <button type="submit" class="btn btn--gold"><?= icon('play') ?> تنفيذ</button>
  </form>
</div>
<?php if(!empty($error)): ?><div class="acard" style="border:2px solid var(--err-br)"><pre style="color:var(--err-fg);font-size:12px;direction:ltr;text-align:left;white-space:pre-wrap;margin:0"><?= e($error) ?></pre></div>
<?php elseif(isset($affected)&&$affected!==null): ?><div class="acard"><p style="color:var(--ok-fg);font-weight:700"><?= icon('circle-check') ?> تم — صفوف متأثرة: <?= (int)$affected ?></p></div>
<?php elseif(is_array($result)): ?>
<div class="acard">
  <?php if(empty($result)): ?><p style="color:var(--text-muted)">لا توجد نتائج.</p>
  <?php else: ?>
  <div style="font-size:12px;color:var(--text-muted);margin-bottom:8px"><?= count($result) ?> صف</div>
  <div class="table-wrap" style="overflow-x:auto">
    <table class="atable" style="font-family:monospace;font-size:12px;direction:ltr;text-align:left">
      <thead><tr><?php foreach(array_keys($result[0]) as $col): ?><th style="white-space:nowrap"><?= e($col) ?></th><?php endforeach; ?></tr></thead>
      <tbody><?php foreach($result as $row): ?><tr><?php foreach($row as $val): ?><td style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= e((string)$val) ?>"><?= e((string)$val) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>
