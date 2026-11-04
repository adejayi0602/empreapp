<?php
require __DIR__ . '/lib/bootstrap.php';
$title = 'Enrol | Empre Technologies Training'; $desc = 'Complete your enrolment for an Empre Technologies training course.'; $active = 'training.php';
$courses = active_courses();
$errors = []; $v = ['name' => '', 'age' => '', 'sex' => '', 'location' => '', 'course_id' => '', 'email' => '', 'phone' => ''];
$pre = course_by_slug((string)($_GET['c'] ?? ''));
if ($pre) $v['course_id'] = (string)$pre['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($v as $k => $_) $v[$k] = $k === 'location' || $k === 'name' ? line(post($k, 120)) : line(post($k, 120));
    if (post('company_site') !== '') redirect('training.php');                 // honeypot
    if (isset($_SESSION['last_enrol']) && time() - $_SESSION['last_enrol'] < 10) $errors['form'] = 'Please wait a few seconds before submitting again.';
    if (mb_strlen($v['name']) < 3 || !str_contains($v['name'], ' ')) $errors['name'] = 'Please enter your full name (first and last name).';
    if (!ctype_digit($v['age']) || (int)$v['age'] < 10 || (int)$v['age'] > 99) $errors['age'] = 'Enter a valid age (10–99).';
    if (!in_array($v['sex'], SEXES, true)) $errors['sex'] = 'Please select an option.';
    if (mb_strlen($v['location']) < 2) $errors['location'] = 'Please tell us where you are located.';
    $course = ctype_digit($v['course_id']) ? q1("SELECT * FROM courses WHERE id=? AND active=1", [(int)$v['course_id']]) : null;
    if (!$course) $errors['course_id'] = 'Please select a course.';
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
    if (!preg_match('/^\+?[0-9][0-9 \-()]{6,18}$/', $v['phone'])) $errors['phone'] = 'Enter a valid phone number.';
    if (!$errors && q1("SELECT id FROM students WHERE lower(email)=lower(?) AND course_id=? AND status NOT IN ('Withdrawn','Completed')", [$v['email'], $course['id']]))
        $errors['form'] = 'You have already enrolled for this course with this email. Our team will contact you shortly; or contact us if you need help.';
    if (!$errors) {
        $r = create_student($v);
        $_SESSION['last_enrol'] = time();
        $_SESSION['enrolled'] = ['code' => $r['code'], 'name' => $v['name'], 'course' => $r['course']['title'], 'email' => $v['email'], 'phone' => $v['phone'], 'age' => $v['age'], 'sex' => $v['sex'], 'location' => $v['location']];
        $body = "New training enrolment\n\nStudent ID: {$r['code']}\nName: {$v['name']}\nAge: {$v['age']}\nSex: {$v['sex']}\nLocation: {$v['location']}\nCourse: {$r['course']['title']}\nEmail: {$v['email']}\nPhone: {$v['phone']}\n\nReview and prepare the invoice in the admin dashboard.";
        send_mail(NOTIFY_EMAIL, 'New enrolment ' . $r['code'] . ' - ' . $r['course']['title'], $body, $v['email']);
        send_mail($v['email'], 'Your Empre Technologies enrolment (' . $r['code'] . ')',
            "Hello {$v['name']},\n\nThank you for enrolling for {$r['course']['title']} with Empre Technologies.\n\nYour Student ID: {$r['code']}\n\nOur team will review your application and email your invoice and next steps shortly. Please quote your Student ID in all communication.\n\nEmpre Technologies Ltd\n" . setting('company_phone') . "\n" . setting('company_email'));
        redirect('enroll-success.php');
    }
}
include __DIR__ . '/inc/head.php';
$err = fn($k) => isset($errors[$k]) ? '<span class="req-err">' . e($errors[$k]) . '</span>' : '';
?>
<section class="banner"><div class="container">
  <div class="crumbs"><a href="index.html">Home</a><span>/</span><a href="training.php">Training</a><span>/</span><span class="cur">Enrol</span></div>
  <h1>Enrol for a course</h1><p>Complete the form below. Your unique student ID is generated as soon as you submit.</p>
</div></section>
<section class="section"><div class="container enrol-wrap">
  <div class="form-card">
    <h3>Student enrolment form</h3><p>All fields are required.</p>
    <?php if (isset($errors['form'])): ?><div class="alert fail"><?= e($errors['form']) ?></div><?php endif; ?>
    <form method="post" action="enroll.php<?= $pre ? '?c=' . e($pre['slug']) : '' ?>" novalidate>
      <?= csrf_field() ?>
      <div class="field"><label for="course_id">Course</label>
        <select id="course_id" name="course_id" required>
          <option value="">Select a course</option>
          <?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (string)$c['id'] === $v['course_id'] ? 'selected' : '' ?>><?= e($c['title']) ?><?= $c['duration'] ? ' — ' . e($c['duration']) : '' ?></option><?php endforeach; ?>
        </select><?= $err('course_id') ?></div>
      <div class="field"><label for="name">Full name</label><input id="name" name="name" value="<?= e($v['name']) ?>" autocomplete="name" required><?= $err('name') ?></div>
      <div class="form-row">
        <div class="field"><label for="age">Age</label><input id="age" name="age" type="number" min="10" max="99" inputmode="numeric" value="<?= e($v['age']) ?>" required><?= $err('age') ?></div>
        <div class="field"><label for="sex">Sex</label><select id="sex" name="sex" required><option value="">Select</option><?php foreach (SEXES as $s): ?><option <?= $v['sex'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select><?= $err('sex') ?></div>
      </div>
      <div class="field"><label for="location">Location (city / state)</label><input id="location" name="location" value="<?= e($v['location']) ?>" autocomplete="address-level2" placeholder="e.g. Akure, Ondo State" required><?= $err('location') ?></div>
      <div class="form-row">
        <div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" value="<?= e($v['email']) ?>" autocomplete="email" required><?= $err('email') ?></div>
        <div class="field"><label for="phone">Phone number</label><input id="phone" name="phone" type="tel" value="<?= e($v['phone']) ?>" autocomplete="tel" placeholder="+234…" required><?= $err('phone') ?></div>
      </div>
      <div class="hp" aria-hidden="true"><label>Leave empty <input type="text" name="company_site" tabindex="-1" autocomplete="off"></label></div>
      <button class="btn btn-primary" type="submit">Submit enrolment <svg class="ic" viewBox="0 0 24 24"><use href="#i-arrow"/></svg></button>
    </form>
  </div>
</div></section>
<?php include __DIR__ . '/inc/foot.php'; ?>
