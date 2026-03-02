<?php
// views/home_sidebar.php
?>
<div class="card">
  <div class="pad">
    <div class="topbar">
      <div>
        <div class="brand"><?= htmlspecialchars(APP_NAME) ?></div>
        <div class="muted">Карта товарів/послуг поблизу</div>
      </div>

    </div>

    <!-- Пошук -->
    <div class="row mt12">
      <input id="q" placeholder="Пошук: суші, зарядка, ковбаса..." />
      <button id="btnSearch" type="button">Знайти</button>
    </div>

    <!-- Підказка -->
    <div class="hint">
      Геолокація визначається автоматично при вході (одноразово). Якщо браузер питає дозвіл — натисни “Allow”.
      Якщо відмовиш — показуватиметься карта за останньою збереженою локацією або за Києвом.
    </div>
  </div>

  <!-- Результати -->
  <div class="pad border-top">
    <div class="results">
      Результати: <span id="count">0</span>
      <span id="status" class="muted status"></span>
    </div>
  </div>

  <!-- Список -->
  <div id="list" class="list"></div>
</div>