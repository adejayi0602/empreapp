<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin(); require_perm('careers.view');
$fj = (int)($_GET['job'] ?? 0);
$MOVABLE = array_values(array_diff(PIPELINE_COLUMNS, ['More Details Needed']));   // "More details" needs a message, so it is set from the application page

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); require_perm('careers.manage');
    $ajax = post('ajax', 1) === '1';
    $a = q1("SELECT a.*, j.title jtitle, j.company jcompany FROM applications a JOIN jobs j ON j.id=a.job_id WHERE a.id=?", [(int)post('id')]);
    $to = post('status', 30);
    $res = ['ok' => false, 'msg' => 'Could not move that application.'];
    if ($a && in_array($to, $MOVABLE, true) && $a['status'] !== $to) {
        $mail = hr_set_status($a, $to, '', admin_user()['name'], post('notify', 1) === '1');
        $res = ['ok' => true, 'msg' => $a['name'] . ' moved to ' . $to . '.' . hr_notice($mail)];
    } elseif ($a && $a['status'] === $to) $res = ['ok' => true, 'msg' => 'Already in ' . $to . '.'];
    if ($ajax) { header('Content-Type: application/json'); echo json_encode($res); exit; }
    flash($res['msg'], $res['ok'] ? 'ok' : 'err'); redirect('pipeline.php' . ($fj ? '?job=' . $fj : ''));
}
$jobs = q("SELECT id,title FROM jobs ORDER BY sort,id");
$cols = [];
foreach (PIPELINE_COLUMNS as $c) {
    $lim = in_array($c, ['Rejected', 'Not Qualified', 'Hired'], true) ? 25 : 200;
    $cols[$c] = q("SELECT a.id,a.code,a.name,a.status,a.created_at,a.updated_at,a.interview_at,a.edit_unlocked,j.title jtitle FROM applications a JOIN jobs j ON j.id=a.job_id WHERE a.status=?" . ($fj ? ' AND a.job_id=' . $fj : '') . " ORDER BY a.updated_at DESC LIMIT $lim", [$c]);
}
$tot = array_column(q("SELECT status, COUNT(*) n FROM applications" . ($fj ? ' WHERE job_id=' . $fj : '') . " GROUP BY status"), 'n', 'status');
$canMove = can('careers.manage');
admin_head('Pipeline board', 'pipeline'); ?>
<style>.content{max-width:none}</style>
<form class="bar" method="get">
  <select name="job" onchange="this.form.submit()"><option value="">All jobs</option><?php foreach ($jobs as $j): ?><option value="<?= $j['id'] ?>" <?= $fj === (int)$j['id'] ? 'selected' : '' ?>><?= e($j['title']) ?></option><?php endforeach; ?></select>
  <span class="grow muted small"><?= $canMove ? 'Drag a card to another column, or use “Move to…”. Moving a card emails the applicant.' : 'You can view the board. Your access level cannot move applicants.' ?></span>
  <?php if ($canMove): ?><label class="check-line" style="margin:0"><input type="checkbox" id="notify" checked> Email applicants when I move them</label><?php endif; ?>
  <a class="btn ghost" href="applications.php">List view</a></form>
<div class="kanban" id="kanban" data-csrf="<?= e(csrf_token()) ?>" data-can="<?= $canMove ? 1 : 0 ?>">
<?php foreach ($cols as $c => $cards): $key = strtolower(str_replace(' ', '-', $c)); ?>
  <section class="kcol k-<?= e($key) ?>" data-status="<?= e($c) ?>">
    <header><b><?= e($c) ?></b><i><?= (int)($tot[$c] ?? 0) ?></i></header>
    <div class="kdrop">
    <?php foreach ($cards as $a): ?>
      <article class="kcard" <?= $canMove && $c !== 'More Details Needed' ? 'draggable="true"' : '' ?> data-id="<?= $a['id'] ?>" data-name="<?= e($a['name']) ?>">
        <a href="application.php?id=<?= $a['id'] ?>" class="kname"><?= e($a['name']) ?></a>
        <span class="kjob"><?= e($a['jtitle']) ?></span>
        <span class="kmeta"><?= e(ago($a['created_at'])) ?><?= $a['interview_at'] && $c === 'Interview' ? ' · ' . e(date('j M, g:i A', strtotime($a['interview_at']))) : '' ?></span>
        <?php if ($canMove && $c !== 'More Details Needed'): ?>
        <form method="post" class="kmove"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $a['id'] ?>"><input type="hidden" name="notify" value="1">
          <select name="status" aria-label="Move <?= e($a['name']) ?> to" onchange="if(!this.value)return;if(['Accepted','Hired','Rejected','Not Qualified'].indexOf(this.value)>-1&&!confirm('Move '+<?= e(json_encode($a['name'], JSON_HEX_APOS)) ?>+' to '+this.value+'? The applicant will be emailed.')){this.value='';return}this.form.submit()">
            <option value="">Move to…</option><?php foreach ($MOVABLE as $m) if ($m !== $c) echo '<option>' . e($m) . '</option>'; ?></select></form>
        <?php endif; ?>
      </article>
    <?php endforeach; if (!$cards): ?><p class="kempty">No applicants</p><?php endif; ?>
    <?php if (($tot[$c] ?? 0) > count($cards)): ?><a class="kmore" href="applications.php?status=<?= urlencode($c) ?><?= $fj ? '&job=' . $fj : '' ?>">View all <?= (int)$tot[$c] ?> →</a><?php endif; ?>
    </div>
  </section>
<?php endforeach; ?>
</div>
<div class="toast" id="toast" role="status"></div>
<script>
(function(){
  var kb=document.getElementById('kanban'); if(!kb||kb.dataset.can!=='1') return;
  var csrf=kb.dataset.csrf, drag=null, TERMINAL=['Accepted','Hired','Rejected','Not Qualified'];
  function toast(m,bad){var t=document.getElementById('toast');t.textContent=m;t.className='toast show'+(bad?' bad':'');setTimeout(function(){t.className='toast'},3200)}
  kb.querySelectorAll('.kcard[draggable]').forEach(function(c){
    c.addEventListener('dragstart',function(e){drag=c;c.classList.add('drag');e.dataTransfer.effectAllowed='move';try{e.dataTransfer.setData('text/plain',c.dataset.id)}catch(x){}});
    c.addEventListener('dragend',function(){c.classList.remove('drag');kb.querySelectorAll('.kcol').forEach(function(k){k.classList.remove('over')})});
  });
  kb.addEventListener('dragover',function(e){if(!drag)return;var r=kb.getBoundingClientRect();if(e.clientX>r.right-70)kb.scrollLeft+=24;else if(e.clientX<r.left+70)kb.scrollLeft-=24});   // edge auto-scroll
  kb.querySelectorAll('.kcol').forEach(function(col){
    col.addEventListener('dragover',function(e){if(!drag)return;e.preventDefault();col.classList.add('over')});
    col.addEventListener('dragleave',function(e){if(!col.contains(e.relatedTarget))col.classList.remove('over')});
    col.addEventListener('drop',function(e){
      e.preventDefault();col.classList.remove('over'); if(!drag)return;
      var to=col.dataset.status, from=drag.closest('.kcol').dataset.status, card=drag; drag=null;
      if(to===from)return;
      if(to==='More Details Needed'){toast('Use “More details needed” on the application page so you can tell the applicant what is missing.',true);return}
      var notify=document.getElementById('notify').checked;
      if(TERMINAL.indexOf(to)>-1 && !confirm('Move '+card.dataset.name+' to '+to+'?'+(notify?' The applicant will be emailed.':''))) return;
      var fd=new FormData();fd.append('_csrf',csrf);fd.append('ajax','1');fd.append('id',card.dataset.id);fd.append('status',to);fd.append('notify',notify?'1':'0');
      fetch('pipeline.php'+location.search,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json()}).then(function(j){
        if(j.ok){var d=col.querySelector('.kdrop');var em=d.querySelector('.kempty');if(em)em.remove();d.insertBefore(card,d.firstChild);
          var n=function(k,x){var i=k.querySelector('header i');i.textContent=Math.max(0,parseInt(i.textContent,10)+x)};n(col,1);n(kb.querySelector('.kcol[data-status="'+from+'"]'),-1);
          var sel=card.querySelector('.kmove');if(sel)sel.remove();toast(j.msg);}else toast(j.msg,true);
      }).catch(function(){toast('Network error. Nothing was changed.',true)});
    });
  });
})();
</script>
<?php admin_foot();
