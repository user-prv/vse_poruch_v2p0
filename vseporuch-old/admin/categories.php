<?php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/categories.php';

$pageTitle = 'Категорії — адмінка';

$rows   = categoriesAll(false);        // всі категорії (включно з підкатегоріями)
$select = categoriesForSelect(false);  // список для select

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

include __DIR__ . '/../inc/header.php';
?>
<div class="wrap">
  <div class="card">
    <div class="pad" style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;">
      <div>
        <div style="font-weight:900;font-size:16px;">Категорії</div>
        <div class="muted">Створи категорії та підкатегорії. Іконка бажано квадратна (100×100).</div>
      </div>
      <div class="row">
        <a class="btn" href="/admin/">← Назад</a>
      </div>
    </div>

    <!-- =========================
         1) CREATE (додати категорію)
         ========================= -->
    <div class="pad border-top">
      <form method="post"
            action="/admin/categories_action.php"
            enctype="multipart/form-data"
            class="row"
            style="align-items:flex-end;">

        <input type="hidden" name="action" value="create">

        <div style="flex:1;min-width:220px;">
          <div class="muted">Назва</div>
          <input name="name" required placeholder="Напр.: Їжа / Техніка / Послуги">
        </div>

        <div style="min-width:240px;">
          <div class="muted">Батьківська (опційно)</div>
          <select name="parent_id">
            <option value="">— без батьківської —</option>
            <?php foreach ($select as $c): ?>
              <option value="<?= (int)$c['id'] ?>"><?= h($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="min-width:240px;">
          <div class="muted">Фото/іконка (опційно)</div>
          <input type="file" name="icon" accept="image/*">
        </div>

        <button class="btn primary" type="submit">+ Додати</button>
      </form>
    </div>

    <!-- =========================
         2) LIST + UPDATE + DELETE
         ========================= -->
    <div class="pad border-top">
      <table style="width:100%;border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left;padding:10px;border-top:1px solid var(--border);">Фото</th>
            <th style="text-align:left;padding:10px;border-top:1px solid var(--border);">Категорія</th>
            <th style="text-align:left;padding:10px;border-top:1px solid var(--border);">Батьківська</th>
            <th style="text-align:left;padding:10px;border-top:1px solid var(--border);">Редагувати</th>
            <th style="text-align:left;padding:10px;border-top:1px solid var(--border);">Дії</th>
          </tr>
        </thead>
        <tbody>
          <?php
            // Мапа id->name для виводу parent
            $byId = [];
            foreach ($rows as $r) $byId[(int)$r['id']] = (string)$r['name'];
          ?>

          <?php foreach ($rows as $r): ?>
            <?php
              $id   = (int)$r['id'];
              $pid  = isset($r['parent_id']) ? (int)$r['parent_id'] : 0;
              $icon = $r['icon_path'] ?? null;
              $name = (string)($r['name'] ?? '');
            ?>
            <tr>
              <!-- Фото -->
              <td style="padding:10px;border-top:1px solid var(--border);width:90px;">
                <?php if ($icon): ?>
                  <img src="<?= h((string)$icon) ?>" alt="icon"
                       style="width:52px;height:52px;object-fit:cover;border-radius:12px;border:1px solid var(--border);">
                <?php else: ?>
                  <div class="muted">—</div>
                <?php endif; ?>
              </td>

              <!-- Назва (поточна) -->
              <td style="padding:10px;border-top:1px solid var(--border);">
                <strong><?= h($name) ?></strong><br>
                <span class="muted">ID: <?= $id ?></span>
              </td>

              <!-- Поточна батьківська -->
              <td style="padding:10px;border-top:1px solid var(--border);">
                <?php if ($pid && isset($byId[$pid])): ?>
                  <?= h($byId[$pid]) ?>
                <?php else: ?>
                  <span class="muted">—</span>
                <?php endif; ?>
              </td>

              <!-- UPDATE form -->
              <td style="padding:10px;border-top:1px solid var(--border);">
                <form method="post"
                      action="/admin/categories_action.php"
                      enctype="multipart/form-data"
                      style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">

                  <input type="hidden" name="action" value="update">
                  <input type="hidden" name="id" value="<?= $id ?>">

                  <div style="min-width:200px;flex:1;">
                    <div class="muted">Нова назва</div>
                    <input name="name" value="<?= h($name) ?>" required>
                  </div>

                  <div style="min-width:220px;">
                    <div class="muted">Батьківська</div>
                    <select name="parent_id">
                      <option value="">— без батьківської —</option>
                      <?php foreach ($select as $c): ?>
                        <?php
                          $cid = (int)$c['id'];
                          if ($cid === $id) continue; // не можна робити parent = self
                          $selected = ($pid === $cid) ? 'selected' : '';
                        ?>
                        <option value="<?= $cid ?>" <?= $selected ?>><?= h($c['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div style="min-width:220px;">
                    <div class="muted">Нова іконка</div>
                    <input type="file" name="icon" accept="image/*">
                  </div>

                  <button class="btn primary" type="submit">Зберегти</button>
                </form>
              </td>

              <!-- DELETE -->
              <td style="padding:10px;border-top:1px solid var(--border);width:140px;">
                <form method="post" action="/admin/categories_action.php"
                      onsubmit="return confirm('Видалити категорію? Якщо є підкатегорії — видалення не пройде.');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $id ?>">
                  <button class="btn" type="submit">Видалити</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php if (!$rows): ?>
            <tr>
              <td colspan="5" class="muted" style="padding:12px;border-top:1px solid var(--border);">
                Категорій ще немає.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>

      <div class="muted" style="margin-top:10px;">
        Порада: якщо потрібно видалити категорію з підкатегоріями — спочатку видали/перенеси підкатегорії.
      </div>
    </div>

  </div>
</div>
<?php include __DIR__ . '/../inc/footer.php'; ?>