<?php // VentureIQ – Footer ?>
</main>

<footer role="contentinfo">
  <div class="container">
    <p style="margin-bottom:8px">
      <a href="<?= url('/') ?>" class="logo" style="font-size:1rem;display:inline-flex;gap:6px;align-items:center">⚡ VentureIQ</a>
    </p>
    <p>© <?= date('Y') ?> VentureIQ &nbsp;·&nbsp;
       <a href="<?= url('/how.php') ?>"    style="color:var(--text4);font-size:.8rem">How It Works</a> &nbsp;·&nbsp;
       <a href="<?= url('/explore.php') ?>" style="color:var(--text4);font-size:.8rem">Explore Models</a>
    </p>
  </div>
</footer>

<!-- Main JS — BASE_PATH-aware -->
<script src="<?= BASE_PATH ?>/js/app.js"></script>
<script src="<?= BASE_PATH ?>/js/app.js"></script>

<script src="<?= BASE_PATH ?>/js/charts.js"></script>

<?php if (!empty($inlineScript)): ?>
<script><?= $inlineScript ?></script>
<?php endif; ?>
<?php if (!empty($inlineScript)): ?>
<script><?= $inlineScript ?></script>
<?php endif; ?>

<!-- Mobile nav responsive -->
<style>
@media(max-width:768px){
  #navToggle{display:block!important}
  #navMenu{display:none;position:absolute;top:62px;left:0;right:0;background:var(--bg2);border-bottom:1px solid var(--border);padding:12px;flex-direction:column;z-index:300}
  #navMenu.nav-open{display:flex!important}
  .nav-links .nav-link,.nav-links .btn{padding:10px 14px;width:100%;text-align:left}
}
</style>

</body>
</html>
