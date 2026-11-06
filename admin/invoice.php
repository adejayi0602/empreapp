<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin(); require_perm('docs.view'); require __DIR__ . '/_doc.php';
$i = q1("SELECT * FROM invoices WHERE id=?", [(int)($_GET['id'] ?? 0)]);
if (!$i) { http_response_code(404); exit('Invoice not found'); }
$st = q1("SELECT * FROM students WHERE id=?", [$i['student_id']]);
$s = invoice_state($i); $set = settings();
$stamp = $s['label'] === 'Paid' ? '<div class="stamp paid">PAID</div>' : ($s['label'] === 'Partial' ? '<div class="stamp part">PART-PAID</div>' : ($s['label'] === 'Cancelled' ? '<div class="stamp void">CANCELLED</div>' : ''));
$pays = q("SELECT * FROM payments WHERE invoice_id=? AND status='Confirmed' ORDER BY paid_on, id", [$i['id']]);
doc_head('Invoice ' . $i['invoice_no']); echo $stamp;
doc_company('INVOICE', $i['invoice_no'], dt($i['issued_on']));
doc_billto($st);
echo '<table><thead><tr><th>Description</th><th class="r">Amount</th></tr></thead><tbody><tr><td>' . e($i['description']) . '<br><span style="color:#8d8683;font-size:12.5px">Due date: ' . e(dt($i['due_date'])) . '</span></td><td class="r">' . e(money($i['amount'])) . '</td></tr></tbody></table>';
echo '<div class="tot"><div><span>Invoice total</span><span>' . e(money($i['amount'])) . '</span></div><div><span>Paid to date</span><span>' . e(money($s['paid'])) . '</span></div><div class="big due"><span>Balance due</span><span>' . e(money(max(0, $s['balance']))) . '</span></div></div>';
if ($pays) { echo '<div class="box"><h4>Payments received</h4>'; foreach ($pays as $p) echo '<div style="display:flex;justify-content:space-between"><span>' . e(dt($p['paid_on'])) . ' · ' . e($p['method']) . ' · Receipt ' . e($p['receipt_no']) . '</span><b>' . e(money($p['amount'])) . '</b></div>'; echo '</div>'; }
if ($s['balance'] > 0.004 && $i['status'] !== 'Cancelled') {
    echo '<div class="box"><h4>How to pay</h4>';
    if ($set['bank_name'] || $set['account_number']) echo 'Bank: <b>' . e($set['bank_name']) . '</b><br>Account name: <b>' . e($set['account_name']) . '</b><br>Account number: <b>' . e($set['account_number']) . '</b><br>';
    echo 'Payment reference: <b>' . e($st['student_code']) . '</b><br>' . e($set['invoice_note']) . '</div>';
}
doc_foot();
