<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin();
require_perm('students.view');
$canEdit = can('students.edit'); $canBill = can('billing.manage');
$id = (int)($_GET['id'] ?? 0);
$s = q1("SELECT * FROM students WHERE id=?", [$id]);
if (!$s) { flash('Student not found.', 'err'); redirect('students.php'); }
$me = $_SESSION['admin']['name'];
$date = fn($k) => preg_match('/^\d{4}-\d{2}-\d{2}$/', post($k)) ? post($k) : '';
$amt = fn($k) => round(max(0, (float)str_replace(',', '', post($k, 20))), 2);

function give_receipt(int $pid): void {   // assigns a receipt number when a payment becomes Confirmed
    $p = q1("SELECT * FROM payments WHERE id=?", [$pid]);
    if (!$p) return;
    if (!$p['receipt_no']) {
        insert_with_code('REC', 'payments', 'receipt_no', fn($code) => run("UPDATE payments SET receipt_no=? WHERE id=?", [$code, $pid]));
    }
    run("UPDATE payments SET status='Confirmed', confirmed_at=COALESCE(confirmed_at,?), confirmed_by=COALESCE(confirmed_by,?) WHERE id=?", [now(), $_SESSION['admin']['name'], $pid]);
}
function new_invoice(int $sid, string $desc, float $amount, string $due): int {
    $iid = 0;
    insert_with_code('INV', 'invoices', 'invoice_no', function ($code) use (&$iid, $sid, $desc, $amount, $due) {
        $iid = run("INSERT INTO invoices(invoice_no,student_id,description,amount,issued_on,due_date,status,created_at) VALUES(?,?,?,?,?,?, 'Open', ?)", [$code, $sid, $desc, $amount, date('Y-m-d'), $due, now()]);
    });
    return $iid;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $a = post('action');
    $back = "student.php?id=$id";
    // Server-side permission check for every action (the UI also hides buttons, but this is what enforces it)
    $need = ['profile' => 'students.edit', 'application' => 'students.edit', 'add_invoice' => 'billing.manage', 'quick_plan' => 'billing.manage',
             'cancel_invoice' => 'billing.manage', 'reopen_invoice' => 'billing.manage', 'add_payment' => 'billing.manage', 'payment_status' => 'billing.manage',
             'delete_invoice' => 'billing.delete', 'delete_payment' => 'billing.delete', 'delete_student' => 'students.delete'];
    if (!isset($need[$a])) deny();
    require_perm($need[$a]);
    switch ($a) {
    case 'profile':
        $name = line(post('name', 120)); $email = line(post('email', 150)); $age = (int)post('age', 3);
        if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || $age < 1 || !in_array(post('sex'), SEXES, true)) { flash('Please check the profile details (name, age, sex, email).', 'err'); break; }
        $course = q1("SELECT * FROM courses WHERE id=?", [(int)post('course_id')]) ?: ['id' => $s['course_id'], 'title' => $s['course_title']];
        run("UPDATE students SET name=?,age=?,sex=?,location=?,email=?,phone=?,course_id=?,course_title=?,updated_at=? WHERE id=?",
            [$name, $age, post('sex'), line(post('location', 120)), $email, line(post('phone', 40)), $course['id'], $course['title'], now(), $id]);
        if ($course['id'] != $s['course_id']) log_act('student', $id, 'Course changed to ' . $course['title'] . ' (fee unchanged; adjust under Application if needed)');
        log_act('student', $id, 'Profile updated'); flash('Profile saved.'); break;
    case 'application':
        $st = in_array(post('status'), STUDENT_STATUSES, true) ? post('status') : $s['status'];
        $plan = in_array(post('plan'), PLANS, true) ? post('plan') : $s['plan'];
        $fee = $amt('fee'); $disc = min($amt('discount'), $fee);
        run("UPDATE students SET status=?,plan=?,fee=?,discount=?,next_action=?,notes=?,follow_up=?,updated_at=? WHERE id=?",
            [$st, $plan, $fee, $disc, post('next_action', 500), post('notes', 5000), $date('follow_up'), now(), $id]);
        $ch = [];
        if ($st !== $s['status']) $ch[] = "Status: {$s['status']} → $st";
        if ($plan !== $s['plan']) $ch[] = "Payment plan: $plan";
        if ($fee != $s['fee'] || $disc != $s['discount']) $ch[] = 'Fee ' . money($fee) . ', discount ' . money($disc);
        if (post('next_action', 500) !== (string)$s['next_action']) $ch[] = 'Next action: ' . (post('next_action', 500) ?: '(cleared)');
        if (post('notes', 5000) !== (string)$s['notes']) $ch[] = 'Notes updated';
        if ($ch) log_act('student', $id, implode(' · ', $ch));
        flash('Application saved.'); break;
    case 'add_invoice':
        $amount = $amt('amount'); if ($amount <= 0) { flash('Enter an invoice amount.', 'err'); break; }
        $desc = post('description', 200) ?: ('Tuition: ' . $s['course_title']);
        $iid = new_invoice($id, $desc, $amount, $date('due_date'));
        log_act('student', $id, 'Invoice created: ' . qv("SELECT invoice_no FROM invoices WHERE id=?", [$iid]) . ' for ' . money($amount)); flash('Invoice created.'); break;
    case 'quick_plan':
        $t = student_totals($s); $remaining = round($t['due'] - $t['invoiced'], 2);
        if ($remaining <= 0) { flash('The full fee is already invoiced. Adjust the fee or cancel an invoice first.', 'err'); break; }
        $plan = post('plan_type'); $first = $date('first_due') ?: date('Y-m-d', strtotime('+7 days'));
        if ($plan === 'full') {
            new_invoice($id, 'Tuition (full payment): ' . $s['course_title'], $remaining, $first);
            run("UPDATE students SET plan='Full payment' WHERE id=?", [$id]); log_act('student', $id, 'Full-payment invoice created for ' . money($remaining));
        } else {
            $n = max(2, min(12, (int)post('n', 2))); $each = floor($remaining / $n * 100) / 100; $sum = 0;
            for ($i = 1; $i <= $n; $i++) {
                $amount = $i === $n ? round($remaining - $sum, 2) : $each; $sum += $amount;
                new_invoice($id, "Tuition installment $i of $n: " . $s['course_title'], $amount, date('Y-m-d', strtotime("$first +" . ($i - 1) . ' month')));
            }
            run("UPDATE students SET plan='Installments' WHERE id=?", [$id]); log_act('student', $id, "Installment plan created: $n invoices totalling " . money($remaining));
        }
        flash('Invoice(s) created.'); break;
    case 'cancel_invoice':
        run("UPDATE invoices SET status='Cancelled' WHERE id=? AND student_id=?", [(int)post('iid'), $id]); log_act('student', $id, 'Invoice cancelled'); flash('Invoice cancelled.'); break;
    case 'reopen_invoice':
        run("UPDATE invoices SET status='Open' WHERE id=? AND student_id=?", [(int)post('iid'), $id]); flash('Invoice re-opened.'); break;
    case 'delete_invoice':
        if (qv("SELECT COUNT(*) FROM payments WHERE invoice_id=?", [(int)post('iid')])) { flash('This invoice has payments recorded; cancel it instead.', 'err'); break; }
        run("DELETE FROM invoices WHERE id=? AND student_id=?", [(int)post('iid'), $id]); flash('Invoice deleted.'); break;
    case 'add_payment':
        $amount = $amt('amount'); $iid = (int)post('invoice_id') ?: null; $status = post('pstatus') === 'Pending' ? 'Pending' : 'Confirmed';
        if ($amount <= 0) { flash('Enter the payment amount.', 'err'); break; }
        if ($iid) {
            $inv = q1("SELECT * FROM invoices WHERE id=? AND student_id=?", [$iid, $id]);
            if (!$inv || $inv['status'] === 'Cancelled') { flash('Invoice not available.', 'err'); break; }
            $stt = invoice_state($inv);
            if ($status === 'Confirmed' && $amount > $stt['balance'] + 0.004) { flash('Amount is more than the invoice balance (' . money($stt['balance']) . ').', 'err'); break; }
        }
        $pid = run("INSERT INTO payments(student_id,invoice_id,amount,method,reference,paid_on,status,note,created_at) VALUES(?,?,?,?,?,?,?,?,?)",
            [$id, $iid, $amount, in_array(post('method'), PAY_METHODS, true) ? post('method') : 'Bank transfer', line(post('reference', 100)), $date('paid_on') ?: date('Y-m-d'), 'Pending', post('note', 300), now()]);
        if ($status === 'Confirmed') give_receipt($pid);
        log_act('student', $id, 'Payment ' . strtolower($status) . ': ' . money($amount)); flash($status === 'Confirmed' ? 'Payment confirmed. Receipt generated.' : 'Payment recorded as pending.'); break;
    case 'payment_status':
        $pid = (int)post('pid'); $p = q1("SELECT * FROM payments WHERE id=? AND student_id=?", [$pid, $id]); $to = post('to');
        if (!$p) break;
        if ($to === 'Confirmed') {
            if ($p['invoice_id'] && ($inv = q1("SELECT * FROM invoices WHERE id=?", [$p['invoice_id']])) && $p['status'] !== 'Confirmed') {
                if ($p['amount'] > invoice_state($inv)['balance'] + 0.004) { flash('Amount exceeds the invoice balance.', 'err'); break; }
            }
            give_receipt($pid); log_act('student', $id, 'Payment confirmed: ' . money($p['amount'])); flash('Payment confirmed. Receipt generated.');
        } elseif (in_array($to, ['Pending', 'Rejected'], true)) {
            run("UPDATE payments SET status=? WHERE id=?", [$to, $pid]); log_act('student', $id, "Payment marked $to: " . money($p['amount'])); flash("Payment marked $to.");
        }
        break;
    case 'delete_payment':
        $p = q1("SELECT * FROM payments WHERE id=? AND student_id=?", [(int)post('pid'), $id]);
        if ($p && $p['status'] !== 'Confirmed') { run("DELETE FROM payments WHERE id=?", [$p['id']]); flash('Payment removed.'); }
        else flash('Confirmed payments have a receipt and cannot be deleted. Mark it Rejected if it was a mistake.', 'err');
        break;
    case 'delete_student':
        if (qv("SELECT COUNT(*) FROM payments WHERE student_id=? AND status='Confirmed'", [$id])) { flash('This student has confirmed payments. Set status to Withdrawn instead of deleting.', 'err'); break; }
        run("DELETE FROM students WHERE id=?", [$id]); run("DELETE FROM activity WHERE entity='student' AND entity_id=?", [$id]); flash('Student deleted.'); redirect('students.php');
    }
    redirect($back);
}

$t = student_totals($s);
$courses = q("SELECT id,title FROM courses ORDER BY sort,title");
$invoices = q("SELECT * FROM invoices WHERE student_id=? ORDER BY id", [$id]);
$payments = q("SELECT p.*, i.invoice_no FROM payments p LEFT JOIN invoices i ON i.id=p.invoice_id WHERE p.student_id=? ORDER BY p.id DESC", [$id]);
$openInv = [];
foreach ($invoices as &$i) { $i['st'] = invoice_state($i); if ($i['status'] !== 'Cancelled' && $i['st']['balance'] > 0.004) $openInv[] = $i; } unset($i);
admin_head($s['name'], 'students'); ?>
<p><a href="students.php" class="muted">← All students</a></p>
<div class="card" style="display:flex;gap:18px;flex-wrap:wrap;align-items:center;justify-content:space-between">
  <div><span class="sid"><?= e($s['student_code']) ?></span> &nbsp;<?= badge($s['status']) ?> <?= badge($t['label']) ?><br><span class="muted"><?= e($s['course_title']) ?> · applied <?= e(dt($s['created_at'])) ?></span></div>
  <?php if (can('billing.view')): ?><div class="totals" style="margin:0;min-width:min(100%,560px)"><div><small>Total due</small><b><?= e(money($t['due'])) ?></b></div><div><small>Invoiced</small><b><?= e(money($t['invoiced'])) ?></b></div><div><small>Paid</small><b><?= e(money($t['paid'])) ?></b></div><div class="bal"><small>Balance</small><b><?= e(money(max(0, $t['balance']))) ?></b></div></div><?php endif; ?>
</div>

<?php if ($canEdit): ?>
<div class="grid g2" style="margin-bottom:18px">
<form method="post" class="card" style="margin:0"><?= csrf_field() ?><input type="hidden" name="action" value="profile"><h3>Student profile</h3>
  <div class="f"><label>Full name</label><input name="name" required value="<?= e($s['name']) ?>"></div>
  <div class="row3 row"><div class="f"><label>Age</label><input type="number" name="age" min="1" max="99" required value="<?= e($s['age']) ?>"></div>
  <div class="f"><label>Sex</label><select name="sex"><?= opts(SEXES, (string)$s['sex']) ?></select></div>
  <div class="f"><label>Location</label><input name="location" value="<?= e($s['location']) ?>"></div></div>
  <div class="row"><div class="f"><label>Email</label><input type="email" name="email" required value="<?= e($s['email']) ?>"></div><div class="f"><label>Phone</label><input name="phone" value="<?= e($s['phone']) ?>"></div></div>
  <div class="f"><label>Course</label><select name="course_id"><?php foreach ($courses as $c): ?><option value="<?= $c['id'] ?>" <?= (int)$c['id'] === (int)$s['course_id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option><?php endforeach; ?></select></div>
  <button class="btn">Save profile</button></form>

<form method="post" class="card" style="margin:0"><?= csrf_field() ?><input type="hidden" name="action" value="application"><h3>Application &amp; next steps</h3>
  <div class="row"><div class="f"><label>Application status</label><select name="status"><?= opts(STUDENT_STATUSES, $s['status']) ?></select></div>
  <div class="f"><label>Payment plan</label><select name="plan"><?= opts(PLANS, $s['plan']) ?></select></div></div>
  <div class="row3 row"><div class="f"><label>Course fee (<?= e(setting('currency')) ?>)</label><input name="fee" inputmode="decimal" value="<?= e($s['fee']) ?>"></div>
  <div class="f"><label>Discount</label><input name="discount" inputmode="decimal" value="<?= e($s['discount']) ?>"></div>
  <div class="f"><label>Follow-up date</label><input type="date" name="follow_up" value="<?= e($s['follow_up']) ?>"></div></div>
  <div class="f"><label>Next action (what to do next)</label><input name="next_action" maxlength="500" placeholder="e.g. Send invoice and confirm start date" value="<?= e($s['next_action']) ?>"></div>
  <div class="f"><label>Admin notes</label><textarea name="notes"><?= e($s['notes']) ?></textarea></div>
  <button class="btn">Save application</button></form>
</div>
<?php else: ?>
<div class="grid g2" style="margin-bottom:18px">
  <div class="card" style="margin:0"><h3>Student profile <span class="muted small">(read-only)</span></h3><dl class="kv">
    <dt>Name</dt><dd><?= e($s['name']) ?></dd><dt>Age / Sex</dt><dd><?= e($s['age']) ?> / <?= e($s['sex']) ?></dd><dt>Location</dt><dd><?= e($s['location']) ?></dd>
    <dt>Email</dt><dd><?= e($s['email']) ?></dd><dt>Phone</dt><dd><?= e($s['phone']) ?></dd><dt>Course</dt><dd><?= e($s['course_title']) ?></dd></dl></div>
  <div class="card" style="margin:0"><h3>Application <span class="muted small">(read-only)</span></h3><dl class="kv">
    <dt>Status</dt><dd><?= badge($s['status']) ?></dd><dt>Payment plan</dt><dd><?= e($s['plan']) ?></dd>
    <?php if (can('billing.view')): ?><dt>Course fee</dt><dd><?= e(money($s['fee'])) ?></dd><dt>Discount</dt><dd><?= e(money($s['discount'])) ?></dd><?php endif; ?></dl></div>
</div>
<?php endif; ?>
<?php if (can('billing.view')): ?>

<div class="card"><h3>Invoices</h3>
<?php if ($invoices): ?><div class="tw"><table><thead><tr><th>Invoice</th><th>Description</th><th>Due</th><th class="num">Amount</th><th class="num">Paid</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($invoices as $i): ?><tr><td><b><?= e($i['invoice_no']) ?></b></td><td><?= e($i['description']) ?></td><td class="muted small"><?= e(dt($i['due_date'])) ?></td>
<td class="num"><?= e(money($i['amount'])) ?></td><td class="num"><?= e(money($i['st']['paid'])) ?></td><td><?= badge($i['st']['label']) ?></td>
<td class="num"><div class="actions"><a class="btn sm ghost" target="_blank" href="invoice.php?id=<?= $i['id'] ?>">View / Print</a>
<?php if (can('billing.delete') && !$i['st']['paid']): ?><form method="post" data-confirm="Delete this invoice permanently?"><?= csrf_field() ?><input type="hidden" name="action" value="delete_invoice"><input type="hidden" name="iid" value="<?= $i['id'] ?>"><button class="btn sm danger">Delete</button></form><?php endif; ?>
<?php if (!$canBill): ?><?php elseif ($i['status'] === 'Cancelled'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="reopen_invoice"><input type="hidden" name="iid" value="<?= $i['id'] ?>"><button class="btn sm ghost">Re-open</button></form>
<?php else: ?><form method="post" data-confirm="Cancel this invoice?"><?= csrf_field() ?><input type="hidden" name="action" value="cancel_invoice"><input type="hidden" name="iid" value="<?= $i['id'] ?>"><button class="btn sm danger">Cancel</button></form><?php endif; ?></div></td></tr>
<?php endforeach; ?></tbody></table></div><?php else: ?><p class="muted">No invoices yet. Create one below.</p><?php endif; ?>
<?php if ($canBill): ?><div class="grid g2" style="margin-top:20px">
  <form method="post" class="card" style="margin:0;background:var(--bg)"><?= csrf_field() ?><input type="hidden" name="action" value="quick_plan"><h3>Quick payment plan</h3>
    <p class="muted small" style="margin-top:-6px">Invoices the remaining <?= e(money(max(0, $t['due'] - $t['invoiced']))) ?> of the fee.</p>
    <div class="row"><div class="f"><label>Plan</label><select name="plan_type"><option value="full">Full payment (1 invoice)</option><option value="installments">Installments</option></select></div>
    <div class="f"><label>No. of installments</label><input type="number" name="n" min="2" max="12" value="3"></div></div>
    <div class="f"><label>First due date</label><input type="date" name="first_due" value="<?= date('Y-m-d', strtotime('+7 days')) ?>"></div><button class="btn dark">Generate invoice(s)</button></form>
  <form method="post" class="card" style="margin:0;background:var(--bg)"><?= csrf_field() ?><input type="hidden" name="action" value="add_invoice"><h3>Custom invoice</h3>
    <div class="f"><label>Description</label><input name="description" placeholder="Tuition: <?= e($s['course_title']) ?>"></div>
    <div class="row"><div class="f"><label>Amount (<?= e(setting('currency')) ?>)</label><input name="amount" inputmode="decimal" required></div><div class="f"><label>Due date</label><input type="date" name="due_date"></div></div><button class="btn dark">Create invoice</button></form>
</div><?php endif; ?></div>

<div class="card"><h3>Payments &amp; receipts</h3>
<?php if ($payments): ?><div class="tw"><table><thead><tr><th>Date</th><th>Invoice</th><th>Method / reference</th><th class="num">Amount</th><th>Status</th><th>Receipt</th><th></th></tr></thead><tbody>
<?php foreach ($payments as $p): ?><tr><td class="muted small"><?= e(dt($p['paid_on'])) ?></td><td><?= e($p['invoice_no'] ?: 'On account') ?></td>
<td><?= e($p['method']) ?><br><span class="muted small"><?= e($p['reference'] ?: '') ?></span></td><td class="num"><b><?= e(money($p['amount'])) ?></b></td><td><?= badge($p['status']) ?></td>
<td><?= $p['receipt_no'] && $p['status'] === 'Confirmed' ? '<a class="btn sm ghost" target="_blank" href="receipt.php?id=' . $p['id'] . '">' . e($p['receipt_no']) . '</a>' : '<span class="muted small">—</span>' ?></td>
<td class="num"><div class="actions">
<?php if ($canBill && $p['status'] !== 'Confirmed'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="payment_status"><input type="hidden" name="pid" value="<?= $p['id'] ?>"><input type="hidden" name="to" value="Confirmed"><button class="btn sm">Confirm</button></form><?php endif; ?>
<?php if ($canBill && $p['status'] === 'Pending'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="payment_status"><input type="hidden" name="pid" value="<?= $p['id'] ?>"><input type="hidden" name="to" value="Rejected"><button class="btn sm ghost">Reject</button></form><?php endif; ?>
<?php if ($canBill && $p['status'] === 'Confirmed'): ?><form method="post" data-confirm="Mark this payment Rejected? (e.g. it bounced or was entered by mistake)"><?= csrf_field() ?><input type="hidden" name="action" value="payment_status"><input type="hidden" name="pid" value="<?= $p['id'] ?>"><input type="hidden" name="to" value="Rejected"><button class="btn sm danger">Reverse</button></form><?php endif; ?>
<?php if (can('billing.delete') && $p['status'] !== 'Confirmed'): ?><form method="post" data-confirm="Delete this payment record?"><?= csrf_field() ?><input type="hidden" name="action" value="delete_payment"><input type="hidden" name="pid" value="<?= $p['id'] ?>"><button class="btn sm danger">Delete</button></form><?php endif; ?></div></td></tr>
<?php endforeach; ?></tbody></table></div><?php else: ?><p class="muted">No payments recorded yet.</p><?php endif; ?>
<?php if ($canBill): ?><form method="post" class="card" style="margin:20px 0 0;background:var(--bg)"><?= csrf_field() ?><input type="hidden" name="action" value="add_payment"><h3>Record a payment</h3>
  <div class="row3 row"><div class="f"><label>Apply to invoice</label><select name="invoice_id"><option value="">On account (no invoice)</option>
    <?php foreach ($openInv as $i): ?><option value="<?= $i['id'] ?>"><?= e($i['invoice_no']) ?> — balance <?= e(money($i['st']['balance'])) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label>Amount (<?= e(setting('currency')) ?>)</label><input name="amount" inputmode="decimal" required value="<?= $openInv ? e($openInv[0]['st']['balance']) : '' ?>"></div>
  <div class="f"><label>Payment date</label><input type="date" name="paid_on" value="<?= date('Y-m-d') ?>"></div></div>
  <div class="row3 row"><div class="f"><label>Method</label><select name="method"><?= opts(PAY_METHODS) ?></select></div>
  <div class="f"><label>Reference / teller no.</label><input name="reference"></div>
  <div class="f"><label>Status</label><select name="pstatus"><option value="Confirmed">Confirmed — generate receipt now</option><option value="Pending">Pending — confirm later</option></select></div></div>
  <div class="f"><label>Note (optional)</label><input name="note"></div>
  <button class="btn">Save payment</button></form><?php endif; ?></div>
<?php endif; /* billing.view */ ?>

<?php if (can('tasks.viewall')): $stasks = q("SELECT t.*, a.name aname, a.title atitle FROM tasks t LEFT JOIN admins a ON a.id=t.assigned_to WHERE t.student_id=? ORDER BY t.id", [$id]); ?>
<div class="card"><h3>Tasks for this student</h3>
<?php if ($stasks): ?><div class="tw"><table><thead><tr><th>Task</th><th>Assigned to</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($stasks as $k): ?><tr><td><a href="task.php?id=<?= $k['id'] ?>"><b><?= e($k['title']) ?></b></a><?= $k['due_date'] ? '<br><span class="muted small">Due ' . e(dt($k['due_date'])) . '</span>' : '' ?></td>
<td><?php if (is_super()): ?><form method="post" action="task.php" style="display:flex;gap:6px;margin:0"><?= csrf_field() ?><input type="hidden" name="action" value="assign"><input type="hidden" name="id" value="<?= $k['id'] ?>"><input type="hidden" name="due_date" value="<?= e($k['due_date']) ?>"><input type="hidden" name="back" value="student.php?id=<?= $id ?>">
<select name="assignee" style="min-width:190px"><?= staff_options($k['assigned_to'] ? (int)$k['assigned_to'] : null, 'Not assigned') ?></select><button class="btn sm dark">Save</button></form>
<?php else: ?><?= $k['aname'] ? e($k['aname']) . ' <span class="muted small">' . e($k['atitle']) . '</span>' : '<span class="muted">Nobody yet</span>' ?><?php endif; ?></td>
<td><?= badge($k['status']) ?></td><td class="num"><a class="btn sm ghost" href="task.php?id=<?= $k['id'] ?>">Open</a></td></tr>
<?php endforeach; ?></tbody></table></div><?php else: ?><p class="muted">No tasks yet.</p><?php endif; ?>
<?php if (is_super()): ?><form method="post" action="task.php" class="card" style="margin:16px 0 0;background:var(--bg)"><?= csrf_field() ?><input type="hidden" name="action" value="create"><input type="hidden" name="student_id" value="<?= $id ?>"><input type="hidden" name="back" value="student.php?id=<?= $id ?>"><h3>Add &amp; assign a task</h3>
<div class="row3 row"><div class="f"><label>Task</label><input name="title" required placeholder="e.g. Send welcome pack and class schedule"></div><div class="f"><label>Assign to</label><select name="assignee"><?= staff_options(null, 'Not assigned yet') ?></select></div><div class="f"><label>Due date</label><input type="date" name="due_date"></div></div>
<div class="f"><label>Instructions (optional)</label><input name="details"></div><button class="btn dark">Create task</button></form><?php endif; ?></div>
<?php endif; ?>

<div class="grid g2"><div><?php activity_box('student', $id); ?></div>
<div><?php if (can('students.delete')): ?><form method="post" class="card" data-confirm="Delete this student and all invoices? This cannot be undone."><?= csrf_field() ?><input type="hidden" name="action" value="delete_student"><h3>Danger zone</h3><p class="muted small">Students with confirmed payments cannot be deleted; set the status to Withdrawn instead.</p><button class="btn danger sm">Delete student</button></form><?php endif; ?></div></div>
<?php admin_foot();
