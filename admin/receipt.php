<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin(); require_perm('docs.view'); require __DIR__ . '/_doc.php';
$p = q1("SELECT * FROM payments WHERE id=? AND status='Confirmed' AND receipt_no IS NOT NULL", [(int)($_GET['id'] ?? 0)]);
if (!$p) { http_response_code(404); exit('Receipt not found (only confirmed payments have receipts).'); }
$st = q1("SELECT * FROM students WHERE id=?", [$p['student_id']]);
$inv = $p['invoice_id'] ? q1("SELECT * FROM invoices WHERE id=?", [$p['invoice_id']]) : null;
$is = $inv ? invoice_state($inv) : null; $t = student_totals($st);
doc_head('Receipt ' . $p['receipt_no']); echo '<div class="stamp paid">PAID</div>';
doc_company('RECEIPT', $p['receipt_no'], dt($p['paid_on']));
doc_billto($st);
echo '<div class="amount-box"><span>Amount received</span><b>' . e(money($p['amount'])) . '</b></div>';
echo '<table><thead><tr><th>Payment details</th><th></th></tr></thead><tbody>
<tr><td>Payment for</td><td class="r">' . e($inv ? $inv['invoice_no'] . ' — ' . $inv['description'] : 'Tuition: ' . $st['course_title']) . '</td></tr>
<tr><td>Payment method</td><td class="r">' . e($p['method']) . '</td></tr>
<tr><td>Reference</td><td class="r">' . e($p['reference'] ?: '—') . '</td></tr>
<tr><td>Payment date</td><td class="r">' . e(dt($p['paid_on'])) . '</td></tr>
<tr><td>Received by</td><td class="r">' . e($p['confirmed_by'] ?: '—') . '</td></tr></tbody></table>';
echo '<div class="tot">' . ($is ? '<div><span>Invoice balance remaining</span><span>' . e(money(max(0, $is['balance']))) . '</span></div>' : '') . '<div class="big"><span>Total course balance</span><span>' . e(money(max(0, $t['balance']))) . '</span></div></div>';
doc_foot();
