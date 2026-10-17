<div class="toolbar">
  <form method="GET" action="<?= BASE_URL ?>/admin/logs" class="filter-form" style="flex-wrap:wrap;gap:8px">
    <select name="level" class="form-select" data-auto-submit><option value="">كل المستويات</option><?php foreach(['debug','info','warning','error','critical'] as $l): ?><option value="<?= $l ?>" <?= $level===$l?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select>
    <input type="text" name="search" class="form-input" placeholder="بحث..." value="<?= e($search) ?>" style="width:190px">
    <button class="btn btn--sm btn--outline">بحث</button>
    <a href="<?= BASE_URL ?>/admin/logs" class="btn btn--sm btn--outline">إعادة</a>
  </form>
  <?php if(auth()['role']==='super_admin'): ?>
  <form action="<?= BASE_URL ?>/admin/logs/clear" method="POST" style="display:flex;gap:8px;align-items:center" data-confirm="حذف السجلات القديمة؟" data-confirm-title="تنظيف السجلات" data-confirm-ok="تنظيف" data-confirm-danger="1">
    <?= csrf_field() ?>
    <select name="days" class="form-select" style="font-size:12px;padding:5px 10px"><option value="7">7 أيام</option><option value="30" selected>30 يوم</option><option value="90">90 يوم</option></select>
    <button class="btn btn--sm btn--danger"><?= icon('trash') ?> تنظيف</button>
  </form>
  <?php endif; ?>
</div>
<div class="acard"><div class="table-wrap"><table class="atable">
  <thead><tr><th>المستوى</th><th>الإجراء</th><th>الرسالة</th><th>المستخدم</th><th>IP</th><th>الوقت</th></tr></thead>
  <tbody>
  <?php foreach ($logs as $log): ?>
  <tr class="log-level--<?= e($log['level']) ?>">
    <td><span class="badge log-badge--<?= e($log['level']) ?>"><?= e($log['level']) ?></span></td>
    <td><code style="font-size:11px;background:var(--bg-2);padding:2px 6px;border-radius:4px"><?= e($log['action']) ?></code></td>
    <td style="max-width:300px;font-size:12px"><?= e($log['message']) ?><?php if($log['context']): ?><details style="margin-top:4px"><summary style="font-size:11px;cursor:pointer;color:var(--tm)">context</summary><pre style="font-size:10px;margin-top:4px;background:var(--bg-2);padding:6px;border-radius:4px;overflow:auto;max-width:280px"><?= e(json_encode(json_decode($log['context']),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)) ?></pre></details><?php endif; ?></td>
    <td style="font-size:12px"><?= $log['username']?'@'.e($log['username']):'—' ?></td>
    <td style="font-size:11px;color:var(--tm)"><?= e($log['ip_address']??'—') ?></td>
    <td style="font-size:11px;white-space:nowrap"><?= date('d/m H:i:s',strtotime($log['created_at'])) ?></td>
  </tr>
  <?php endforeach; ?>
  <?php if(empty($logs)): ?><tr><td colspan="6" class="empty-row">لا توجد سجلات.</td></tr><?php endif; ?>
  </tbody>
</table></div></div>
